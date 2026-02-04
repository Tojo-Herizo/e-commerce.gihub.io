<?php
require 'config.php';

// paramètres recherche / catégorie / page
$q = secure_data($_GET['q'] ?? '');
$cat = intval($_GET['cat'] ?? 0);
$page = max(1, intval($_GET['page'] ?? 1));
$perpage = 12; // Plus de produits sur la page boutique
$offset = ($page-1)*$perpage;

// Construction de la requête SQL
$sql_where = "WHERE p.status = 'active'";
$params = [];
$types = '';

if ($q !== '') { 
    $sql_where .= " AND (p.name LIKE CONCAT('%',?,'%') OR p.description LIKE CONCAT('%',?,'%'))"; 
    $params[] = $q; 
    $params[] = $q; 
    $types .= 'ss'; 
}

if ($cat > 0) { 
    $sql_where .= " AND p.category_id = ?"; 
    $params[] = $cat; 
    $types .= 'i'; 
}

// Compter le total
$count_sql = "SELECT COUNT(*) as total FROM products p $sql_where";
$stmt = mysqli_prepare($conn, $count_sql);
if ($types) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$total_data = mysqli_fetch_assoc($count_result);
$total = $total_data['total'] ?? 0;
mysqli_stmt_close($stmt);

// Récupérer les produits
$sql = "SELECT p.*, c.nom as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $sql_where 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $sql);

if ($types) {
    $full_types = $types . 'ii';
    $full_params = array_merge($params, [$perpage, $offset]);
    mysqli_stmt_bind_param($stmt, $full_types, ...$full_params);
} else {
    mysqli_stmt_bind_param($stmt, 'ii', $perpage, $offset);
}

mysqli_stmt_execute($stmt);
$products_result = mysqli_stmt_get_result($stmt);
$products = mysqli_fetch_all($products_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Récupérer les catégories
$cats_result = mysqli_query($conn, "SELECT * FROM categories WHERE status = 'active'");
$cats = mysqli_fetch_all($cats_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boutique - Fanilo Art Studio | Œuvres d'Art Malgache</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a3d5d;
            --secondary: #2a7abf;
            --accent: #eef6fb;
        }
        
        .boutique-hero {
            background: linear-gradient(rgba(26, 61, 93, 0.9), rgba(42, 122, 191, 0.8)), 
                        url('https://images.unsplash.com/photo-1563089145-599997674d42?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .card {
            border: none;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .product-image {
            height: 200px;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        
        .price {
            color: var(--primary);
            font-weight: bold;
            font-size: 1.3rem;
        }
        
        .category-badge {
            background: var(--accent);
            color: var(--primary);
            font-size: 0.8rem;
            padding: 4px 12px;
            border-radius: 20px;
        }
        
        .filter-sidebar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }
        
        .stats-badge {
            background: var(--primary);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
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
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link active" href="boutique.php">Boutique</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">À propos</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>
                <div class="navbar-nav">
                    <a class="nav-link" href="cart.php">
                        <i class="fas fa-shopping-cart"></i> Panier
                        <?php if(!empty($_SESSION['cart'])): ?>
                            <span class="stats-badge"><?php echo count($_SESSION['cart']); ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section Boutique -->
    <section class="boutique-hero">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Notre Boutique d'Art</h1>
            <p class="lead mb-4">Découvrez des œuvres uniques inspirées par la richesse culturelle et naturelle de Madagascar</p>
            
            <!-- Barre de recherche -->
            <form method="get" action="boutique.php" class="row g-2 justify-content-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control form-control-lg" 
                               placeholder="Rechercher une œuvre, un artiste..." value="<?php echo htmlspecialchars($q); ?>">
                        <button class="btn btn-light btn-lg" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <div class="container py-4">
        <div class="row">
            <!-- Sidebar Filtres -->
            <div class="col-lg-3 mb-4">
                <div class="filter-sidebar">
                    <h5 class="text-primary mb-4"><i class="fas fa-filter me-2"></i>Filtres</h5>
                    
                    <!-- Filtre Catégories -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">Catégories</h6>
                        <div class="list-group list-group-flush">
                            <a href="boutique.php" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $cat == 0 ? 'active' : ''; ?>">
                                Toutes les catégories
                                <span class="badge bg-primary rounded-pill"><?php echo $total; ?></span>
                            </a>
                            <?php foreach($cats as $c): ?>
                                <a href="boutique.php?cat=<?php echo $c['id']; ?>" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $cat == $c['id'] ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($c['nom']); ?>
                                    <?php
                                    // Compter les produits par catégorie
                                    $count_cat = mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE category_id = {$c['id']} AND status='active'");
                                    $count_data = mysqli_fetch_assoc($count_cat);
                                    ?>
                                    <span class="badge bg-secondary rounded-pill"><?php echo $count_data['count']; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Informations -->
                    <div class="border-top pt-3">
                        <div class="text-center">
                            <i class="fas fa-shipping-fast fa-2x text-primary mb-2"></i>
                            <h6 class="mb-1">Livraison Offerte</h6>
                            <p class="small text-muted mb-0">À Antananarivo</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Produits -->
            <div class="col-lg-9">
                <!-- En-tête avec stats -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="h3 text-primary mb-1">Toutes nos œuvres</h2>
                        <p class="text-muted mb-0"><?php echo $total; ?> œuvre(s) disponible(s)</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Page <?php echo $page; ?> sur <?php echo max(1, ceil($total/$perpage)); ?></small>
                    </div>
                </div>

                <?php if($q): ?>
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="fas fa-search me-3 fa-lg"></i>
                        <div>
                            <strong>Résultats de recherche</strong><br>
                            <span class="mb-0">Pour "<?php echo htmlspecialchars($q); ?>" - <?php echo $total; ?> résultat(s)</span>
                        </div>
                        <a href="boutique.php" class="btn btn-outline-primary btn-sm ms-auto">Tout voir</a>
                    </div>
                <?php endif; ?>

                <!-- Grille des produits -->
                <?php if(empty($products)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-palette fa-4x text-muted mb-4"></i>
                        <h3 class="text-muted">Aucune œuvre trouvée</h3>
                        <p class="text-muted mb-4">
                            <?php if($total == 0): ?>
                                Notre boutique est en cours de préparation.<br>
                                <small>Revenez bientôt pour découvrir nos nouvelles créations.</small>
                            <?php else: ?>
                                Aucun résultat ne correspond à votre recherche.
                            <?php endif; ?>
                        </p>
                        <a href="boutique.php" class="btn btn-primary">
                            <i class="fas fa-redo me-2"></i>Voir toutes les œuvres
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach($products as $p): ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                                <div class="card h-100">
                                    <!-- Image produit -->
                                    <div class="product-image">
                                        <i class="fas fa-image fa-3x"></i>
                                    </div>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <!-- En-tête produit -->
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="category-badge"><?php echo htmlspecialchars($p['category_name']); ?></span>
                                            <?php if($p['stock'] <= 3 && $p['stock'] > 0): ?>
                                                <span class="badge bg-warning">Bientôt épuisé</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Titre et description -->
                                        <h5 class="card-title"><?php echo htmlspecialchars($p['name']); ?></h5>
                                        <p class="card-text text-muted small flex-grow-1">
                                            <?php 
                                            $desc = htmlspecialchars($p['description']);
                                            echo strlen($desc) > 80 ? substr($desc, 0, 80) . '...' : $desc;
                                            ?>
                                        </p>
                                        
                                        <!-- Artiste -->
                                        <p class="text-muted small mb-2">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($p['artist']); ?>
                                        </p>
                                        
                                        <!-- Prix et actions -->
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="price"><?php echo number_format($p['price'], 0, ',', ' '); ?> MGA</span>
                                                <span class="badge bg-<?php echo $p['stock'] > 0 ? 'success' : 'danger'; ?>">
                                                    <?php echo $p['stock'] > 0 ? $p['stock'] . ' dispo' : 'Rupture'; ?>
                                                </span>
                                            </div>
                                            
                                            <div class="d-grid gap-2">
                                                <a href="product.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i>Voir les détails
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php
                    $total_pages = ceil($total / $perpage);
                    if ($total_pages > 1):
                    ?>
                    <nav aria-label="Navigation des pages" class="mt-5">
                        <ul class="pagination justify-content-center">
                            <!-- Page précédente -->
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Pages -->
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Page suivante -->
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Fanilo Art Studio</h5>
                    <p>Artisanat et créations inspirés de Madagascar</p>
                </div>
                <div class="col-md-6 text-end">
                    <p>&copy; 2025 Fanilo Art Studio. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>