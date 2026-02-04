<?php
session_start();
require 'config.php';

// Récupérer l'ID du produit depuis l'URL
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Récupérer le produit depuis la base de données
$sql = "SELECT p.*, c.nom as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ? AND p.status = 'active'";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

// Si produit non trouvé, rediriger vers l'accueil
if (!$product) {
    header('Location: index.php');
    exit;
}

// Données simulées complémentaires (à remplacer par vos vraies données)
$product_details = [
    'full_description' => $product['description'] . ' Cette œuvre unique a été créée avec passion et dévouement. Chaque détail a été soigneusement travaillé pour créer une pièce exceptionnelle qui embellira votre espace.',
    'artist' => 'Artiste Fanilo Studio',
    'dimensions' => '60 x 80 cm',
    'materials' => 'Matériaux premium',
    'year' => 2024
];

// Fusionner les données
$product = array_merge($product, $product_details);

// Ajouter au panier
if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $quantity = intval($_POST['quantity'] ?? 1);
    $cart_item = [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'quantity' => $quantity,
        'image' => $product['image'] ?? 'default.jpg'
    ];
    
    $_SESSION['cart'][] = $cart_item;
    $added_to_cart = true;
}

// FONCTION POUR VÉRIFIER ET AFFICHER L'IMAGE
function displayProductImage($product, $upload_dir = 'uploads/') {
    $image_name = $product['image'] ?? '';
    
    if (!$image_name) {
        return false;
    }
    
    // Essayer différents chemins
    $possible_paths = [
        $upload_dir . $image_name,
        '../' . $upload_dir . $image_name,
        '../../' . $upload_dir . $image_name,
        './' . $upload_dir . $image_name
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return false;
}

$image_path = displayProductImage($product);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Fanilo Art Studio</title>
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
        
        .product-gallery img {
            border-radius: 10px;
            cursor: pointer;
        }
        
        .product-info {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .price-tag {
            font-size: 2rem;
            color: var(--primary);
            font-weight: bold;
        }
        
        .breadcrumb {
            background: transparent;
        }
        
        .image-placeholder {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
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
                        <a class="nav-link" href="cart.php">
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
        <?php if(isset($added_to_cart)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                Produit ajouté au panier avec succès !
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="index.php">Boutique</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Gallery CORRIGÉE -->
            <div class="col-md-6">
                <div class="product-gallery">
                    <div class="main-image mb-3">
                        <?php if($image_path): ?>
                            <img src="<?php echo $image_path; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="img-fluid rounded w-100" 
                                 style="height: 400px; object-fit: cover;">
                        <?php else: ?>
                            <div class="image-placeholder w-100" style="height: 400px;">
                                <div class="text-center">
                                    <i class="fas fa-image fa-5x mb-3"></i>
                                    <p class="mb-1">Image non disponible</p>
                                    <small class="text-muted">ID: <?php echo $product['id']; ?></small>
                                    <br>
                                    <small class="text-muted">Fichier: <?php echo $product['image'] ?? 'Aucun'; ?></small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-md-6">
                <div class="product-info">
                    <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    <h1 class="h2"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <p class="text-muted">par <?php echo htmlspecialchars($product['artist']); ?></p>
                    
                    <div class="price-tag mb-3">
                        <?php echo number_format($product['price'], 0, ',', ' '); ?> MGA
                    </div>
                    
                    <p class="lead"><?php echo htmlspecialchars($product['description']); ?></p>
                    
                    <form method="POST" class="mb-4">
                        <div class="row align-items-center mb-3">
                            <div class="col-auto">
                                <label class="form-label fw-bold">Quantité:</label>
                            </div>
                            <div class="col-auto">
                                <select name="quantity" class="form-select" style="width: 100px;">
                                    <?php 
                                    $max_quantity = min(5, $product['stock'] ?? 5);
                                    for($i = 1; $i <= $max_quantity; $i++): 
                                    ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col">
                                <span class="text-muted"><?php echo $product['stock'] ?? 5; ?> disponible(s)</span>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-cart me-2"></i>Ajouter au panier
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-heart me-2"></i>Ajouter aux favoris
                            </button>
                        </div>
                    </form>

                    <!-- Product Details -->
                    <div class="mt-4">
                        <h5 class="mb-3">Détails de l'œuvre</h5>
                        <div class="row">
                            <div class="col-6">
                                <strong>Artiste:</strong><br>
                                <?php echo htmlspecialchars($product['artist']); ?>
                            </div>
                            <div class="col-6">
                                <strong>Dimensions:</strong><br>
                                <?php echo htmlspecialchars($product['dimensions']); ?>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <strong>Matériaux:</strong><br>
                                <?php echo htmlspecialchars($product['materials']); ?>
                            </div>
                            <div class="col-6">
                                <strong>Année:</strong><br>
                                <?php echo htmlspecialchars($product['year']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description complète -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="product-info">
                    <h4>Description complète</h4>
                    <p><?php echo htmlspecialchars($product['full_description']); ?></p>
                    
                    <h5 class="mt-4">Livraison et retours</h5>
                    <ul>
                        <li>Livraison gratuite à Antananarivo</li>
                        <li>Livraison sous 5-7 jours dans toute l'île</li>
                        <li>Emballage sécurisé et assuré</li>
                        <li>Retours acceptés sous 14 jours</li>
                    </ul>
                </div>
            </div>
        </div>

       
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>Fanilo Art Studio</h5>
                    <p>Créateur d'art inspiré par la nature malgache.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Liens rapides</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-light">Accueil</a></li>
                        <li><a href="index.php" class="text-light">Boutique</a></li>
                        <li><a href="about.php" class="text-light">À propos</a></li>
                        <li><a href="contact.php" class="text-light">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i> Antananarivo, Madagascar</li>
                        <li><i class="fas fa-phone me-2"></i> +261 34 00 023 50</li>
                        <li><i class="fas fa-envelope me-2"></i> contact@faniloartstudio.mg</li>
                    </ul>
                </div>
            </div>
            <div class="text-center">
                <p>&copy; 2025 Fanilo Art Studio. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>