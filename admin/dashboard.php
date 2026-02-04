<?php
require '../config.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// ==================== STATISTIQUES AVANCÉES ====================

// PRODUITS
$products_stats = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
        SUM(CASE WHEN stock < 5 AND stock > 0 THEN 1 ELSE 0 END) as low_stock,
        SUM(price * stock) as stock_value,
        AVG(price) as avg_price,
        MAX(price) as max_price,
        MIN(price) as min_price
    FROM products 
    WHERE status='active'
");
$products_data = mysqli_fetch_assoc($products_stats);

// CATÉGORIES
$categories_stats = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT p.category_id) as used_categories
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status='active'
    WHERE c.status='active'
");
$categories_data = mysqli_fetch_assoc($categories_stats);

// CLIENTS
$clients_stats = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as new_this_week,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_this_month
    FROM clients
");
$clients_data = mysqli_fetch_assoc($clients_stats);

// COMMANDES
$orders_stats = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(total) as total_revenue,
        AVG(total) as avg_order_value,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN statut = 'confirme' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN statut = 'expedie' THEN 1 ELSE 0 END) as shipped,
        SUM(CASE WHEN statut = 'livre' THEN 1 ELSE 0 END) as delivered
    FROM commandes
");
$orders_data = mysqli_fetch_assoc($orders_stats);

// VENTES MENSUELLES (12 derniers mois)
$monthly_sales = mysqli_query($conn, "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as order_count,
        SUM(total) as revenue
    FROM commandes 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");

// PRODUITS LES PLUS VENDUS (simulation)
$top_products = mysqli_query($conn, "
    SELECT 
        p.name,
        p.price,
        p.stock,
        c.nom as category_name,
        (p.stock * 0.1) as simulated_sales -- Simulation pour la démo
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status='active'
    ORDER BY simulated_sales DESC
    LIMIT 5
");

// DERNIÈRES ACTIVITÉS
$recent_activities = mysqli_query($conn, "
    (SELECT 'commande' as type, id, created_at, CONCAT('Nouvelle commande #', id) as description, total as amount FROM commandes ORDER BY created_at DESC LIMIT 3)
    UNION ALL
    (SELECT 'client' as type, id, created_at, CONCAT('Nouveau client: ', nom) as description, 0 as amount FROM clients ORDER BY created_at DESC LIMIT 2)
    UNION ALL
    (SELECT 'produit' as type, id, created_at, CONCAT('Nouveau produit: ', name) as description, price as amount FROM products ORDER BY created_at DESC LIMIT 2)
    ORDER BY created_at DESC 
    LIMIT 7
");

// ALERTES
$alerts = [];
if ($products_data['out_of_stock'] > 0) {
    $alerts[] = [
        'type' => 'danger',
        'icon' => 'fas fa-exclamation-triangle',
        'title' => 'Rupture de stock',
        'message' => $products_data['out_of_stock'] . ' produit(s) en rupture de stock',
        'link' => 'products.php'
    ];
}
if ($products_data['low_stock'] > 0) {
    $alerts[] = [
        'type' => 'warning',
        'icon' => 'fas fa-exclamation-circle',
        'title' => 'Stock faible',
        'message' => $products_data['low_stock'] . ' produit(s) avec stock faible (< 5)',
        'link' => 'products.php'
    ];
}
if ($orders_data['pending'] > 0) {
    $alerts[] = [
        'type' => 'info',
        'icon' => 'fas fa-clock',
        'title' => 'Commandes en attente',
        'message' => $orders_data['pending'] . ' commande(s) en attente de traitement',
        'link' => 'orders.php'
    ];
}

// STATISTIQUES RAPIDES POUR LES CARTES
$stats_cards = [
    [
        'title' => 'Produits Actifs',
        'value' => $products_data['total'] ?? 0,
        'icon' => 'fas fa-palette',
        'color' => 'primary',
        'trend' => '+12%',
        'description' => 'Total des produits en stock'
    ],
    [
        'title' => 'Valeur du Stock',
        'value' => number_format($products_data['stock_value'] ?? 0, 0, ',', ' ') . ' MGA',
        'icon' => 'fas fa-chart-line',
        'color' => 'success',
        'trend' => '+8%',
        'description' => 'Valeur totale de l\'inventaire'
    ],
    [
        'title' => 'Clients',
        'value' => $clients_data['total'] ?? 0,
        'icon' => 'fas fa-users',
        'color' => 'info',
        'trend' => '+5%',
        'description' => $clients_data['new_this_week'] ?? 0 . ' nouveaux cette semaine'
    ],
    [
        'title' => 'Chiffre d\'Affaires',
        'value' => number_format($orders_data['total_revenue'] ?? 0, 0, ',', ' ') . ' MGA',
        'icon' => 'fas fa-money-bill-wave',
        'color' => 'warning',
        'trend' => '+15%',
        'description' => 'Total des ventes'
    ]
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Fanilo Art Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #1a3d5d;
            --secondary: #2a7abf;
            --accent: #eef6fb;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --dark: #343a40;
            --light: #f8f9fa;
        }
        
        .admin-wrapper {
            background: #f5f7fa;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .admin-sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, #0f2a42 100%);
            color: white;
            min-height: 100vh;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            position: fixed;
            width: 280px;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 4px 10px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .admin-sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.12);
            border-left-color: var(--secondary);
            transform: translateX(3px);
        }
        
        .admin-sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.15);
            border-left-color: var(--secondary);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .admin-main {
            margin-left: 280px;
            padding: 0;
            transition: margin-left 0.3s ease;
            width: calc(100% - 280px);
        }
        
        .admin-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 15px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.04);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 6px;
        }
        
        .stat-trend {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 15px;
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 12px;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.04);
            height: 100%;
        }
        
        /* STYLES SPÉCIFIQUES POUR LE GRAPHIQUE */
        .chart-wrapper {
            position: relative;
            height: 180px; /* Hauteur réduite */
            width: 100%;
        }
        
        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid #f1f3f4;
            transition: background 0.2s ease;
        }
        
        .activity-item:hover {
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .alert-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            font-size: 0.9rem;
        }
        
        .progress-thin {
            height: 5px;
            border-radius: 3px;
        }
        
        .badge-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .quick-action {
            background: white;
            border: 2px dashed #e9ecef;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
        }
        
        .quick-action:hover {
            border-color: var(--primary);
            background: rgba(26, 61, 93, 0.02);
            transform: translateY(-2px);
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(26, 61, 93, 0.3);
            height: 100%;
        }
        
        /* RESPONSIVE DESIGN */
        @media (max-width: 1200px) {
            .admin-sidebar {
                width: 250px;
            }
            .admin-main {
                margin-left: 250px;
                width: calc(100% - 250px);
            }
            .stat-number {
                font-size: 1.6rem;
            }
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
            }
            .chart-wrapper {
                height: 160px;
            }
        }
        
        @media (max-width: 992px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-main {
                margin-left: 0;
                width: 100%;
            }
            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }
            .mobile-menu-btn {
                display: block !important;
            }
            .stat-card {
                padding: 15px;
            }
            .chart-container {
                padding: 15px;
            }
            .chart-wrapper {
                height: 150px;
            }
        }
        
        @media (max-width: 768px) {
            .admin-header {
                padding: 12px 15px;
            }
            .container-fluid.py-4 {
                padding: 15px !important;
            }
            .stat-number {
                font-size: 1.4rem;
            }
            .welcome-card {
                padding: 20px;
            }
            .quick-action {
                padding: 12px;
            }
            .quick-action h6 {
                font-size: 0.85rem;
            }
            .chart-wrapper {
                height: 140px;
            }
        }
        
        @media (max-width: 576px) {
            .admin-header h1 {
                font-size: 1.5rem;
            }
            .stat-card {
                margin-bottom: 15px;
            }
            .chart-container {
                margin-bottom: 15px;
            }
            .mobile-menu-btn {
                padding: 6px 10px;
            }
            .chart-wrapper {
                height: 130px;
            }
        }
        
        /* Bouton menu mobile */
        .mobile-menu-btn {
            display: none;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            margin-right: 15px;
        }
        
        /* Overlay pour mobile */
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
    </style>
</head>
<body class="admin-wrapper">
    <!-- Overlay pour mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR FIXE -->
            <div class="admin-sidebar" id="adminSidebar">
                <div class="p-3 p-lg-4">
                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <div class="stat-icon mx-auto mb-2" style="background: rgba(255,255,255,0.15); color: white;">
                            <i class="fas fa-palette"></i>
                        </div>
                        <h5 class="mb-1">Fanilo Art Studio</h5>
                        <small class="text-light opacity-75">Administration</small>
                    </div>
                    
                    <!-- Navigation -->
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Tableau de Bord
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="fas fa-palette me-2"></i>Produits
                                <span class="badge bg-light text-primary ms-2"><?php echo $products_data['total'] ?? 0; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php">
                                <i class="fas fa-tags me-2"></i>Catégories
                                <span class="badge bg-light text-primary ms-2"><?php echo $categories_data['total'] ?? 0; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="customers.php">
                                <i class="fas fa-users me-2"></i>Clients
                                <span class="badge bg-light text-primary ms-2"><?php echo $clients_data['total'] ?? 0; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="fas fa-shopping-cart me-2"></i>Commandes
                                <span class="badge bg-light text-primary ms-2"><?php echo $orders_data['total'] ?? 0; ?></span>
                            </a>
                        </li>
                        
                        <li class="nav-item mt-4 pt-3">
                            <a class="nav-link text-warning" href="../boutique.php">
                                <i class="fas fa-store me-2"></i>Voir la Boutique
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

            <!-- CONTENU PRINCIPAL -->
            <div class="admin-main" id="adminMain">
                <!-- HEADER -->
                <div class="admin-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <button class="mobile-menu-btn" id="mobileMenuBtn">
                                <i class="fas fa-bars"></i>
                            </button>
                            <h1 class="h4 mb-0 text-dark d-inline-block">
                                <i class="fas fa-tachometer-alt me-2 text-primary"></i>Tableau de Bord
                            </h1>
                            <p class="text-muted mb-0 small d-none d-md-block">Aperçu complet de votre activité</p>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex align-items-center">
                                <span class="text-muted me-3 small d-none d-md-inline"><?php echo date('d F Y'); ?></span>
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-user-circle me-2"></i><?php echo $_SESSION['admin_username']; ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Paramètres</a></li>
                                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CONTENU -->
                <div class="container-fluid py-3">
                    <!-- CARTES DE BIENVENUE ET ALERTES -->
                    <div class="row mb-4">
                        <div class="col-xxl-8 col-lg-7 mb-3">
                            <div class="welcome-card">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h4 class="mb-2">Bonjour, <?php echo $_SESSION['admin_username']; ?> ! 👋</h4>
                                        <p class="mb-0 opacity-90 small">Voici un résumé de votre activité aujourd'hui.</p>
                                    </div>
                                    <div class="col-md-4 text-end d-none d-md-block">
                                        <div class="stat-icon mx-auto" style="background: rgba(255,255,255,0.2);">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-4 col-lg-5 mb-3">
                            <?php if(!empty($alerts)): ?>
                                <?php foreach($alerts as $alert): ?>
                                <div class="alert alert-<?php echo $alert['type']; ?> alert-card mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="<?php echo $alert['icon']; ?> me-2"></i>
                                        <div class="flex-grow-1">
                                            <strong class="small"><?php echo $alert['title']; ?></strong>
                                            <p class="mb-0 small"><?php echo $alert['message']; ?></p>
                                        </div>
                                        <a href="<?php echo $alert['link']; ?>" class="btn btn-<?php echo $alert['type']; ?> btn-sm ms-2">Voir</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-success alert-card">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <div>
                                            <strong class="small">Tout va bien !</strong>
                                            <p class="mb-0 small">Aucune alerte pour le moment</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- CARTES DE STATISTIQUES -->
                    <div class="row mb-4">
                        <?php foreach($stats_cards as $card): ?>
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="stat-icon" style="background: rgba(26, 61, 93, 0.1); color: var(--primary);">
                                        <i class="<?php echo $card['icon']; ?>"></i>
                                    </div>
                                    <span class="stat-trend"><?php echo $card['trend']; ?></span>
                                </div>
                                <div class="stat-number"><?php echo $card['value']; ?></div>
                                <h6 class="text-muted mb-1 small"><?php echo $card['title']; ?></h6>
                                <p class="small text-muted mb-0"><?php echo $card['description']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- GRAPHIQUES ET TABLEAUX -->
                    <div class="row mb-4">
                        <!-- Graphique des ventes -->
                        <div class="col-xl-8 col-lg-7 mb-3">
                            <div class="chart-container">
                                <h6 class="mb-3">
                                    <i class="fas fa-chart-bar me-2 text-primary"></i>Évolution des ventes
                                </h6>
                                <div class="chart-wrapper">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Produits populaires -->
                        <div class="col-xl-4 col-lg-5 mb-3">
                            <div class="chart-container">
                                <h6 class="mb-3">
                                    <i class="fas fa-fire me-2 text-warning"></i>Produits populaires
                                </h6>
                                <div class="list-group list-group-flush">
                                    <?php while($product = mysqli_fetch_assoc($top_products)): ?>
                                    <div class="list-group-item px-0 py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 small"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($product['category_name']); ?></small>
                                            </div>
                                            <div class="text-end ms-2">
                                                <strong class="text-primary small"><?php echo number_format($product['price'], 0, ',', ' '); ?> MGA</strong>
                                                <div class="progress progress-thin mt-1" style="width: 60px;">
                                                    <div class="progress-bar bg-success" style="width: <?php echo min($product['simulated_sales'] * 10, 100); ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ACTIVITÉS RÉCENTES ET ACTIONS RAPIDES -->
                    <div class="row">
                        <!-- Activités récentes -->
                        <div class="col-xl-8 col-lg-7 mb-3">
                            <div class="chart-container">
                                <h6 class="mb-3">
                                    <i class="fas fa-history me-2 text-primary"></i>Activités récentes
                                </h6>
                                <div class="activity-list">
                                    <?php while($activity = mysqli_fetch_assoc($recent_activities)): ?>
                                    <div class="activity-item">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="stat-icon" style="width: 40px; height: 40px; background: rgba(26, 61, 93, 0.1); font-size: 1rem;">
                                                    <i class="fas fa-<?php echo $activity['type'] == 'commande' ? 'shopping-cart' : ($activity['type'] == 'client' ? 'user' : 'palette'); ?>"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-2">
                                                <p class="mb-1 small"><?php echo $activity['description']; ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                                </small>
                                            </div>
                                            <?php if($activity['amount'] > 0): ?>
                                            <div class="flex-shrink-0">
                                                <span class="badge bg-success small"><?php echo number_format($activity['amount'], 0, ',', ' '); ?> MGA</span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Actions rapides -->
                        <div class="col-xl-4 col-lg-5 mb-3">
                            <div class="chart-container">
                                <h6 class="mb-3">
                                    <i class="fas fa-bolt me-2 text-warning"></i>Actions rapides
                                </h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="quick-action" onclick="window.location.href='products.php'">
                                            <i class="fas fa-plus text-primary mb-2"></i>
                                            <h6 class="small">Ajouter Produit</h6>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="quick-action" onclick="window.location.href='orders.php'">
                                            <i class="fas fa-shopping-cart text-success mb-2"></i>
                                            <h6 class="small">Voir Commandes</h6>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="quick-action" onclick="window.location.href='customers.php'">
                                            <i class="fas fa-users text-info mb-2"></i>
                                            <h6 class="small">Gérer Clients</h6>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="quick-action" onclick="window.location.href='categories.php'">
                                            <i class="fas fa-tags text-warning mb-2"></i>
                                            <h6 class="small">Catégories</h6>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Statistiques rapides -->
                                <div class="mt-3 pt-3 border-top">
                                    <h6 class="mb-2 small">Aperçu rapide</h6>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="border rounded p-1">
                                                <div class="text-primary fw-bold small"><?php echo $orders_data['pending'] ?? 0; ?></div>
                                                <small class="text-muted">En attente</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-1">
                                                <div class="text-success fw-bold small"><?php echo $orders_data['delivered'] ?? 0; ?></div>
                                                <small class="text-muted">Livrés</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-1">
                                                <div class="text-warning fw-bold small"><?php echo $products_data['low_stock'] ?? 0; ?></div>
                                                <small class="text-muted">Stock faible</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du menu mobile
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const adminSidebar = document.getElementById('adminSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const adminMain = document.getElementById('adminMain');

        function toggleMobileMenu() {
            adminSidebar.classList.toggle('mobile-open');
            sidebarOverlay.classList.toggle('active');
            document.body.style.overflow = adminSidebar.classList.contains('mobile-open') ? 'hidden' : '';
        }

        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        sidebarOverlay.addEventListener('click', toggleMobileMenu);

        // Graphique des ventes - TAILLE RÉDUITE
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
                datasets: [{
                    label: 'Chiffre d\'affaires (MGA)',
                    data: [1200000, 1500000, 1800000, 2200000, 2500000, 2800000, 3200000, 3500000, 3800000, 4200000, 4500000, 4800000],
                    borderColor: '#1a3d5d',
                    backgroundColor: 'rgba(26, 61, 93, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'CA: ' + context.parsed.y.toLocaleString() + ' MGA';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: 9
                            }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: 9
                            },
                            callback: function(value) {
                                return (value / 1000000) + 'M';
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    }
                },
                elements: {
                    point: {
                        radius: 2,
                        hoverRadius: 4
                    },
                    line: {
                        tension: 0.4
                    }
                }
            }
        });

        // Mise à jour de l'heure en temps réel
        function updateTime() {
            const now = new Date();
            const timeElement = document.querySelector('.text-muted.me-3');
            if (timeElement) {
                timeElement.textContent = 
                    now.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }
        }
        setInterval(updateTime, 60000);
        updateTime();

        // Fermer le menu mobile en redimensionnant la fenêtre
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                adminSidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Redimensionner le graphique quand la fenêtre change de taille
        window.addEventListener('resize', function() {
            salesChart.resize();
        });
    </script>
</body>
</html>