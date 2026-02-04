<?php
require '../config.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

$success = $error = '';

// Créer la table commandes si elle n'existe pas
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS commandes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT,
        produits TEXT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        statut ENUM('en_attente', 'confirme', 'expedie', 'livre', 'annule') DEFAULT 'en_attente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Créer la table clients si elle n'existe pas
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        telephone VARCHAR(50),
        adresse TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Ajouter des données d'exemple si pas de commandes
$check_orders = mysqli_query($conn, "SELECT COUNT(*) as count FROM commandes");
$orders_count = mysqli_fetch_assoc($check_orders)['count'];

if ($orders_count == 0) {
    // Ajouter un client exemple
    mysqli_query($conn, "INSERT IGNORE INTO clients (nom, email, telephone, adresse) VALUES 
        ('Sarah Rakoto', 'sarah@email.mg', '034 12 345 67', 'Lot 123B Analakely, Antananarivo'),
        ('Jean Rabe', 'jean@email.mg', '032 98 765 43', 'Villa 56 Ivandry, Antananarivo')
    ");
    
    // Ajouter des commandes exemple
    mysqli_query($conn, "INSERT INTO commandes (client_id, produits, total, statut) VALUES 
        (1, '[{\"nom\":\"Tableau Moderne\", \"artiste\":\"Fanilo\", \"quantite\":1, \"prix\":450000}]', 450000, 'en_attente'),
        (2, '[{\"nom\":\"Sculpture Bois\", \"artiste\":\"Nirina\", \"quantite\":1, \"prix\":320000}, {\"nom\":\"Peinture Murale\", \"artiste\":\"Sandra\", \"quantite\":1, \"prix\":280000}]', 600000, 'confirme')
    ");
}

// Récupérer les commandes avec les infos clients
$orders_result = mysqli_query($conn, "
    SELECT c.*, cl.nom as client_nom, cl.email as client_email, cl.telephone, cl.adresse
    FROM commandes c 
    LEFT JOIN clients cl ON c.client_id = cl.id 
    ORDER BY c.created_at DESC
");
$orders = mysqli_fetch_all($orders_result, MYSQLI_ASSOC);

// Changer le statut d'une commande
if ($_POST['action'] ?? '' === 'update_status') {
    $order_id = intval($_POST['order_id']);
    $new_status = secure_data($_POST['new_status']);
    
    $sql = "UPDATE commandes SET statut = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $new_status, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "✅ Statut de la commande mis à jour !";
    } else {
        $error = "❌ Erreur lors de la mise à jour";
    }
    
    header('Location: orders.php');
    exit;
}

// Statistiques
$stats = [
    'total' => 0,
    'en_attente' => 0,
    'confirme' => 0,
    'expedie' => 0,
    'livre' => 0,
    'annule' => 0,
    'chiffre_affaires' => 0
];

foreach ($orders as $order) {
    $stats['total']++;
    $stats[$order['statut']]++;
    $stats['chiffre_affaires'] += $order['total'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes - Fanilo Art Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a3d5d;
            --secondary: #2a7abf;
            --accent: #eef6fb;
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
        
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left-color: var(--secondary);
        }
        
        .stats-card {
            border: none;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .stats-card.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stats-card.en_attente { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stats-card.confirme { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stats-card.expedie { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stats-card.livre { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
        .order-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .status-en_attente { background: #fff3cd; color: #856404; }
        .status-confirme { background: #d1ecf1; color: #0c5460; }
        .status-expedie { background: #d4edda; color: #155724; }
        .status-livre { background: #e2e3e5; color: #383d41; }
        .status-annule { background: #f8d7da; color: #721c24; }
        
        .product-badge {
            background: var(--accent);
            border: 1px solid var(--secondary);
            border-radius: 8px;
            padding: 8px 12px;
            margin: 2px;
            font-size: 0.8rem;
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
                        <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                            <i class="fas fa-palette fa-lg"></i>
                        </div>
                        <h5 class="mb-1">Fanilo Art Studio</h5>
                        <small class="text-light opacity-75">Administration</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Tableau de bord</a></li>
                        <li class="nav-item"><a class="nav-link" href="products.php"><i class="fas fa-palette me-2"></i>Produits</a></li>
                        <li class="nav-item"><a class="nav-link" href="categories.php"><i class="fas fa-tags me-2"></i>Catégories</a></li>
                        <li class="nav-item"><a class="nav-link" href="customers.php"><i class="fas fa-users me-2"></i>Clients</a></li>
                        <li class="nav-item"><a class="nav-link active" href="orders.php"><i class="fas fa-shopping-cart me-2"></i>Commandes</a></li>
                        <li class="nav-item mt-4"><a class="nav-link text-warning" href="../boutique.php"><i class="fas fa-store me-2"></i>Voir la boutique</a></li>
                        <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                    </ul>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div class="col-md-9 col-lg-10 bg-light">
                <div class="p-4">
                    <!-- HEADER -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 text-primary mb-1">
                                <i class="fas fa-shopping-cart me-2"></i>Gestion des Commandes
                            </h1>
                            <p class="text-muted mb-0">Suivez et gérez toutes les commandes de votre boutique</p>
                        </div>
                    </div>

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
                        <div class="col-md-2 col-6">
                            <div class="stats-card total">
                                <div class="text-center">
                                    <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                                    <small>Total</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="stats-card en_attente">
                                <div class="text-center">
                                    <h3 class="mb-0"><?php echo $stats['en_attente']; ?></h3>
                                    <small>En attente</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="stats-card confirme">
                                <div class="text-center">
                                    <h3 class="mb-0"><?php echo $stats['confirme']; ?></h3>
                                    <small>Confirmées</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="stats-card expedie">
                                <div class="text-center">
                                    <h3 class="mb-0"><?php echo $stats['expedie']; ?></h3>
                                    <small>Expédiées</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="stats-card livre">
                                <div class="text-center">
                                    <h3 class="mb-0"><?php echo $stats['livre']; ?></h3>
                                    <small>Livrées</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="stats-card total" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                                <div class="text-center">
                                    <h3 class="mb-0"><?php echo number_format($stats['chiffre_affaires'], 0, ',', ' '); ?> MGA</h3>
                                    <small>Chiffre d'affaires</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LISTE DES COMMANDES -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>Liste des Commandes
                                <span class="badge bg-primary ms-2"><?php echo count($orders); ?></span>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($orders)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Aucune commande enregistrée</h4>
                                    <p class="text-muted">Les commandes apparaîtront ici lorsqu'elles seront passées par les clients.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($orders as $order): ?>
                                <div class="order-card m-3">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-3">
                                                <h6 class="mb-1">Commande #<?php echo $order['id']; ?></h6>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?>
                                                </small>
                                                <div class="mt-2">
                                                    <span class="status-badge status-<?php echo $order['statut']; ?>">
                                                        <?php 
                                                        $status_labels = [
                                                            'en_attente' => '⏳ En attente',
                                                            'confirme' => '✅ Confirmée', 
                                                            'expedie' => '🚚 Expédiée',
                                                            'livre' => '📦 Livrée',
                                                            'annule' => '❌ Annulée'
                                                        ];
                                                        echo $status_labels[$order['statut']];
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <strong><?php echo htmlspecialchars($order['client_nom']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['client_email']); ?></small>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['telephone']); ?></small>
                                            </div>
                                            
                                            <div class="col-md-2">
                                                <small class="text-muted">Total</small>
                                                <br>
                                                <strong class="text-primary"><?php echo number_format($order['total'], 0, ',', ' '); ?> MGA</strong>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="d-flex gap-2">
                                                    <!-- MODIFIER STATUT -->
                                                    <form method="POST" class="flex-grow-1">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <select name="new_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                            <option value="en_attente" <?php echo $order['statut'] == 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                                            <option value="confirme" <?php echo $order['statut'] == 'confirme' ? 'selected' : ''; ?>>Confirmée</option>
                                                            <option value="expedie" <?php echo $order['statut'] == 'expedie' ? 'selected' : ''; ?>>Expédiée</option>
                                                            <option value="livre" <?php echo $order['statut'] == 'livre' ? 'selected' : ''; ?>>Livrée</option>
                                                            <option value="annule" <?php echo $order['statut'] == 'annule' ? 'selected' : ''; ?>>Annulée</option>
                                                        </select>
                                                    </form>
                                                    
                                                    <!-- BOUTON DÉTAILS -->
                                                    <button class="btn btn-outline-primary btn-sm" 
                                                            data-bs-toggle="collapse" 
                                                            data-bs-target="#orderDetails<?php echo $order['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- DÉTAILS DE LA COMMANDE -->
                                        <div class="collapse mt-3" id="orderDetails<?php echo $order['id']; ?>">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6>Produits commandés :</h6>
                                                    <?php 
                                                    $produits = json_decode($order['produits'], true);
                                                    if ($produits): 
                                                        foreach ($produits as $produit): 
                                                    ?>
                                                        <div class="product-badge">
                                                            <strong><?php echo htmlspecialchars($produit['nom']); ?></strong>
                                                            <br>
                                                            <small>Artiste: <?php echo htmlspecialchars($produit['artiste']); ?></small>
                                                            <br>
                                                            <small>Quantité: <?php echo $produit['quantite']; ?> x <?php echo number_format($produit['prix'], 0, ',', ' '); ?> MGA</small>
                                                        </div>
                                                    <?php 
                                                        endforeach; 
                                                    endif; 
                                                    ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Adresse de livraison :</h6>
                                                    <p class="small"><?php echo nl2br(htmlspecialchars($order['adresse'])); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>