<?php
require '../config.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// GESTION DES CLIENTS
$success = $error = '';

// AJOUTER CLIENT
if ($_POST['action'] ?? '' === 'add_customer') {
    $nom = secure_data($_POST['nom']);
    $email = secure_data($_POST['email']);
    $telephone = secure_data($_POST['telephone']);
    $adresse = secure_data($_POST['adresse']);
    
    // Vérifier si l'email existe déjà
    $check_email = mysqli_query($conn, "SELECT id FROM clients WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $error = "❌ Un client avec cet email existe déjà !";
    } else {
        $sql = "INSERT INTO clients (nom, email, telephone, adresse) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssss', $nom, $email, $telephone, $adresse);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "✅ Client ajouté avec succès !";
        } else {
            $error = "❌ Erreur lors de l'ajout : " . mysqli_error($conn);
        }
    }
}

// MODIFIER CLIENT
if ($_POST['action'] ?? '' === 'edit_customer') {
    $client_id = intval($_POST['client_id']);
    $nom = secure_data($_POST['nom']);
    $email = secure_data($_POST['email']);
    $telephone = secure_data($_POST['telephone']);
    $adresse = secure_data($_POST['adresse']);
    
    $sql = "UPDATE clients SET nom = ?, email = ?, telephone = ?, adresse = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssssi', $nom, $email, $telephone, $adresse, $client_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "✅ Client modifié avec succès !";
    } else {
        $error = "❌ Erreur lors de la modification : " . mysqli_error($conn);
    }
}

// SUPPRIMER CLIENT
if ($_GET['action'] ?? '' === 'delete' && isset($_GET['id'])) {
    $client_id = intval($_GET['id']);
    
    // Vérifier si le client a des commandes
    $check_orders = mysqli_query($conn, "SELECT COUNT(*) as count FROM commandes WHERE client_id = $client_id");
    $orders_data = mysqli_fetch_assoc($check_orders);
    
    if ($orders_data['count'] > 0) {
        $error = "❌ Impossible de supprimer : ce client a des commandes associées !";
    } else {
        $sql = "DELETE FROM clients WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $client_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "✅ Client supprimé avec succès !";
        } else {
            $error = "❌ Erreur lors de la suppression : " . mysqli_error($conn);
        }
    }
}

// RÉCUPÉRER LES CLIENTS
$clients_result = mysqli_query($conn, "
    SELECT c.*, 
           (SELECT COUNT(*) FROM commandes WHERE client_id = c.id) as commandes_count,
           (SELECT SUM(total) FROM commandes WHERE client_id = c.id) as total_achats
    FROM clients c 
    ORDER BY c.created_at DESC
");
$clients = mysqli_fetch_all($clients_result, MYSQLI_ASSOC);

// CLIENT À MODIFIER
$edit_client = null;
if ($_GET['action'] ?? '' === 'edit' && isset($_GET['id'])) {
    $client_id = intval($_GET['id']);
    $edit_result = mysqli_query($conn, "SELECT * FROM clients WHERE id = $client_id");
    $edit_client = mysqli_fetch_assoc($edit_result);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Clients - Fanilo Art Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a3d5d;
            --secondary: #2a7abf;
            --accent: #eef6fb;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        
        .admin-sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, #0f2a42 100%);
            color: white;
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 3px 0;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .admin-sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left-color: var(--secondary);
        }
        
        .admin-sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.15);
            border-left-color: var(--secondary);
        }
        
        .admin-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .customer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .action-buttons .btn {
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(26, 61, 93, 0.02);
        }
        
        .badge-custom {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 10px 10px 0 0;
        }
        
        .search-box {
            border-radius: 25px;
            border: 2px solid #e9ecef;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .search-box:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 61, 93, 0.25);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-md-3 col-lg-2 admin-sidebar">
                <div class="p-3">
                    <div class="text-center mb-4">
                        <div class="customer-avatar mx-auto mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-palette"></i>
                        </div>
                        <h5 class="mb-1">Fanilo Art Studio</h5>
                        <small class="text-light opacity-75">Administration</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="fas fa-palette me-2"></i>Produits
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php">
                                <i class="fas fa-tags me-2"></i>Catégories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="customers.php">
                                <i class="fas fa-users me-2"></i>Clients
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="fas fa-shopping-cart me-2"></i>Commandes
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link text-warning" href="../boutique.php">
                                <i class="fas fa-store me-2"></i>Voir la boutique
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div class="col-md-9 col-lg-10">
                <!-- HEADER -->
                <div class="admin-header">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col">
                                <h2 class="h4 mb-0 text-primary">
                                    <i class="fas fa-users me-2"></i>Gestion des Clients
                                </h2>
                            </div>
                            <div class="col-auto">
                                <span class="text-muted">Connecté en tant que <strong><?php echo $_SESSION['admin_username']; ?></strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-fluid py-4">
                    <!-- MESSAGES -->
                    <?php if($success): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center">
                            <i class="fas fa-check-circle fa-lg me-3"></i>
                            <div class="flex-grow-1"><?php echo $success; ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
                            <div class="flex-grow-1"><?php echo $error; ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- STATISTIQUES -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="text-primary mb-0"><?php echo count($clients); ?></h3>
                                        <p class="text-muted mb-0">Clients total</p>
                                    </div>
                                    <div class="text-primary opacity-75">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card" style="border-left-color: var(--success);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="text-success mb-0">
                                            <?php 
                                            $active_clients = array_filter($clients, function($c) { 
                                                return $c['commandes_count'] > 0; 
                                            });
                                            echo count($active_clients);
                                            ?>
                                        </h3>
                                        <p class="text-muted mb-0">Clients actifs</p>
                                    </div>
                                    <div class="text-success opacity-75">
                                        <i class="fas fa-user-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card" style="border-left-color: var(--warning);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="text-warning mb-0">
                                            <?php 
                                            $new_clients = array_filter($clients, function($c) { 
                                                return strtotime($c['created_at']) > strtotime('-7 days'); 
                                            });
                                            echo count($new_clients);
                                            ?>
                                        </h3>
                                        <p class="text-muted mb-0">Nouveaux (7j)</p>
                                    </div>
                                    <div class="text-warning opacity-75">
                                        <i class="fas fa-user-plus fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card" style="border-left-color: var(--secondary);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="text-info mb-0">
                                            <?php
                                            $total_ca = array_sum(array_column($clients, 'total_achats'));
                                            echo number_format($total_ca, 0, ',', ' ');
                                            ?> MGA
                                        </h3>
                                        <p class="text-muted mb-0">Chiffre d'affaires</p>
                                    </div>
                                    <div class="text-info opacity-75">
                                        <i class="fas fa-chart-line fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BARRE D'ACTIONS -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="fas fa-search text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control search-box border-start-0" placeholder="Rechercher un client...">
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                                        <i class="fas fa-plus me-2"></i>Nouveau Client
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TABLEAU DES CLIENTS -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>Liste des Clients
                                <span class="badge bg-primary ms-2"><?php echo count($clients); ?></span>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($clients)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Aucun client enregistré</h4>
                                    <p class="text-muted mb-4">Commencez par ajouter votre premier client.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                                        <i class="fas fa-plus me-2"></i>Ajouter un client
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Client</th>
                                                <th>Contact</th>
                                                <th>Commandes</th>
                                                <th>Total Achats</th>
                                                <th>Date d'inscription</th>
                                                <th class="text-end pe-4">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($clients as $client): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="customer-avatar me-3">
                                                            <?php echo strtoupper(substr($client['nom'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <strong class="d-block"><?php echo htmlspecialchars($client['nom']); ?></strong>
                                                            <small class="text-muted">ID: #<?php echo $client['id']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <i class="fas fa-envelope text-muted me-1"></i>
                                                        <?php echo htmlspecialchars($client['email']); ?>
                                                        <?php if($client['telephone']): ?>
                                                            <br>
                                                            <i class="fas fa-phone text-muted me-1"></i>
                                                            <?php echo htmlspecialchars($client['telephone']); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $client['commandes_count'] > 0 ? 'success' : 'secondary'; ?> badge-custom">
                                                        <?php echo $client['commandes_count']; ?> commande(s)
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong class="text-success">
                                                        <?php echo number_format($client['total_achats'] ?? 0, 0, ',', ' '); ?> MGA
                                                    </strong>
                                                </td>
                                                <td>
                                                    <?php echo date('d/m/Y', strtotime($client['created_at'])); ?>
                                                    <br>
                                                    <small class="text-muted"><?php echo date('H:i', strtotime($client['created_at'])); ?></small>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="action-buttons">
                                                        <!-- MODIFIER -->
                                                        <button class="btn btn-outline-primary btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editCustomerModal<?php echo $client['id']; ?>"
                                                                title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        
                                                        <!-- SUPPRIMER -->
                                                        <a href="customers.php?action=delete&id=<?php echo $client['id']; ?>" 
                                                           class="btn btn-outline-danger btn-sm" 
                                                           title="Supprimer"
                                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer le client <?php echo addslashes($client['nom']); ?> ?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                        
                                                        <!-- DÉTAILS -->
                                                        <button class="btn btn-outline-info btn-sm" title="Détails">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL AJOUT CLIENT -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Nouveau Client
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_customer">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom complet *</label>
                                <input type="text" name="nom" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="text" name="telephone" class="form-control" placeholder="+261 34 12 34 56">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date d'inscription</label>
                                <input type="text" class="form-control" value="<?php echo date('d/m/Y'); ?>" disabled>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresse</label>
                            <textarea name="adresse" class="form-control" rows="3" placeholder="Adresse complète du client"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Enregistrer le client
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODALS MODIFICATION CLIENTS -->
    <?php foreach($clients as $client): ?>
    <div class="modal fade" id="editCustomerModal<?php echo $client['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Modifier le client
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="edit_customer">
                    <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom complet *</label>
                                <input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($client['nom']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($client['email']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="text" name="telephone" class="form-control" value="<?php echo htmlspecialchars($client['telephone']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date d'inscription</label>
                                <input type="text" class="form-control" value="<?php echo date('d/m/Y', strtotime($client['created_at'])); ?>" disabled>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresse</label>
                            <textarea name="adresse" class="form-control" rows="3"><?php echo htmlspecialchars($client['adresse']); ?></textarea>
                        </div>
                        
                        <!-- STATISTIQUES DU CLIENT -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title mb-3">
                                    <i class="fas fa-chart-bar me-2"></i>Statistiques du client
                                </h6>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h4 class="text-primary mb-0"><?php echo $client['commandes_count']; ?></h4>
                                        <small class="text-muted">Commandes</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-success mb-0"><?php echo number_format($client['total_achats'] ?? 0, 0, ',', ' '); ?> MGA</h4>
                                        <small class="text-muted">Total achats</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-info mb-0">
                                            <?php 
                                            $avg = $client['commandes_count'] > 0 ? ($client['total_achats'] / $client['commandes_count']) : 0;
                                            echo number_format($avg, 0, ',', ' ');
                                            ?> MGA
                                        </h4>
                                        <small class="text-muted">Moyenne/commande</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Modifier le client
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Recherche en temps réel
        document.querySelector('.search-box').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Animation des cartes statistiques
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>