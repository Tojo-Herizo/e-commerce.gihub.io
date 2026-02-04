<?php
session_start();

// Initialiser le panier
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Supprimer un article
if (isset($_GET['remove'])) {
    $index = intval($_GET['remove']);
    if (isset($_SESSION['cart'][$index])) {
        array_splice($_SESSION['cart'], $index, 1);
    }
    header('Location: cart.php');
    exit;
}

// Mettre à jour les quantités
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $index => $quantity) {
        if (isset($_SESSION['cart'][$index])) {
            $new_quantity = max(1, intval($quantity));
            $_SESSION['cart'][$index]['quantity'] = $new_quantity;
        }
    }
    header('Location: cart.php');
    exit;
}

// Vider le panier
if (isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

// Calculer le total
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
    <title>Panier - Fanilo Art Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a3d5d;
            --secondary: #2a7abf;
            --accent: #eef6fb;
        }
        
        .navbar { background-color: var(--primary) !important; }
        .btn-primary { background-color: var(--primary); border-color: var(--primary); }
        
        .cart-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .summary-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-palette me-2"></i>Fanilo Art Studio
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php">Boutique</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">À propos</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <li class="nav-item">
                        <a class="nav-link active" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Panier
                            <?php if(!empty($_SESSION['cart'])): ?>
                                <span class="badge bg-light text-primary ms-1"><?php echo count($_SESSION['cart']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="admin/login.php" class="btn btn-light me-2">
                        <i class="fas fa-cog"></i> Admin
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h1 class="text-primary mb-4">Votre Panier</h1>

        <?php if(empty($_SESSION['cart'])): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                <h3 class="text-muted">Votre panier est vide</h3>
                <p class="text-muted mb-4">Découvrez nos œuvres d'art et ajoutez-les à votre panier.</p>
                <a href="index.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-palette me-2"></i>Découvrir la boutique
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Articles du panier -->
                <div class="col-md-8">
                    <form method="POST" action="cart.php">
                        <?php foreach($_SESSION['cart'] as $index => $item): ?>
                            <div class="cart-item">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="text-muted mb-0"><?php echo number_format($item['price'], 0, ',', ' '); ?> MGA</p>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group">
                                            <input type="number" name="quantities[<?php echo $index; ?>]" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <strong><?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> MGA</strong>
                                    </div>
                                    <div class="col-md-1">
                                        <a href="cart.php?remove=<?php echo $index; ?>" class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" name="update_cart" class="btn btn-outline-primary">
                                <i class="fas fa-sync me-2"></i>Actualiser le panier
                            </button>
                            <button type="submit" name="clear_cart" class="btn btn-outline-danger" onclick="return confirm('Vider tout le panier ?')">
                                <i class="fas fa-trash me-2"></i>Vider le panier
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Récapitulatif -->
                <div class="col-md-4">
                    <div class="summary-card">
                        <h4 class="mb-4">Récapitulatif</h4>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Sous-total:</span>
                            <strong><?php echo number_format($total, 0, ',', ' '); ?> MGA</strong>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Livraison:</span>
                            <strong>Gratuite</strong>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <span class="h5">Total:</span>
                            <span class="h5 text-primary"><?php echo number_format($total, 0, ',', ' '); ?> MGA</span>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="chekout.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-credit-card me-2"></i>Procéder au paiement
                            </a>
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Continuer mes achats
                            </a>
                        </div>
                        
                        <div class="mt-4">
                            <p class="small text-muted">
                                <i class="fas fa-shield-alt me-2"></i>
                                Paiement sécurisé - Livraison gratuite à Antananarivo
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="text-center">
                <p>&copy; 2025 Fanilo Art Studio. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>