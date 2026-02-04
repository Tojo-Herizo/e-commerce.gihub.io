<?php
// Désactiver temporairement les warnings pour debug
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require '../config.php';

// Initialiser toutes les variables
$product = null;
$edit_product = null;
$products = [];
$categories = [];
$success = '';
$error = '';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// CRÉER LE DOSSIER UPLOADS S'IL N'EXISTE PAS
$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// ==================== AJOUTER PRODUIT AVEC UPLOAD ====================
if (($_POST['action'] ?? '') === 'add_product') {
    $name = secure_data($_POST['name'] ?? '');
    $description = secure_data($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $artist = secure_data($_POST['artist'] ?? '');
    $materials = secure_data($_POST['materials'] ?? '');
    $dimensions = secure_data($_POST['dimensions'] ?? '');
    
    // GESTION UPLOAD IMAGE
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_name = $_FILES['image']['name'];
        $file_size = $_FILES['image']['size'];
        $file_type = $_FILES['image']['type'];
        
        // VALIDATION FICHIER
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file_type, $allowed_types)) {
            $error = "❌ Format d'image non supporté. Utilisez JPG, PNG, GIF ou WebP.";
        } elseif ($file_size > $max_size) {
            $error = "❌ L'image est trop volumineuse (max 5MB).";
        } else {
            // GÉNÉRER UN NOM UNIQUE
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $image_name = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $image_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // COMPRESSER L'IMAGE (optionnel)
                compressImage($upload_path, $upload_path, 80);
            } else {
                $error = "❌ Erreur lors de l'upload de l'image.";
            }
        }
    }
    
    if (!$error) {
        $sql = "INSERT INTO products (name, description, price, category_id, stock, artist, materials, dimensions, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssdiissss', $name, $description, $price, $category_id, $stock, $artist, $materials, $dimensions, $image_name);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "✅ Produit ajouté avec succès !";
        } else {
            $error = "❌ Erreur lors de l'ajout : " . mysqli_error($conn);
        }
    }
}

// ==================== MODIFIER PRODUIT AVEC UPLOAD ====================
if (($_POST['action'] ?? '') === 'edit_product') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $name = secure_data($_POST['name'] ?? '');
    $description = secure_data($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $artist = secure_data($_POST['artist'] ?? '');
    $materials = secure_data($_POST['materials'] ?? '');
    $dimensions = secure_data($_POST['dimensions'] ?? '');
    
    // RÉCUPÉRER L'ANCIENNE IMAGE (CORRIGÉ)
    $old_image = '';
    $old_image_result = mysqli_query($conn, "SELECT image FROM products WHERE id = $product_id");
    if ($old_image_result && mysqli_num_rows($old_image_result) > 0) {
        $old_image_data = mysqli_fetch_assoc($old_image_result);
        $old_image = $old_image_data['image'] ?? '';
    }
    
    $new_image_name = $old_image;
    
    // GESTION NOUVELLE IMAGE
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_name = $_FILES['image']['name'];
        $file_size = $_FILES['image']['size'];
        $file_type = $_FILES['image']['type'];
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024;
        
        if (!in_array($file_type, $allowed_types)) {
            $error = "❌ Format d'image non supporté.";
        } elseif ($file_size > $max_size) {
            $error = "❌ L'image est trop volumineuse (max 5MB).";
        } else {
            // SUPPRIMER L'ANCIENNE IMAGE
            if ($old_image && file_exists($upload_dir . $old_image)) {
                unlink($upload_dir . $old_image);
            }
            
            // UPLOAD NOUVELLE IMAGE
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_image_name = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_image_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                compressImage($upload_path, $upload_path, 80);
            } else {
                $error = "❌ Erreur lors de l'upload de la nouvelle image.";
            }
        }
    }
    
    if (!$error) {
        $sql = "UPDATE products SET 
                name = ?, description = ?, price = ?, category_id = ?, 
                stock = ?, artist = ?, materials = ?, dimensions = ?, image = ? 
                WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssdiissssi', $name, $description, $price, $category_id, $stock, $artist, $materials, $dimensions, $new_image_name, $product_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "✅ Produit modifié avec succès !";
        } else {
            $error = "❌ Erreur lors de la modification : " . mysqli_error($conn);
        }
    }
}

// ==================== SUPPRIMER PRODUIT + IMAGE ====================
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    // RÉCUPÉRER L'IMAGE AVANT SUPPRESSION
    $image_data = ['image' => ''];
    $image_result = mysqli_query($conn, "SELECT image FROM products WHERE id = $product_id");
    if ($image_result && mysqli_num_rows($image_result) > 0) {
        $image_data = mysqli_fetch_assoc($image_result);
    }
    
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // SUPPRIMER LE FICHIER IMAGE
        if (($image_data['image'] ?? '') && file_exists($upload_dir . $image_data['image'])) {
            unlink($upload_dir . $image_data['image']);
        }
        $success = "✅ Produit supprimé avec succès !";
    } else {
        $error = "❌ Erreur lors de la suppression : " . mysqli_error($conn);
    }
}

// ==================== FONCTION COMPRESSION IMAGE ====================
function compressImage($source, $destination, $quality) {
    // Vérifier si GD est disponible
    if (!function_exists('imagecreatefromjpeg')) {
        // Si GD n'est pas disponible, copier simplement l'image
        return copy($source, $destination);
    }
    
    $info = getimagesize($source);
    if (!$info) {
        return copy($source, $destination);
    }
    
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            if ($image) {
                $result = imagejpeg($image, $destination, $quality);
                imagedestroy($image);
                return $result;
            }
            break;
            
        case 'image/png':
            $image = imagecreatefrompng($source);
            if ($image) {
                imagealphablending($image, false);
                imagesavealpha($image, true);
                $result = imagepng($image, $destination, 9);
                imagedestroy($image);
                return $result;
            }
            break;
            
        case 'image/gif':
            $image = imagecreatefromgif($source);
            if ($image) {
                $result = imagegif($image, $destination);
                imagedestroy($image);
                return $result;
            }
            break;
            
        case 'image/webp':
            $image = imagecreatefromwebp($source);
            if ($image) {
                $result = imagewebp($image, $destination, $quality);
                imagedestroy($image);
                return $result;
            }
            break;
    }
    
    // Fallback: copier l'image originale
    return copy($source, $destination);
}

// ==================== RÉCUPÉRATION DONNÉES ====================
$products_result = mysqli_query($conn, "
    SELECT p.*, c.nom as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC
");
if ($products_result) {
    $products = mysqli_fetch_all($products_result, MYSQLI_ASSOC);
} else {
    $products = [];
}

$categories_result = mysqli_query($conn, "SELECT * FROM categories WHERE status = 'active'");
if ($categories_result) {
    $categories = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);
} else {
    $categories = [];
}

// Récupération du produit à éditer
if (($_GET['action'] ?? '') === 'edit' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $edit_result = mysqli_query($conn, "SELECT * FROM products WHERE id = $product_id");
    if ($edit_result && mysqli_num_rows($edit_result) > 0) {
        $edit_product = mysqli_fetch_assoc($edit_result);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Produits - Fanilo Art Studio</title>
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
        
        .product-image-preview {
            width: 100%;
            height: 200px;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .product-image-preview:hover {
            border-color: var(--primary);
            background: #e9ecef;
        }
        
        .product-image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .product-image-preview i {
            font-size: 3rem;
            color: #6c757d;
        }
        
        .image-upload-btn {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .image-upload-btn input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        
        .product-card-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        
        .upload-zone {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .upload-zone.dragover {
            border-color: #28a745;
            background: #e8f5e8;
        }
        
        .file-info {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 10px;
        }
        
        .image-preview-container {
            position: relative;
            display: inline-block;
        }
        
        .image-remove-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            font-size: 0.8rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
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
                        <li class="nav-item"><a class="nav-link active" href="products.php"><i class="fas fa-palette me-2"></i>Produits</a></li>
                        <li class="nav-item"><a class="nav-link" href="categories.php"><i class="fas fa-tags me-2"></i>Catégories</a></li>
                        <li class="nav-item"><a class="nav-link" href="customers.php"><i class="fas fa-users me-2"></i>Clients</a></li>
                        <li class="nav-item"><a class="nav-link" href="orders.php"><i class="fas fa-shopping-cart me-2"></i>Commandes</a></li>
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
                            <h1 class="h3 text-primary mb-1"><i class="fas fa-palette me-2"></i>Gestion des Produits</h1>
                            <p class="text-muted mb-0">Gérez votre catalogue d'œuvres d'art</p>
                        </div>
                        <button class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus me-2"></i>Nouveau Produit
                        </button>
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

                    <!-- TABLEAU DES PRODUITS -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>Liste des Produits
                                <span class="badge bg-primary ms-2"><?php echo count($products); ?></span>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($products)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-palette fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Aucun produit enregistré</h4>
                                    <p class="text-muted mb-4">Commencez par ajouter votre première œuvre.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                        <i class="fas fa-plus me-2"></i>Ajouter un produit
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Image</th>
                                                <th>Produit</th>
                                                <th>Catégorie</th>
                                                <th>Prix</th>
                                                <th>Stock</th>
                                                <th>Artiste</th>
                                                <th class="text-end pe-4">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($products as $product): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <?php if(($product['image'] ?? '') && file_exists($upload_dir . $product['image'])): ?>
                                                        <img src="../uploads/<?php echo $product['image']; ?>" 
                                                             alt="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" 
                                                             class="product-card-image"
                                                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjZjhmOWZhIi8+CjxwYXRoIGQ9Ik0zMCAzN0MzMy4zMTM3IDM3IDM2IDM0LjMxMzcgMzYgMzFDMzYgMjcuNjg2MyAzMy4zMTM3IDI1IDMwIDI1QzI2LjY4NjMgMjUgMjQgMjcuNjg2MyAyNCAzMUMyNCAzNC4zMTM3IDI2LjY4NjMgMzcgMzAgMzdaIiBmaWxsPSIjNmM3NTc4Ii8+CjxwYXRoIGQ9Ik00MiAyMEM0MiAxOC44OTU0IDQxLjEwNDYgMTggNDAgMThIMjBDMTguODk1NCAxOCAxOCAxOC44OTU0IDE4IDIwVjQyQzE4IDQzLjEwNDYgMTguODk1NCA0NCAyMCA0NEg0MEM0MS4xMDQ2IDQ0IDQyIDQzLjEwNDYgNDIgNDJWMjBaIiBmaWxsPSIjNmM3NTc4Ii8+Cjwvc3ZnPgo='">
                                                    <?php else: ?>
                                                        <div class="product-card-image bg-light d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($product['name'] ?? ''); ?></strong>
                                                    <br>
                                                    <small class="text-muted">ID: #<?php echo $product['id'] ?? ''; ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($product['category_name'] ?? ''); ?></span>
                                                </td>
                                                <td>
                                                    <strong class="text-primary"><?php echo number_format($product['price'] ?? 0, 0, ',', ' '); ?> MGA</strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo ($product['stock'] ?? 0) > 0 ? 'success' : 'danger'; ?>">
                                                        <?php echo $product['stock'] ?? 0; ?> unités
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($product['artist'] ?? ''); ?></td>
                                                <td class="text-end pe-4">
                                                    <div class="btn-group btn-group-sm">
                                                        <!-- MODIFIER -->
                                                        <button class="btn btn-outline-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editProductModal<?php echo $product['id'] ?? ''; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        
                                                        <!-- SUPPRIMER -->
                                                        <a href="products.php?action=delete&id=<?php echo $product['id'] ?? ''; ?>" 
                                                           class="btn btn-outline-danger" 
                                                           onclick="return confirm('Supprimer le produit <?php echo addslashes($product['name'] ?? ''); ?> ?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
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

    <!-- MODAL AJOUT PRODUIT -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nouveau Produit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_product">
                    <div class="modal-body">
                        <div class="row">
                            <!-- UPLOAD IMAGE -->
                            <div class="col-md-5 mb-4">
                                <label class="form-label fw-bold">Image du produit</label>
                                <div class="upload-zone" id="uploadZone">
                                    <div class="product-image-preview" id="imagePreview">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <input type="file" name="image" id="imageInput" accept="image/*" class="d-none">
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm image-upload-btn" onclick="document.getElementById('imageInput').click()">
                                            <i class="fas fa-camera me-2"></i>Choisir une image
                                        </button>
                                    </div>
                                    <div class="file-info text-muted small mt-2">
                                        Formats: JPG, PNG, GIF, WebP<br>Max: 5MB
                                    </div>
                                </div>
                            </div>

                            <!-- INFORMATIONS PRODUIT -->
                            <div class="col-md-7">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Nom du produit *</label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Catégorie *</label>
                                        <select name="category_id" class="form-select" required>
                                            <option value="">Choisir une catégorie</option>
                                            <?php foreach($categories as $cat): ?>
                                                <option value="<?php echo $cat['id'] ?? ''; ?>"><?php echo htmlspecialchars($cat['nom'] ?? ''); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Prix (MGA) *</label>
                                        <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Stock *</label>
                                        <input type="number" name="stock" class="form-control" min="0" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Artiste *</label>
                                    <input type="text" name="artist" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Description *</label>
                                <textarea name="description" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Matériaux</label>
                                <input type="text" name="materials" class="form-control" placeholder="ex: Peinture à l'huile, toile">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dimensions</label>
                                <input type="text" name="dimensions" class="form-control" placeholder="ex: 80x60 cm">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer le produit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODALS MODIFICATION PRODUITS -->
    <?php foreach($products as $product): ?>
    <div class="modal fade" id="editProductModal<?php echo $product['id'] ?? ''; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifier le produit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_product">
                    <input type="hidden" name="product_id" value="<?php echo $product['id'] ?? ''; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <!-- UPLOAD IMAGE -->
                            <div class="col-md-5 mb-4">
                                <label class="form-label fw-bold">Image du produit</label>
                                <div class="upload-zone">
                                    <div class="product-image-preview" id="editImagePreview<?php echo $product['id'] ?? ''; ?>">
                                        <?php if(($product['image'] ?? '') && file_exists($upload_dir . $product['image'])): ?>
                                            <img src="../uploads/<?php echo $product['image']; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name'] ?? ''); ?>"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <i class="fas fa-cloud-upload-alt" style="display: none;"></i>
                                        <?php else: ?>
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" name="image" class="d-none" 
                                           onchange="previewEditImage(this, <?php echo $product['id'] ?? ''; ?>)">
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-outline-warning btn-sm image-upload-btn" 
                                                onclick="this.previousElementSibling.click()">
                                            <i class="fas fa-sync me-2"></i>Changer l'image
                                        </button>
                                    </div>
                                    <?php if($product['image'] ?? ''): ?>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeImage<?php echo $product['id'] ?? ''; ?>">
                                            <label class="form-check-label text-danger small" for="removeImage<?php echo $product['id'] ?? ''; ?>">
                                                Supprimer l'image actuelle
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- INFORMATIONS PRODUIT -->
                            <div class="col-md-7">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Nom du produit *</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Catégorie *</label>
                                        <select name="category_id" class="form-select" required>
                                            <?php foreach($categories as $cat): ?>
                                                <option value="<?php echo $cat['id'] ?? ''; ?>" <?php echo ($cat['id'] ?? '') == ($product['category_id'] ?? '') ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['nom'] ?? ''); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Prix (MGA) *</label>
                                        <input type="number" name="price" class="form-control" step="0.01" min="0" 
                                               value="<?php echo $product['price'] ?? 0; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Stock *</label>
                                        <input type="number" name="stock" class="form-control" min="0" 
                                               value="<?php echo $product['stock'] ?? 0; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Artiste *</label>
                                    <input type="text" name="artist" class="form-control" value="<?php echo htmlspecialchars($product['artist'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Description *</label>
                                <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Matériaux</label>
                                <input type="text" name="materials" class="form-control" 
                                       value="<?php echo htmlspecialchars($product['materials'] ?? ''); ?>" 
                                       placeholder="ex: Peinture à l'huile, toile">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dimensions</label>
                                <input type="text" name="dimensions" class="form-control" 
                                       value="<?php echo htmlspecialchars($product['dimensions'] ?? ''); ?>" 
                                       placeholder="ex: 80x60 cm">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning">Modifier le produit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // PREVIEW IMAGE POUR AJOUT
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                }
                reader.readAsDataURL(file);
            }
        });

        // PREVIEW IMAGE POUR MODIFICATION
        function previewEditImage(input, productId) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('editImagePreview' + productId);
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                }
                reader.readAsDataURL(file);
            }
        }

        // DRAG & DROP FUNCTIONALITY
        const uploadZone = document.getElementById('uploadZone');
        const imageInput = document.getElementById('imageInput');

        // Empêcher le comportement par défaut
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight drop zone when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            uploadZone.classList.add('dragover');
        }

        function unhighlight() {
            uploadZone.classList.remove('dragover');
        }

        // Handle dropped files
        uploadZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                imageInput.files = files;
                previewImage(files[0]);
            }
        }

        function previewImage(file) {
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                }
                reader.readAsDataURL(file);
            }
        }

        // CLICK ON PREVIEW TO TRIGGER FILE SELECT
        document.getElementById('imagePreview').addEventListener('click', function() {
            imageInput.click();
        });

        // VALIDATION FORMULAIRE
        document.querySelector('form').addEventListener('submit', function(e) {
            const price = document.querySelector('input[name="price"]');
            const stock = document.querySelector('input[name="stock"]');
            
            if (price && parseFloat(price.value) <= 0) {
                e.preventDefault();
                alert('⚠️ Le prix doit être supérieur à 0');
                price.focus();
                return false;
            }
            
            if (stock && parseInt(stock.value) < 0) {
                e.preventDefault();
                alert('⚠️ Le stock ne peut pas être négatif');
                stock.focus();
                return false;
            }
        });

        console.log('🎨 Système de gestion d\'art chargé avec succès !');
    </script>
</body>
</html>