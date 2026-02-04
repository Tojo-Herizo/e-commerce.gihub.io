<?php
session_start();
require 'config.php';

// Vérifier si le panier est vide
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$success = $error = '';

// Traitement du formulaire de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupérer les données du formulaire
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
        $address = mysqli_real_escape_string($conn, trim($_POST['address']));
        $notes = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));
        
        // Validation
        if (empty($name) || empty($email) || empty($phone) || empty($address)) {
            throw new Exception("Tous les champs obligatoires doivent être remplis");
        }
        
        // Calculer le total
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        // Commencer une transaction
        mysqli_begin_transaction($conn);
        
        try {
            // 1. Créer la commande
            $order_sql = "INSERT INTO orders (customer_name, customer_email, customer_phone, customer_address, notes, total_amount, status) 
                         VALUES (?, ?, ?, ?, ?, ?, 'pending')";
            $order_stmt = mysqli_prepare($conn, $order_sql);
            mysqli_stmt_bind_param($order_stmt, 'sssssd', $name, $email, $phone, $address, $notes, $total);
            
            if (!mysqli_stmt_execute($order_stmt)) {
                throw new Exception("Erreur lors de la création de la commande");
            }
            
            $order_id = mysqli_insert_id($conn);
            
            // 2. Ajouter les articles de la commande
            foreach ($_SESSION['cart'] as $item) {
                $item_sql = "INSERT INTO order_items (order_id, product_id, product_name, artist, quantity, unit_price) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                $item_stmt = mysqli_prepare($conn, $item_sql);
                mysqli_stmt_bind_param($item_stmt, 'iisisd', $order_id, $item['id'], $item['name'], $item['artist'], $item['quantity'], $item['price']);
                
                if (!mysqli_stmt_execute($item_stmt)) {
                    throw new Exception("Erreur lors de l'ajout des articles");
                }
                
                // 3. Mettre à jour le stock (optionnel)
                $update_sql = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, 'ii', $item['quantity'], $item['id']);
                mysqli_stmt_execute($update_stmt);
            }
            
            // Valider la transaction
            mysqli_commit($conn);
            
            // Vider le panier
            $_SESSION['cart'] = [];
            
            // Rediriger vers la page de confirmation
            header('Location: order_success.php?id=' . $order_id);
            exit;
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            mysqli_rollback($conn);
            throw $e;
        }
        
    } catch (Exception $e) {
        $error = "❌ " . $e->getMessage();
    }
}

// Calculer le total pour l'affichage
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - Fanilo Art Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a3d5d;
            --secondary: #2a7abf;
        }
        
        .navbar { background-color: var(--primary) !important; }
        .checkout-card { border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.1); }
        .summary-item { border-bottom: 1px solid #eee; padding: 15px 0; }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-palette me-2"></i>Fanilo Art Studio
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 class="text-primary mb-5 text-center">
                    <i class="fas fa-credit-card me-2"></i>Finaliser votre commande
                </h1>

                <div class="row">
                    <!-- Formulaire de paiement -->
                    <div class="col-lg-7 mb-4">
                        <div class="card checkout-card">
                            <div class="card-header bg-primary text-white">
                                <h4 class="mb-0">
                                    <i class="fas fa-user me-2"></i>Informations personnelles
                                </h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="checkoutForm">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nom complet *</label>
                                            <input type="text" name="name" class="form-control" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email *</label>
                                            <input type="email" name="email" class="form-control" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Téléphone *</label>
                                        <input type="tel" name="phone" class="form-control" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Adresse de livraison *</label>
                                        <textarea name="address" class="form-control" rows="3" placeholder="Rue, ville, code postal..." required></textarea>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Notes (optionnel)</label>
                                        <textarea name="notes" class="form-control" rows="2" placeholder="Instructions spéciales..."></textarea>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <small>
                                            <i class="fas fa-info-circle me-2"></i>
                                            Vous recevrez un email de confirmation dès que votre commande sera traitée.
                                        </small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-check-circle me-2"></i>Confirmer la commande
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Récapitulatif de la commande -->
                    <div class="col-lg-5">
                        <div class="card checkout-card">
                            <div class="card-header bg-success text-white">
                                <h4 class="mb-0">
                                    <i class="fas fa-receipt me-2"></i>Votre commande
                                </h4>
                            </div>
                            <div class="card-body">
                                <?php foreach($_SESSION['cart'] as $item): ?>
                                <div class="summary-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <small class="text-muted">Quantité: <?php echo $item['quantity']; ?></small>
                                        </div>
                                        <strong><?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> MGA</strong>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="mt-3 pt-3 border-top">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Sous-total:</span>
                                        <strong><?php echo number_format($total, 0, ',', ' '); ?> MGA</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Livraison:</span>
                                        <strong class="text-success">Gratuite</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                                        <span class="h5">Total:</span>
                                        <strong class="h5 text-primary"><?php echo number_format($total, 0, ',', ' '); ?> MGA</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="cart.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Retour au panier
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation basique du formulaire
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const phone = document.querySelector('input[name="phone"]');
            if (!phone.value.match(/^[0-9+\-\s()]{8,}$/)) {
                e.preventDefault();
                alert('Veuillez entrer un numéro de téléphone valide');
                phone.focus();
                return false;
            }
        });
    </script>
</body>
</html>