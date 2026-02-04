<?php
session_start();
require 'config.php';

if (!isset($_GET['id'])) {
    header('Location: boutique.php');
    exit;
}

$order_id = intval($_GET['id']);

// Récupérer les détails de la commande
$order_sql = "SELECT * FROM orders WHERE id = ?";
$order_stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($order_stmt, 'i', $order_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    header('Location: boutique.php');
    exit;
}

// Récupérer les articles de la commande
$items_sql = "SELECT * FROM order_items WHERE order_id = ?";
$items_stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($items_stmt, 'i', $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);
$items = mysqli_fetch_all($items_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation - Fanilo Art Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .confirmation-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .success-animation {
            animation: bounce 1s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-10px);}
            60% {transform: translateY(-5px);}
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card confirmation-card">
                    <div class="card-body text-center p-5">
                        <!-- ICON SUCCESS -->
                        <div class="success-animation mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                        </div>
                        
                        <h1 class="text-success mb-3">Commande Confirmée !</h1>
                        <p class="lead mb-4">Merci pour votre achat chez Fanilo Art Studio</p>
                        
                        <!-- RÉSUMÉ COMMANDE -->
                        <div class="row text-start mb-4">
                            <div class="col-md-6">
                                <h5>Détails de la commande</h5>
                                <p><strong>N° Commande :</strong> #<?php echo $order['id']; ?></p>
                                <p><strong>Date :</strong> <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?></p>
                                <p><strong>Total :</strong> <?php echo number_format($order['total_amount'], 0, ',', ' '); ?> MGA</p>
                                <p><strong>Statut :</strong> <span class="badge bg-warning">En traitement</span></p>
                            </div>
                            <div class="col-md-6">
                                <h5>Livraison</h5>
                                <p><strong>À :</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                <p><strong>Email :</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                                <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                <p><strong>Adresse :</strong> <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></p>
                            </div>
                        </div>
                        
                        <!-- ARTICLES COMMANDÉS -->
                        <div class="text-start mb-4">
                            <h5>Œuvres commandées</h5>
                            <?php foreach($items as $item): ?>
                                <div class="d-flex justify-content-between border-bottom py-2">
                                    <span><?php echo htmlspecialchars($item['product_name']); ?> x <?php echo $item['quantity']; ?></span>
                                    <span><?php echo number_format($item['unit_price'] * $item['quantity'], 0, ',', ' '); ?> MGA</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- ACTIONS -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="boutique.php" class="btn btn-primary me-md-2">
                                <i class="fas fa-palette me-2"></i>Continuer mes achats
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-2"></i>Retour à l'accueil
                            </a>
                        </div>
                        
                        <div class="mt-4 text-muted">
                            <small>
                                <i class="fas fa-envelope me-1"></i>
                                Un email de confirmation vous a été envoyé à <?php echo htmlspecialchars($order['customer_email']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>