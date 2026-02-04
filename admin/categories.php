<?php
require '../config.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// CRÉER LE DOSSIER UPLOADS S'IL N'EXISTE PAS
$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// VARIABLES
$success = $error = '';
$edit_category = null;

// ==================== AJOUTER CATÉGORIE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    try {
        $nom = mysqli_real_escape_string($conn, trim($_POST['nom']));
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        // VALIDATION
        if (empty($nom)) {
            throw new Exception("Le nom de la catégorie est obligatoire");
        }
        
        // VÉRIFIER SI LA CATÉGORIE EXISTE DÉJÀ
        $check_sql = "SELECT id FROM categories WHERE nom = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 's', $nom);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            throw new Exception("Une catégorie avec ce nom existe déjà");
        }
        
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
                throw new Exception("Format d'image non supporté. Utilisez JPG, PNG, GIF ou WebP.");
            }
            
            if ($file_size > $max_size) {
                throw new Exception("L'image est trop volumineuse (max 5MB).");
            }
            
            // GÉNÉRER UN NOM UNIQUE
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $image_name = 'category_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $image_name;
            
            if (!move_uploaded_file($file_tmp, $upload_path)) {
                throw new Exception("Erreur lors de l'upload de l'image.");
            }
            
            // COMPRESSER L'IMAGE
            compressImage($upload_path, $upload_path, 80);
        }
        
        // INSERTION DANS LA BASE
        $sql = "INSERT INTO categories (nom, description, image, status, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssss', $nom, $description, $image_name, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "✅ Catégorie ajoutée avec succès !";
        } else {
            throw new Exception("Erreur base de données : " . mysqli_error($conn));
        }
        
    } catch (Exception $e) {
        $error = "❌ " . $e->getMessage();
    }
}

// ==================== MODIFIER CATÉGORIE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_category') {
    try {
        $category_id = intval($_POST['category_id']);
        $nom = mysqli_real_escape_string($conn, trim($_POST['nom']));
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        // VALIDATION
        if (empty($nom)) {
            throw new Exception("Le nom de la catégorie est obligatoire");
        }
        
        // VÉRIFIER SI LA CATÉGORIE EXISTE DÉJÀ (EXCLURE L'ACTUELLE)
        $check_sql = "SELECT id FROM categories WHERE nom = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 'si', $nom, $category_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            throw new Exception("Une autre catégorie avec ce nom existe déjà");
        }
        
        // RÉCUPÉRER L'ANCIENNE IMAGE
        $old_image_result = mysqli_query($conn, "SELECT image FROM categories WHERE id = $category_id");
        $old_image_data = mysqli_fetch_assoc($old_image_result);
        $old_image = $old_image_data['image'];
        $new_image_name = $old_image;
        
        // GESTION SUPPRESSION IMAGE
        if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
            if ($old_image && file_exists($upload_dir . $old_image)) {
                unlink($upload_dir . $old_image);
            }
            $new_image_name = '';
        }
        
        // GESTION NOUVELLE IMAGE
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_name = $_FILES['image']['name'];
            $file_size = $_FILES['image']['size'];
            $file_type = $_FILES['image']['type'];
            
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024;
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Format d'image non supporté.");
            }
            
            if ($file_size > $max_size) {
                throw new Exception("L'image est trop volumineuse (max 5MB).");
            }
            
            // SUPPRIMER L'ANCIENNE IMAGE
            if ($old_image && file_exists($upload_dir . $old_image)) {
                unlink($upload_dir . $old_image);
            }
            
            // UPLOAD NOUVELLE IMAGE
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $new_image_name = 'category_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_image_name;
            
            if (!move_uploaded_file($file_tmp, $upload_path)) {
                throw new Exception("Erreur lors de l'upload de la nouvelle image.");
            }
            
            compressImage($upload_path, $upload_path, 80);
        }
        
        // MISE À JOUR
        $sql = "UPDATE categories SET nom = ?, description = ?, image = ?, status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssssi', $nom, $description, $new_image_name, $status, $category_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "✅ Catégorie modifiée avec succès !";
        } else {
            throw new Exception("Erreur base de données : " . mysqli_error($conn));
        }
        
    } catch (Exception $e) {
        $error = "❌ " . $e->getMessage();
    }
}

// ==================== SUPPRIMER CATÉGORIE ====================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $category_id = intval($_GET['id']);
        
        // VÉRIFIER SI LA CATÉGORIE EST UTILISÉE
        $check_sql = "SELECT COUNT(*) as product_count FROM products WHERE category_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 'i', $category_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $product_data = mysqli_fetch_assoc($check_result);
        
        if ($product_data['product_count'] > 0) {
            throw new Exception("Impossible de supprimer : cette catégorie contient " . $product_data['product_count'] . " produit(s).");
        }
        
        // RÉCUPÉRER L'IMAGE
        $image_result = mysqli_query($conn, "SELECT image FROM categories WHERE id = $category_id");
        $image_data = mysqli_fetch_assoc($image_result);
        
        // SUPPRIMER LA CATÉGORIE
        $sql = "DELETE FROM categories WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $category_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // SUPPRIMER L'IMAGE
            if ($image_data['image'] && file_exists($upload_dir . $image_data['image'])) {
                unlink($upload_dir . $image_data['image']);
            }
            $success = "✅ Catégorie supprimée avec succès !";
        } else {
            throw new Exception("Erreur lors de la suppression : " . mysqli_error($conn));
        }
        
    } catch (Exception $e) {
        $error = "❌ " . $e->getMessage();
    }
}

// ==================== FONCTION COMPRESSION IMAGE ====================
function compressImage($source, $destination, $quality) {
    $info = getimagesize($source);
    
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
        imagejpeg($image, $destination, $quality);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
        imagepng($image, $destination, 9 - ($quality / 100 * 9));
    } elseif ($info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
        imagegif($image, $destination);
    } elseif ($info['mime'] == 'image/webp') {
        $image = imagecreatefromwebp($source);
        imagewebp($image, $destination, $quality);
    } else {
        return false;
    }
    
    imagedestroy($image);
    return true;
}

// ==================== RÉCUPÉRATION DONNÉES ====================
$categories_result = mysqli_query($conn, "
    SELECT c.*, 
           (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count,
           (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.stock > 0) as available_products
    FROM categories c 
    ORDER BY c.created_at DESC
");

$categories = [];
if ($categories_result) {
    $categories = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);
}

// RÉCUPÉRATION POUR ÉDITION
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_result = mysqli_query($conn, "SELECT * FROM categories WHERE id = $edit_id");
    if ($edit_result && mysqli_num_rows($edit_result) > 0) {
        $edit_category = mysqli_fetch_assoc($edit_result);
    }
}

// STATISTIQUES
$stats = [
    'total' => 0,
    'active' => 0,
    'with_products' => 0,
    'total_products' => 0
];

if ($categories) {
    $stats['total'] = count($categories);
    foreach ($categories as $cat) {
        if ($cat['status'] === 'active') $stats['active']++;
        if ($cat['product_count'] > 0) $stats['with_products']++;
        $stats['total_products'] += $cat['product_count'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Catégories - Fanilo Art Studio</title>
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
            --dark: #343a40;
            --light: #f8f9fa;
        }
        
        .admin-sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, #0f2a42 100%);
            color: white;
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 250px;
            z-index: 1000;
        }
        
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 15px;
            border-radius: 8px;
            margin: 3px 0;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-size: 0.9rem;
        }
        
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left-color: var(--secondary);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 0;
            width: calc(100% - 250px);
            transition: all 0.3s ease;
        }
        
        .stats-card {
            border: none;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-card.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stats-card.active { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stats-card.products { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stats-card.available { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        
        .category-card {
            border: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            background: white;
            height: 100%;
        }
        
        .category-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .category-image-container {
            position: relative;
            width: 100%;
            height: 160px;
            overflow: hidden;
        }
        
        .category-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .category-image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }
        
        .category-image-placeholder span {
            font-size: 0.9rem;
            margin-top: 8px;
            font-weight: bold;
        }
        
        .category-card:hover .category-image {
            transform: scale(1.05);
        }
        
        .image-preview-container {
            width: 100%;
            height: 150px;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .image-preview-container:hover {
            border-color: var(--primary);
            background: #e9ecef;
        }
        
        .image-preview-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .upload-zone {
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .upload-zone.dragover {
            border-color: #28a745;
            background: #e8f5e8;
        }
        
        .status-badge {
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 15px;
            font-weight: 600;
        }
        
        .action-buttons .btn {
            border-radius: 6px;
            margin: 2px;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .action-buttons .btn:hover {
            transform: scale(1.05);
        }
        
        .search-box {
            position: relative;
            max-width: 100%;
        }
        
        .search-box .form-control {
            border-radius: 20px;
            padding-left: 40px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .filter-buttons .btn {
            border-radius: 15px;
            margin: 2px;
            font-size: 0.8rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .progress-bar-custom {
            height: 6px;
            border-radius: 3px;
        }
        
        .floating-action-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .floating-action-btn:hover {
            transform: scale(1.1);
            background: var(--secondary);
        }
        
        .mobile-menu-btn {
            display: none;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 6px 10px;
            margin-right: 10px;
        }
        
        /* RESPONSIVE DESIGN */
        @media (max-width: 1200px) {
            .admin-sidebar {
                width: 220px;
            }
            .main-content {
                margin-left: 220px;
                width: calc(100% - 220px);
            }
        }
        
        @media (max-width: 992px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }
            .mobile-menu-btn {
                display: block;
            }
            .stats-card {
                padding: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .container-fluid {
                padding: 0 10px;
            }
            .stats-card h3 {
                font-size: 1.3rem;
            }
            .category-image-container {
                height: 140px;
            }
            .filter-buttons {
                text-align: left !important;
                margin-top: 10px;
            }
            .search-box {
                margin-bottom: 10px;
            }
        }
        
        @media (max-width: 576px) {
            .stats-card {
                margin-bottom: 10px;
            }
            .category-card {
                margin-bottom: 15px;
            }
            .modal-dialog {
                margin: 10px;
            }
            .floating-action-btn {
                bottom: 15px;
                right: 15px;
                width: 45px;
                height: 45px;
                font-size: 1.1rem;
            }
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- OVERLAY MOBILE -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="admin-sidebar" id="adminSidebar">
                <div class="p-3">
                    <div class="text-center mb-4">
                        <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                            <i class="fas fa-palette fa-1x"></i>
                        </div>
                        <h6 class="mb-1">Fanilo Art Studio</h6>
                        <small class="text-light opacity-75">Administration</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Tableau de bord</a></li>
                        <li class="nav-item"><a class="nav-link" href="products.php"><i class="fas fa-palette me-2"></i>Produits</a></li>
                        <li class="nav-item"><a class="nav-link active" href="categories.php"><i class="fas fa-tags me-2"></i>Catégories</a></li>
                        <li class="nav-item"><a class="nav-link" href="customers.php"><i class="fas fa-users me-2"></i>Clients</a></li>
                        <li class="nav-item"><a class="nav-link" href="orders.php"><i class="fas fa-shopping-cart me-2"></i>Commandes</a></li>
                        <li class="nav-item mt-3"><a class="nav-link text-warning" href="../boutique.php"><i class="fas fa-store me-2"></i>Voir la boutique</a></li>
                        <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                    </ul>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div class="col-12 main-content" id="mainContent">
                <div class="p-3">
                    <!-- HEADER -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <button class="mobile-menu-btn" id="mobileMenuBtn">
                                <i class="fas fa-bars"></i>
                            </button>
                            <div>
                                <h1 class="h4 text-primary mb-1">
                                    <i class="fas fa-tags me-2"></i>Gestion des Catégories
                                </h1>
                                <p class="text-muted mb-0 small">Organisez vos catégories d'œuvres d'art</p>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal">
                            <i class="fas fa-plus me-1"></i>Nouvelle
                        </button>
                    </div>

                    <!-- STATISTIQUES -->
                    <div class="row mb-3">
                        <div class="col-md-3 col-6 mb-2">
                            <div class="stats-card total">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0"><?php echo $stats['total']; ?></h5>
                                        <p class="mb-0 opacity-75 small">Total</p>
                                    </div>
                                    <i class="fas fa-tags opacity-75"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="stats-card active">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0"><?php echo $stats['active']; ?></h5>
                                        <p class="mb-0 opacity-75 small">Actives</p>
                                    </div>
                                    <i class="fas fa-check-circle opacity-75"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="stats-card products">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0"><?php echo $stats['with_products']; ?></h5>
                                        <p class="mb-0 opacity-75 small">Avec Produits</p>
                                    </div>
                                    <i class="fas fa-boxes opacity-75"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="stats-card available">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0"><?php echo $stats['total_products']; ?></h5>
                                        <p class="mb-0 opacity-75 small">Total Œuvres</p>
                                    </div>
                                    <i class="fas fa-palette opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BARRE DE RECHERCHE ET FILTRES -->
                    <div class="card mb-3">
                        <div class="card-body py-2">
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <div class="search-box">
                                        <i class="fas fa-search"></i>
                                        <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Rechercher...">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="filter-buttons text-md-end">
                                        <span class="me-2 text-muted small">Filtrer:</span>
                                        <button class="btn btn-outline-primary btn-sm active" data-filter="all">Toutes</button>
                                        <button class="btn btn-outline-success btn-sm" data-filter="active">Actives</button>
                                        <button class="btn btn-outline-secondary btn-sm" data-filter="inactive">Inactives</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MESSAGES -->
                    <?php if($success): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3">
                            <i class="fas fa-check-circle me-2"></i>
                            <div class="flex-grow-1 small"><?php echo $success; ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div class="flex-grow-1 small"><?php echo $error; ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- GRILLE DES CATÉGORIES -->
                    <div id="categoriesGrid">
                        <div class="row" id="categoriesContainer">
                            <?php if(empty($categories)): ?>
                                <div class="col-12">
                                    <div class="card empty-state">
                                        <div class="card-body">
                                            <i class="fas fa-tags"></i>
                                            <h5 class="text-muted mt-2">Aucune catégorie</h5>
                                            <p class="text-muted mb-3 small">Créez votre première catégorie.</p>
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                                                <i class="fas fa-plus me-1"></i>Créer une catégorie
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach($categories as $category): ?>
                                <div class="col-xl-3 col-lg-4 col-md-6 mb-3 category-item" 
                                     data-status="<?php echo $category['status']; ?>"
                                     data-name="<?php echo strtolower(htmlspecialchars($category['nom'])); ?>">
                                    <div class="card category-card">
                                        <!-- IMAGE -->
                                        <div class="category-image-container">
                                            <?php if(!empty($category['image']) && file_exists($upload_dir . $category['image'])): ?>
                                                <img src="../uploads/<?php echo $category['image']; ?>" 
                                                     class="category-image" 
                                                     alt="<?php echo htmlspecialchars($category['nom']); ?>"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <?php endif; ?>
                                            
                                            <div class="category-image-placeholder" <?php echo !empty($category['image']) ? 'style="display:none"' : ''; ?>>
                                                <i class="fas fa-tags"></i>
                                                <span><?php echo substr(htmlspecialchars($category['nom']), 0, 2); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="card-body position-relative">
                                            <!-- BADGE STATUT -->
                                            <div class="position-absolute top-0 end-0 m-2">
                                                <span class="status-badge bg-<?php echo $category['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo $category['status'] === 'active' ? '🟢' : '⭕'; ?>
                                                </span>
                                            </div>
                                            
                                            <!-- NOM -->
                                            <h6 class="card-title mb-1"><?php echo htmlspecialchars($category['nom']); ?></h6>
                                            
                                            <!-- DESCRIPTION -->
                                            <p class="card-text text-muted small mb-2">
                                                <?php echo htmlspecialchars($category['description'] ?: 'Aucune description'); ?>
                                            </p>
                                            
                                            <!-- STATISTIQUES -->
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <small class="text-muted">Œuvres:</small>
                                                    <small class="fw-bold"><?php echo $category['product_count']; ?></small>
                                                </div>
                                                <div class="progress-bar-custom bg-light">
                                                    <div class="bg-<?php echo $category['product_count'] > 0 ? 'info' : 'secondary'; ?>" 
                                                         style="height: 100%; width: <?php echo min($category['product_count'] * 10, 100); ?>%"></div>
                                                </div>
                                            </div>
                                            
                                            <!-- ACTIONS -->
                                            <div class="action-buttons d-flex justify-content-between">
                                                <button class="btn btn-outline-primary btn-sm" 
                                                        onclick="editCategory(<?php echo $category['id']; ?>)">
                                                    <i class="fas fa-edit me-1"></i>Modifier
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo addslashes($category['nom']); ?>')">
                                                    <i class="fas fa-trash me-1"></i>Supprimer
                                                </button>
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

    <!-- BOUTON FLOATING ACTION -->
    <button class="floating-action-btn" data-bs-toggle="modal" data-bs-target="#categoryModal">
        <i class="fas fa-plus"></i>
    </button>

    <!-- MODAL CATÉGORIE -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-plus me-2"></i>Nouvelle Catégorie
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="categoryForm">
                    <input type="hidden" name="action" id="formAction" value="add_category">
                    <input type="hidden" name="category_id" id="categoryId" value="">
                    
                    <div class="modal-body">
                        <div class="row">
                            <!-- UPLOAD IMAGE -->
                            <div class="col-md-5 mb-3">
                                <label class="form-label fw-bold">Image</label>
                                <div class="upload-zone" id="uploadZone">
                                    <div class="image-preview-container" id="imagePreview">
                                        <i class="fas fa-cloud-upload-alt fa-2x text-muted"></i>
                                        <div class="mt-2 text-muted small">Glissez ou cliquez</div>
                                    </div>
                                    <input type="file" name="image" id="imageInput" accept="image/*" class="d-none">
                                </div>
                                <div class="mt-2 text-center">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('imageInput').click()">
                                        <i class="fas fa-camera me-1"></i>Choisir
                                    </button>
                                </div>
                                <div class="file-info text-muted small text-center mt-1">
                                    <i class="fas fa-info-circle me-1"></i>
                                    JPG, PNG, GIF, WebP | Max: 5MB
                                </div>
                                
                                <!-- CHECKBOX SUPPRESSION IMAGE -->
                                <div class="form-check mt-2 d-none" id="removeImageContainer">
                                    <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeImage">
                                    <label class="form-check-label text-danger small" for="removeImage">
                                        <i class="fas fa-trash me-1"></i>Supprimer l'image
                                    </label>
                                </div>
                            </div>

                            <!-- INFORMATIONS -->
                            <div class="col-md-7">
                                <div class="mb-3">
                                    <label class="form-label">Nom *</label>
                                    <input type="text" name="nom" id="categoryName" class="form-control" 
                                           placeholder="ex: Peintures à l'huile..." required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" id="categoryDescription" class="form-control" 
                                              rows="3" placeholder="Description..."></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Statut *</label>
                                    <select name="status" id="categoryStatus" class="form-select" required>
                                        <option value="active">🟢 Active</option>
                                        <option value="inactive">⭕ Inactive</option>
                                    </select>
                                </div>
                                
                                <!-- INFORMATIONS (EDIT MODE) -->
                                <div class="alert alert-info d-none small" id="editInfo">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <span id="productCountText">Chargement...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm" id="submitButton">
                            <i class="fas fa-save me-1"></i>Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL CONFIRMATION SUPPRESSION -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Supprimer la catégorie :</p>
                    <h6 class="text-danger" id="deleteCategoryName"></h6>
                    <p class="text-muted small mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Cette action est irréversible.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <a href="#" class="btn btn-danger btn-sm" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-1"></i>Supprimer
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // GESTION MENU MOBILE
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const adminSidebar = document.getElementById('adminSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.getElementById('mainContent');

        function toggleMobileMenu() {
            adminSidebar.classList.toggle('mobile-open');
            sidebarOverlay.classList.toggle('active');
        }

        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        sidebarOverlay.addEventListener('click', toggleMobileMenu);

        // GESTION UPLOAD IMAGE
        const imageInput = document.getElementById('imageInput');
        const imagePreview = document.getElementById('imagePreview');
        const uploadZone = document.getElementById('uploadZone');

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-100 h-100 object-fit-cover">`;
                };
                reader.readAsDataURL(file);
            }
        });

        imagePreview.addEventListener('click', () => imageInput.click());

        // DRAG AND DROP
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadZone.addEventListener(eventName, () => uploadZone.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, () => uploadZone.classList.remove('dragover'), false);
        });

        uploadZone.addEventListener('drop', handleDrop, false);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                imageInput.files = files;
                const event = new Event('change');
                imageInput.dispatchEvent(event);
            }
        }

        // RECHERCHE ET FILTRES
        const searchInput = document.getElementById('searchInput');
        const filterButtons = document.querySelectorAll('[data-filter]');

        searchInput.addEventListener('input', function() {
            filterCategories();
        });

        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                filterCategories();
            });
        });

        function filterCategories() {
            const searchTerm = searchInput.value.toLowerCase();
            const activeFilter = document.querySelector('[data-filter].active').dataset.filter;
            const items = document.querySelectorAll('.category-item');

            items.forEach(item => {
                const name = item.dataset.name;
                const status = item.dataset.status;
                
                const matchesSearch = name.includes(searchTerm);
                const matchesFilter = activeFilter === 'all' || status === activeFilter;

                item.style.display = matchesSearch && matchesFilter ? 'block' : 'none';
            });
        }

        // ÉDITION CATÉGORIE
        function editCategory(categoryId) {
            // Simulation - à remplacer par vos données réelles
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Modifier la catégorie';
            document.getElementById('formAction').value = 'edit_category';
            document.getElementById('categoryId').value = categoryId;
            document.getElementById('submitButton').innerHTML = '<i class="fas fa-save me-1"></i>Modifier';
            
            // Ouvrir le modal
            new bootstrap.Modal(document.getElementById('categoryModal')).show();
        }

        // SUPPRESSION CATÉGORIE
        function confirmDelete(categoryId, categoryName) {
            document.getElementById('deleteCategoryName').textContent = categoryName;
            document.getElementById('confirmDeleteBtn').href = `categories.php?action=delete&id=${categoryId}`;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // RESET MODAL
        document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('categoryForm').reset();
            document.getElementById('formAction').value = 'add_category';
            document.getElementById('categoryId').value = '';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Nouvelle Catégorie';
            document.getElementById('submitButton').innerHTML = '<i class="fas fa-save me-1"></i>Créer';
            document.getElementById('imagePreview').innerHTML = '<i class="fas fa-cloud-upload-alt fa-2x text-muted"></i><div class="mt-2 text-muted small">Glissez ou cliquez</div>';
            document.getElementById('removeImageContainer').classList.add('d-none');
            document.getElementById('editInfo').classList.add('d-none');
        });

        // FERMER LE MENU MOBILE EN REDIMENSIONNANT
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                adminSidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
            }
        });
    </script>
</body>
</html>