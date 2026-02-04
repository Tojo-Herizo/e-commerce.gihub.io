<?php
require '../config.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Créer la table commandes si elle n'existe pas
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS commandes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT,
        produits TEXT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        statut ENUM('en_attente', 'confirme', 'expedie', 'livre', 'annule') DEFAULT 'en_attente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id)
    )
");

// Récupérer les commandes avec les infos clients
$orders_result = mysqli_query($conn, "
    SELECT c.*, cl.nom as client_nom, cl.email as client_email 
    FROM commandes c 
    LEFT JOIN clients cl ON c.client_id = cl.id 
    ORDER BY c.created_at DESC
");
$orders = mysqli_fetch_all($orders_result, MYSQLI_ASSOC);

// Changer le statut d'une commande
if ($_POST['action'] ?? '' === 'update_status') {
    $order_id = intval($_POST['order_id']);
    $new_status = secure_data($_POST['new_status']);
    mysqli_query($conn, "UPDATE commandes SET statut = '$new_status' WHERE id = $order_id");
    header('Location: orders.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 bg-dark text-light min-vh-100">
                <div class="p-3">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-palette me-2"></i>Fanilo Art
                    </h4>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link text-light" href="dashboard.php">Tableau de bord</a></li>
                        <li class="nav-item"><a class="nav-link text-light" href="products.php">Produits</a></li>
                        <li class="nav-item"><a class="nav-link text-light" href="categories.php">Catégories</a></li>
                        <li class="nav-item"><a class="nav-link text-light" href="customers.php">Clients</a></li>
                        <li class="nav-item"><a class="nav-link text-light active" href="orders.php">Commandes</a></li>
                        <li class="nav-item mt-4"><a class="nav-link text-warning" href="../index.php">Voir le site</a></li>
                        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Déconnexion</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="p-4">
                    <h1>Gestion des Commandes</h1>

                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Commandes (<?php echo count($orders); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($orders)): ?>
                                <p class="text-muted text-center py-4">Aucune commande pour le moment</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Client</th>
                                                <th>Total</th>
                                                <th>Statut</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($order['client_nom']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($order['client_email']); ?></small>
                                                </td>
                                                <td><?php echo number_format($order['total'], 0, ',', ' '); ?> MGA</td>
                                                <td>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <select name="new_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                            <option value="en_attente" <?php echo $order['statut'] == 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                                            <option value="confirme" <?php echo $order['statut'] == 'confirme' ? 'selected' : ''; ?>>Confirmé</option>
                                                            <option value="expedie" <?php echo $order['statut'] == 'expedie' ? 'selected' : ''; ?>>Expédié</option>
                                                            <option value="livre" <?php echo $order['statut'] == 'livre' ? 'selected' : ''; ?>>Livré</option>
                                                            <option value="annule" <?php echo $order['statut'] == 'annule' ? 'selected' : ''; ?>>Annulé</option>
                                                        </select>
                                                    </form>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>