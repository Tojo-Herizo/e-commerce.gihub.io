<?php
require 'config.php';

// paramètres recherche / catégorie / page
$q = secure_data($_GET['q'] ?? '');
$cat = intval($_GET['cat'] ?? 0);
$page = max(1, intval($_GET['page'] ?? 1));
$perpage = 8;
$offset = ($page-1)*$perpage;

// Construction de la requête SQL
$where = [];
$params = [];
$types = '';

if ($q !== '') { 
    $where[] = "(p.name LIKE CONCAT('%',?,'%') OR p.description LIKE CONCAT('%',?,'%'))"; 
    $params[] = $q; 
    $params[] = $q; 
    $types .= 'ss'; 
}
if ($cat) { 
    $where[] = 'p.category_id = ?'; 
    $params[] = $cat; 
    $types .= 'i'; 
}

// Construire la clause WHERE correctement
$where_sql = '';
if (!empty($where)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where) . " AND p.status = 'active'";
} else {
    $where_sql = "WHERE p.status = 'active'";
}

// compter total
$count_sql = "SELECT COUNT(*) as c FROM products p $where_sql";
$stmt = mysqli_prepare($conn, $count_sql);
if ($types) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$count_data = mysqli_fetch_assoc($res);
$total = $count_data ? $count_data['c'] : 0;
mysqli_stmt_close($stmt);

// récupérer produits
$sql = "SELECT p.*, c.nom as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $where_sql 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $sql);

// bind params dynamic
if ($types) {
    $types2 = $types.'ii';
    $params2 = array_merge($params, [ $perpage, $offset ]);
    mysqli_stmt_bind_param($stmt, $types2, ...$params2);
} else {
    mysqli_stmt_bind_param($stmt, 'ii', $perpage, $offset);
}

mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$products = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
mysqli_stmt_close($stmt);

// categories pour filtre
$cats_result = mysqli_query($conn, "SELECT * FROM categories WHERE status = 'active'");
$cats = $cats_result ? mysqli_fetch_all($cats_result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <title>Fanilo Art Studio | Boutique d'Art en Ligne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a3d5d;
            --secondary: #2a7abf;
            --accent: #eef6fb;
            --success: #4bb543;
            --warning: #ff9f1c;
            --danger: #e71d36;
            --light: #f8f9fa;
            --dark: #333;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .navbar {
            background-color: var(--primary) !important;
            box-shadow: var(--shadow);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        /* HERO DIAPORAMA FUSIONNÉ */
        .hero-slider {
            height: 600px;
            position: relative;
            overflow: hidden;
            margin-bottom: 40px;
            border-radius: 0 0 15px 15px;
        }
        
        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }
        
        .slide.active {
            opacity: 1;
        }
        
        .slide-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .slide-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.3));
            padding: 0 20px;
        }
        
        .slide-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.7);
        }
        
        .slide-subtitle {
            font-size: 1.4rem;
            margin-bottom: 30px;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.7);
            max-width: 600px;
        }
        
        .slider-nav {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 10;
        }
        
        .nav-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: rgba(255,255,255,0.6);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .nav-dot.active {
            background: white;
            transform: scale(1.3);
            border-color: white;
        }
        
        .slider-arrows {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 25px;
            transform: translateY(-50%);
            z-index: 10;
        }
        
        .arrow {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 50%;
            color: var(--primary);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .arrow:hover {
            background: white;
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        .btn-hero {
            background-color: var(--primary);
            border-color: var(--primary);
            border-radius: 30px;
            padding: 12px 35px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .btn-hero:hover {
            background-color: var(--secondary);
            border-color: var(--secondary);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }
        
        .card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .card-img-top {
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .card:hover .card-img-top {
            transform: scale(1.05);
        }
        
        .price {
            color: var(--primary);
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            border-radius: 20px;
            padding: 8px 20px;
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            border-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .admin-btn {
            background-color: #6c757d;
            border-color: #6c757d;
            border-radius: 20px;
        }
        
        .admin-btn:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
        
        .category-badge {
            background-color: var(--accent);
            color: var(--primary);
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 15px;
        }
        
        .category-sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .category-sidebar .card-header {
            background-color: var(--primary);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .category-list .list-group-item {
            border: none;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: var(--transition);
        }
        
        .category-list .list-group-item:hover,
        .category-list .list-group-item.active {
            background-color: var(--accent);
            color: var(--primary);
        }
        
        .pagination .page-link {
            color: var(--primary);
            border-radius: 8px;
            margin: 0 2px;
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .cart-badge {
            background: var(--secondary);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            position: absolute;
            top: -5px;
            right: -5px;
        }
        
        .search-form .form-control {
            border-radius: 20px 0 0 20px;
            border: none;
            padding: 10px 15px;
        }
        
        .search-form .btn {
            border-radius: 0 20px 20px 0;
            background: white;
            color: var(--primary);
            border: none;
        }
        
        .newsletter-section {
            background-color: var(--primary);
            color: white;
            padding: 60px 0;
            margin-top: 60px;
        }
        
        footer {
            background-color: #2c3e50;
            color: white;
            padding: 40px 0 20px;
        }
        
        .social-links a {
            color: white;
            font-size: 1.2rem;
            margin-right: 15px;
            transition: var(--transition);
        }
        
        .social-links a:hover {
            color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .product-image-placeholder {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }

        /* ==================== */
        /* STYLES DU CHATBOT AMÉLIORÉ */
        /* ==================== */
        #chatbot-widget {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 10000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        #chatbot-toggle {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #1a3d5d, #2a7abf);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            cursor: pointer;
            box-shadow: 0 6px 25px rgba(26, 61, 93, 0.4);
            transition: all 0.3s ease;
            position: relative;
        }

        #chatbot-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 30px rgba(26, 61, 93, 0.6);
        }

        .chatbot-pulse {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.8); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(0.8); opacity: 1; }
        }

        #chatbot-container {
            position: absolute;
            bottom: 85px;
            right: 0;
            width: 380px;
            height: 580px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        #chatbot-container.active {
            display: flex;
        }

        #chatbot-header {
            background: linear-gradient(135deg, #1a3d5d, #2a7abf);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chatbot-avatar {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        #chatbot-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.3rem;
            cursor: pointer;
            padding: 5px;
            transition: opacity 0.3s ease;
        }

        #chatbot-close:hover {
            opacity: 0.8;
        }

        #chatbot-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
            animation: fadeIn 0.3s ease;
        }

        .message.bot {
            justify-content: flex-start;
        }

        .message.user {
            justify-content: flex-end;
        }

        .message-content {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 0.9rem;
            line-height: 1.4;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .message.bot .message-content {
            background: white;
            border: 1px solid #e9ecef;
            border-bottom-left-radius: 5px;
        }

        .message.user .message-content {
            background: linear-gradient(135deg, #1a3d5d, #2a7abf);
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message-time {
            font-size: 0.7rem;
            color: #6c757d;
            margin-top: 5px;
            text-align: right;
        }

        /* Styles pour les boutons de questions rapides */
        #chatbot-quick-questions {
            border-top: 1px solid #e9ecef;
            background: #f8f9fa !important;
            padding: 15px;
        }
        
        .quick-question-btn {
            font-size: 0.75rem;
            padding: 8px 12px;
            border-radius: 15px;
            background: white;
            border: 1px solid #dee2e6;
            color: #1a3d5d;
            transition: all 0.3s ease;
            text-align: center;
            cursor: pointer;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }
        
        .quick-question-btn:hover {
            background: #1a3d5d;
            color: white;
            border-color: #1a3d5d;
            transform: translateY(-2px);
        }
        
        .quick-question-btn:active {
            transform: translateY(0);
        }

        .context-indicator {
            text-align: center;
            color: #6c757d;
            font-size: 0.75rem;
            margin: 10px 0;
            opacity: 0.7;
        }

        #chatbot-input {
            padding: 15px;
            border-top: 1px solid #e9ecef;
            background: white;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        #chatbot-message {
            flex: 1;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            padding: 12px 18px;
            outline: none;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        #chatbot-message:focus {
            border-color: #1a3d5d;
        }

        #chatbot-send {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #1a3d5d, #2a7abf);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #chatbot-send:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(26, 61, 93, 0.3);
        }

        /* Animation d'écriture */
        .typing-indicator .message-content {
            background: white;
            padding: 12px 16px;
        }

        .typing-dots {
            display: flex;
            gap: 4px;
        }

        .typing-dots span {
            width: 6px;
            height: 6px;
            background: #6c757d;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-3px); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 576px) {
            #chatbot-widget {
                bottom: 20px;
                right: 20px;
            }
            
            #chatbot-toggle {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            #chatbot-container {
                width: 320px;
                height: 500px;
                right: -20px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-slider {
                height: 500px;
            }
            
            .slide-title {
                font-size: 2.2rem;
            }
            
            .slide-subtitle {
                font-size: 1.1rem;
            }
            
            .slide-content {
                padding: 0 15px;
            }
            
            .arrow {
                width: 40px;
                height: 40px;
            }
            
            .btn-hero {
                padding: 10px 25px;
                font-size: 1rem;
            }
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
                    <li class="nav-item"><a class="nav-link active" href="index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="boutique.php">Boutique</a></li>
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
                    <a href="admin/login.php" class="btn btn-light admin-btn me-2">
                        <i class="fas fa-cog"></i> Admin
                    </a>
                    <form class="d-flex search-form" method="get" action="index.php">
                        <input class="form-control" name="q" placeholder="Rechercher..." value="<?php echo htmlspecialchars($q); ?>">
                        <button class="btn"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- HERO DIAPORAMA FUSIONNÉ -->
    <div class="hero-slider">
        <!-- Flèches de navigation -->
        <div class="slider-arrows">
            <button class="arrow prev-arrow">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="arrow next-arrow">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        
        <!-- Slide 1 - La Nuit Étoilée -->
        <div class="slide active">
            <img src="data:image/jpeg;base64,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" 
                 alt="La Nuit Étoilée" class="slide-image">
            <div class="slide-content">
                <h1 class="slide-title">Explorez la beauté de l'art naturel</h1>
                <p class="slide-subtitle">Des peintures et créations inspirées par la nature malgache, réalisées avec passion et authenticité.</p>
                <a href="#products" class="btn btn-light btn-hero">
                    <i class="fas fa-gem me-2"></i>Découvrir la Boutique
                </a>
            </div>
        </div>
        
        <!-- Slide 2 - La Joconde -->
        <div class="slide">
            <img src="https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80" 
                 alt="Art Classique" class="slide-image">
            <div class="slide-content">
                <h1 class="slide-title">L'Art Éternel de Madagascar</h1>
                <p class="slide-subtitle">Chaque œuvre raconte l'histoire riche et la culture vibrante de notre terre ancestrale.</p>
                <a href="#products" class="btn btn-light btn-hero">
                    <i class="fas fa-eye me-2"></i>Voir les Créations
                </a>
            </div>
        </div>
        
        <!-- Slide 3 - La Création d'Adam -->
        <div class="slide">
            <img src="https://images.unsplash.com/photo-1578301978018-3005759f48f7?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80" 
                 alt="Art Renaissance" class="slide-image">
            <div class="slide-content">
                <h1 class="slide-title">Maîtrise Artistique Authentique</h1>
                <p class="slide-subtitle">Des techniques ancestrales combinées à une inspiration contemporaine pour des œuvres uniques.</p>
                <a href="#products" class="btn btn-light btn-hero">
                    <i class="fas fa-palette me-2"></i>Inspirez-vous
                </a>
            </div>
        </div>
        
        <!-- Slide 4 - Les Tournesols -->
        <div class="slide">
            <img src="https://images.unsplash.com/photo-1579783901586-d88db74b4fe4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80" 
                 alt="Nature et Couleurs" class="slide-image">
            <div class="slide-content">
                <h1 class="slide-title">Couleurs et Émotions Pures</h1>
                <p class="slide-subtitle">Laissez-vous emporter par la magie des couleurs et l'authenticité de l'art malgache.</p>
                <a href="#products" class="btn btn-light btn-hero">
                    <i class="fas fa-heart me-2"></i>Collection Exclusive
                </a>
            </div>
        </div>

        
        <!-- Navigation par points -->
        <div class="slider-nav">
            <div class="nav-dot active" data-index="0"></div>
            <div class="nav-dot" data-index="1"></div>
            <div class="nav-dot" data-index="2"></div>
            <div class="nav-dot" data-index="3"></div>
        </div>
    </div>

    <div class="container py-4">
        <div class="row">
            <!-- Filtres Catégories -->
            <div class="col-md-3">
                <div class="category-sidebar">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Catégories</h5>
                        </div>
                        <div class="list-group list-group-flush category-list">
                            <a href="index.php" class="list-group-item list-group-item-action <?php echo $cat==0?'active':'';?>">
                                <i class="fas fa-th-large me-2"></i>Toutes les catégories
                                <span class="badge bg-primary rounded-pill float-end"><?php echo $total; ?></span>
                            </a>
                            <?php foreach($cats as $c): ?>
                                <a href="index.php?cat=<?php echo $c['id']; ?>" 
                                   class="list-group-item list-group-item-action <?php echo $cat==$c['id']?'active':'';?>">
                                    <i class="fas fa-<?php echo get_category_icon($c['id']); ?> me-2"></i>
                                    <?php echo htmlspecialchars($c['nom']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>À propos</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">Fanilo Art Studio crée des œuvres uniques inspirées par la nature malgache. Chaque pièce est réalisée avec passion et respect de l'environnement.</p>
                            <a href="about.php" class="btn btn-outline-primary btn-sm">En savoir plus</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Produits -->
            <div class="col-md-9" id="products">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h3 text-primary">Nos œuvres d'art</h2>
                    <div class="text-muted"><?php echo $total; ?> œuvre(s) trouvée(s)</div>
                </div>

                <?php if($q): ?>
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-search me-2"></i>
                        Résultats pour "<?php echo htmlspecialchars($q); ?>"
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- PRODUITS FICTIFS DÉCORATIFS -->
                     <!-- PRODUITS FICTIFS DÉCORATIFS -->
<div class="col-lg-4 col-md-6 mb-4">
    <div class="card h-100">
        <img src="data:image/jpeg;base64,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" 
             alt="Paysage Malgache" class="card-img-top" style="height: 200px; object-fit: cover;">
        <div class="card-body d-flex flex-column">
            <h5 class="card-title">Paysage Malgache</h5>
            <span class="category-badge mb-2 align-self-start">Peinture</span>
            <p class="card-text flex-grow-1">Une magnifique représentation des collines verdoyantes de Madagascar au coucher du soleil.</p>
            <div class="mt-auto">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="price">120,000 MGA</span>
                    <a href="#" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>Voir
                    </a>
                </div>
                <small class="text-success mt-2 d-block">
                    <i class="fas fa-check-circle me-1"></i>En stock (5)
                </small>
            </div>
        </div>
    </div>
</div>

<div class="col-lg-4 col-md-6 mb-4">
    <div class="card h-100">
        <img src="data:image/jpeg;base64,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" 
             alt="Baobab Sacré" class="card-img-top" style="height: 200px; object-fit: cover;">
        <div class="card-body d-flex flex-column">
            <h5 class="card-title">Baobab Sacré</h5>
            <span class="category-badge mb-2 align-self-start">Sculpture</span>
            <p class="card-text flex-grow-1">Sculpture sur bois représentant le baobab emblématique de Madagascar, symbole de vie.</p>
            <div class="mt-auto">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="price">85,000 MGA</span>
                    <a href="#" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>Voir
                    </a>
                </div>
                <small class="text-success mt-2 d-block">
                    <i class="fas fa-check-circle me-1"></i>En stock (3)
                </small>
            </div>
        </div>
    </div>
</div>

<div class="col-lg-4 col-md-6 mb-4">
    <div class="card h-100">
        <img src="data:image/jpeg;base64,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" 
             alt="Océan Indien" class="card-img-top" style="height: 200px; object-fit: cover;">
        <div class="card-body d-flex flex-column">
            <h5 class="card-title">Océan Indien</h5>
            <span class="category-badge mb-2 align-self-start">Peinture</span>
            <p class="card-text flex-grow-1">Captivante peinture marine illustrant les eaux turquoises de l'océan Indien.</p>
            <div class="mt-auto">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="price">95,000 MGA</span>
                    <a href="#" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>Voir
                    </a>
                </div>
                <small class="text-success mt-2 d-block">
                    <i class="fas fa-check-circle me-1"></i>En stock (7)
                </small>
            </div>
        </div>
    </div>
</div>

<div class="col-lg-4 col-md-6 mb-4">
    <div class="card h-100">
        <img src="data:image/jpeg;base64,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" 
             alt="Art Abstrait" class="card-img-top" style="height: 200px; object-fit: cover;">
        <div class="card-body d-flex flex-column">
            <h5 class="card-title">Rêves Colorés</h5>
            <span class="category-badge mb-2 align-self-start">Art Abstrait</span>
            <p class="card-text flex-grow-1">Œuvre abstraite aux couleurs vibrantes, exprimant l'énergie et l'émotion pure.</p>
            <div class="mt-auto">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="price">75,000 MGA</span>
                    <a href="#" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>Voir
                    </a>
                </div>
                <small class="text-success mt-2 d-block">
                    <i class="fas fa-check-circle me-1"></i>En stock (4)
                </small>
            </div>
        </div>
    </div>
</div>

<div class="col-lg-4 col-md-6 mb-4">
    <div class="card h-100">
        <img src="data:image/jpeg;base64,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" 
             alt="Portrait Traditionnel" class="card-img-top" style="height: 200px; object-fit: cover;">
        <div class="card-body d-flex flex-column">
            <h5 class="card-title">Femme Malgache</h5>
            <span class="category-badge mb-2 align-self-start">Portrait</span>
            <p class="card-text flex-grow-1">Portrait émouvant d'une femme malgache, capturant la beauté et la tradition.</p>
            <div class="mt-auto">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="price">150,000 MGA</span>
                    <a href="#" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>Voir
                    </a>
                </div>
                <small class="text-success mt-2 d-block">
                    <i class="fas fa-check-circle me-1"></i>En stock (2)
                </small>
            </div>
        </div>
    </div>
</div>


                    <?php if(empty($products)): ?>
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="fas fa-palette fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">Aucune œuvre trouvée</h4>
                                <p class="text-muted">
                                    <?php if($total == 0): ?>
                                        Aucun produit n'est disponible pour le moment.
                                        <br><small class="text-info">Ajoutez des produits via l'interface d'administration.</small>
                                    <?php else: ?>
                                        Essayez de modifier vos critères de recherche.
                                    <?php endif; ?>
                                </p>
                                <a href="index.php" class="btn btn-primary">Voir toutes les œuvres</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach($products as $p): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100">
                                    <!-- Image du produit -->
                                    <div class="card-img-top product-image-placeholder" style="height: 200px;">
                                        <i class="fas fa-image fa-3x"></i>
                                    </div>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?php echo htmlspecialchars($p['name']); ?></h5>
                                        <span class="category-badge mb-2 align-self-start">
                                            <?php echo htmlspecialchars($p['category_name']); ?>
                                        </span>
                                        <p class="card-text flex-grow-1">
                                            <?php 
                                            $description = htmlspecialchars($p['description']);
                                            echo strlen($description) > 120 ? substr($description, 0, 120) . '...' : $description;
                                            ?>
                                        </p>
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="price">
                                                    <?php echo number_format($p['price'], 0, ',', ' '); ?> MGA
                                                </span>
                                                <a href="product.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i>Voir
                                                </a>
                                            </div>
                                            <?php if($p['stock'] > 0): ?>
                                                <small class="text-success mt-2 d-block">
                                                    <i class="fas fa-check-circle me-1"></i>En stock (<?php echo $p['stock']; ?>)
                                                </small>
                                            <?php else: ?>
                                                <small class="text-danger mt-2 d-block">
                                                    <i class="fas fa-times-circle me-1"></i>Rupture de stock
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php
                $pages = ceil($total / $perpage);
                if ($pages > 1): 
                ?>
                <nav aria-label="Page navigation" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page == 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => max(1, $page-1)])); ?>" tabindex="-1">
                                <i class="fas fa-chevron-left me-1"></i>Précédent
                            </a>
                        </li>
                        
                        <?php for($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page == $pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => min($pages, $page+1)])); ?>">
                                Suivant<i class="fas fa-chevron-right ms-1"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Newsletter -->
    <section class="newsletter-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <h3 class="mb-3">Restez informé</h3>
                    <p class="mb-4">Inscrivez-vous à notre newsletter pour recevoir des informations sur nos nouvelles créations et expositions.</p>
                    <form class="row g-2 justify-content-center">
                        <div class="col-auto">
                            <input type="email" class="form-control" placeholder="Votre adresse email">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-light">S'abonner</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>Fanilo Art Studio</h5>
                    <p>Créateur d'art inspiré par la nature malgache. Chaque œuvre raconte une histoire et célèbre la beauté de notre environnement.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-tiktok fa-lg"></i></a>
                    </div>
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
                        <li><i class="fas fa-phone me-2"></i> +261 38 26 968 85</li>
                        <li><i class="fas fa-envelope me-2"></i> contact@faniloartstudio.mg</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; 2025 Fanilo Art Studio. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- ==================== -->
    <!-- CHATBOT DIALOGFLOW AMÉLIORÉ -->
    <!-- ==================== -->
    
    <!-- Chatbot Widget -->
    <div id="chatbot-widget">
        <div id="chatbot-toggle">
            <i class="fas fa-comments"></i>
            <span class="chatbot-pulse"></span>
        </div>
        <div id="chatbot-container">
            <div id="chatbot-header">
                <div class="d-flex align-items-center">
                    <div class="chatbot-avatar">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="mb-0">Fanilo Art Studio</h6>
                        <small class="text-muted">Assistant virtuel</small>
                    </div>
                </div>
                <button id="chatbot-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="chatbot-messages">
                <!-- Messages apparaîtront ici -->
            </div>
            
            <!-- BOUTONS DE QUESTIONS PRÉDÉFINIES -->
            <div id="chatbot-quick-questions" class="p-3 border-top bg-light">
                <div class="context-indicator" id="context-indicator">Questions principales</div>
                <div class="row g-2" id="quick-questions-container">
                    <!-- Les boutons seront générés dynamiquement -->
                </div>
            </div>
            
            <div id="chatbot-input">
                <input type="text" id="chatbot-message" placeholder="Posez votre question...">
                <button id="chatbot-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // DIAPORAMA SIMPLE
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.slide');
            const dots = document.querySelectorAll('.nav-dot');
            const prevArrow = document.querySelector('.prev-arrow');
            const nextArrow = document.querySelector('.next-arrow');
            let currentSlide = 0;
            let slideInterval;

            function showSlide(index) {
                slides.forEach(slide => slide.classList.remove('active'));
                dots.forEach(dot => dot.classList.remove('active'));
                
                slides[index].classList.add('active');
                dots[index].classList.add('active');
                currentSlide = index;
            }

            function nextSlide() {
                let next = currentSlide + 1;
                if (next >= slides.length) next = 0;
                showSlide(next);
            }

            function prevSlide() {
                let prev = currentSlide - 1;
                if (prev < 0) prev = slides.length - 1;
                showSlide(prev);
            }

            // Event listeners
            nextArrow.addEventListener('click', nextSlide);
            prevArrow.addEventListener('click', prevSlide);

            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    showSlide(index);
                    resetInterval();
                });
            });

            // Auto-slide
            function startInterval() {
                slideInterval = setInterval(nextSlide, 6000);
            }

            function resetInterval() {
                clearInterval(slideInterval);
                startInterval();
            }

            startInterval();

            // Pause on hover
            const slider = document.querySelector('.hero-slider');
            if (slider) {
                slider.addEventListener('mouseenter', () => clearInterval(slideInterval));
                slider.addEventListener('mouseleave', startInterval);
            }
        });

        // CHATBOT DIALOGFLOW AVEC QUESTIONS PRÉDÉFINIES
        class DialogflowChatbot {
            constructor() {
                this.isOpen = false;
                this.sessionId = this.generateSessionId();
                this.conversationHistory = [];
                this.questionSets = {
                    initial: [
                        "Quel est votre produit le plus cher ?",
                        "Montrez-moi vos dernières œuvres",
                        "Quelles sont vos catégories d'art ?",
                        "Comment vous contacter ?"
                    ],
                    price: [
                        "Quelle est votre fourchette de prix ?",
                        "Avez-vous des œuvres abordables ?",
                        "Quel est le prix moyen ?",
                        "Options de paiement disponibles"
                    ],
                    products: [
                        "Œuvres en rupture de stock",
                        "Statistiques de votre collection",
                        "Artistes disponibles",
                        "Techniques utilisées"
                    ],
                    services: [
                        "Livraison à Madagascar",
                        "Délais de livraison",
                        "Commande sur mesure",
                        "Cadeaux et emballage"
                    ],
                    contact: [
                        "Vos horaires d'ouverture",
                        "Adresse du studio",
                        "Téléphone et email",
                        "Rendez-vous en ligne"
                    ],
                    artists: [
                        "Biographie des artistes",
                        "Œuvres les plus populaires",
                        "Inspirations créatives",
                        "Expositions passées"
                    ],
                    techniques: [
                        "Peinture à l'huile",
                        "Sculpture sur bois",
                        "Art digital",
                        "Techniques mixtes"
                    ],
                    delivery: [
                        "Frais de livraison",
                        "Délais Antananarivo",
                        "Livraison province",
                        "Emballage sécurisé"
                    ]
                };
                this.currentQuestionSet = 'initial';
                this.initializeChatbot();
                setTimeout(() => this.addWelcomeMessage(), 1000);
            }

            generateSessionId() {
                return 'fanilo_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }

            initializeChatbot() {
                // Toggle chatbot
                document.getElementById('chatbot-toggle').addEventListener('click', () => {
                    this.toggleChatbot();
                });

                // Close chatbot
                document.getElementById('chatbot-close').addEventListener('click', () => {
                    this.closeChatbot();
                });

                // Send message
                document.getElementById('chatbot-send').addEventListener('click', () => {
                    this.sendMessage();
                });

                // Enter key to send
                document.getElementById('chatbot-message').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.sendMessage();
                    }
                });
            }

            toggleChatbot() {
                this.isOpen = !this.isOpen;
                document.getElementById('chatbot-container').classList.toggle('active', this.isOpen);
                
                if (this.isOpen) {
                    setTimeout(() => {
                        document.getElementById('chatbot-message').focus();
                    }, 300);
                }
            }

            closeChatbot() {
                this.isOpen = false;
                document.getElementById('chatbot-container').classList.remove('active');
            }

            addWelcomeMessage() {
                this.addMessage({
                    type: 'bot',
                    content: '🎨 **Bonjour ! Je suis l\'assistant Fanilo Art Studio**\n\nJe peux vous aider à découvrir nos œuvres, connaître les prix, la livraison à Madagascar, ou répondre à toutes vos questions artistiques !\n\n**Choisissez une question ci-dessous ⬇️**'
                });
                this.updateQuickQuestions('initial');
            }

            updateQuickQuestions(setType) {
                const container = document.getElementById('quick-questions-container');
                const indicator = document.getElementById('context-indicator');
                const questions = this.questionSets[setType];
                
                container.innerHTML = '';
                
                // Mettre à jour l'indicateur de contexte
                const contextLabels = {
                    initial: 'Questions principales',
                    price: '💰 Questions sur les prix',
                    products: '🎨 Questions sur nos œuvres',
                    services: '🚚 Questions services',
                    contact: '📞 Questions contact',
                    artists: '👨‍🎨 Questions artistes',
                    techniques: '🛠️ Questions techniques',
                    delivery: '📦 Questions livraison'
                };
                
                indicator.textContent = contextLabels[setType] || 'Questions';
                
                questions.forEach((question, index) => {
                    const col = document.createElement('div');
                    col.className = 'col-6';
                    
                    const button = document.createElement('button');
                    button.className = 'quick-question-btn w-100';
                    button.textContent = question;
                    button.addEventListener('click', () => {
                        this.selectQuickQuestion(question);
                    });
                    
                    col.appendChild(button);
                    container.appendChild(col);
                });
            }

            selectQuickQuestion(question) {
                // Simuler la saisie de la question
                document.getElementById('chatbot-message').value = question;
                this.sendMessage();
            }

            determineNextQuestionSet(userMessage, botResponse) {
                const message = userMessage.toLowerCase();
                const response = botResponse.toLowerCase();

                // Détection basée sur le message utilisateur
                if (message.includes('prix') || message.includes('cher') || message.includes('coût') || message.includes('tarif') || message.includes('mga')) {
                    return 'price';
                }
                if (message.includes('œuvre') || message.includes('produit') || message.includes('tableau') || message.includes('art') || message.includes('collection')) {
                    return 'products';
                }
                if (message.includes('artiste') || message.includes('créateur') || message.includes('peintre') || message.includes('sculpteur')) {
                    return 'artists';
                }
                if (message.includes('technique') || message.includes('méthode') || message.includes('procédé') || message.includes('huile') || message.includes('sculpture')) {
                    return 'techniques';
                }
                if (message.includes('livraison') || message.includes('expédition') || message.includes('frais') || message.includes('port') || message.includes('délai')) {
                    return 'delivery';
                }
                if (message.includes('contact') || message.includes('téléphone') || message.includes('email') || message.includes('adresse') || message.includes('heure')) {
                    return 'contact';
                }
                if (message.includes('commande') || message.includes('acheter') || message.includes('achat') || message.includes('panier') || message.includes('cadeau')) {
                    return 'services';
                }

                // Détection basée sur la réponse du bot
                if (response.includes('mga') || response.includes('prix') || response.includes('coût') || response.includes('tarif')) {
                    return 'price';
                }
                if (response.includes('artiste') || response.includes('créateur') || response.includes('signature')) {
                    return 'artists';
                }
                if (response.includes('technique') || response.includes('méthode') || response.includes('procédé')) {
                    return 'techniques';
                }
                if (response.includes('livraison') || response.includes('expédition') || response.includes('frais')) {
                    return 'delivery';
                }
                if (response.includes('contact') || response.includes('téléphone') || response.includes('heure')) {
                    return 'contact';
                }

                // Par défaut, retour aux questions initiales
                return 'initial';
            }

            async sendMessage() {
                const input = document.getElementById('chatbot-message');
                const message = input.value.trim();

                if (message) {
                    // Ajouter message utilisateur
                    this.addMessage({
                        type: 'user',
                        content: message
                    });

                    this.conversationHistory.push({ type: 'user', content: message });
                    input.value = '';
                    this.showTypingIndicator();

                    try {
                        const response = await this.callDialogflowAPI(message);
                        this.hideTypingIndicator();
                        
                        this.addMessage({
                            type: 'bot',
                            content: response.response
                        });

                        this.conversationHistory.push({ type: 'bot', content: response.response });

                        // Mettre à jour les questions en fonction du contexte
                        const nextSet = this.determineNextQuestionSet(message, response.response);
                        if (nextSet !== this.currentQuestionSet) {
                            this.currentQuestionSet = nextSet;
                            this.updateQuickQuestions(nextSet);
                        }

                    } catch (error) {
                        console.error('Chatbot error:', error);
                        this.hideTypingIndicator();
                        this.addMessage({
                            type: 'bot',
                            content: 'Désolé, je rencontre un problème technique. Vous pouvez nous appeler directement au +261 34 00 000 00 ou nous envoyer un email à contact@faniloartstudio.mg'
                        });
                    }
                }
            }

            async callDialogflowAPI(message) {
                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('message', message);
                formData.append('session_id', this.sessionId);

                const response = await fetch('chatbot-dialogflow.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('API call failed');
                }

                return await response.json();
            }

            showTypingIndicator() {
                const messagesContainer = document.getElementById('chatbot-messages');
                const typingDiv = document.createElement('div');
                typingDiv.className = 'message bot typing-indicator';
                typingDiv.innerHTML = `
                    <div class="message-content">
                        <div class="typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                `;
                messagesContainer.appendChild(typingDiv);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }

            hideTypingIndicator() {
                const typingIndicator = document.querySelector('.typing-indicator');
                if (typingIndicator) {
                    typingIndicator.remove();
                }
            }

            addMessage(messageData) {
                const messagesContainer = document.getElementById('chatbot-messages');
                
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${messageData.type}`;
                
                const time = new Date().toLocaleTimeString('fr-FR', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });

                // Formater le message avec sauts de ligne
                const formattedContent = messageData.content.replace(/\n/g, '<br>');

                const contentHTML = `
                    <div class="message-content">
                        ${formattedContent}
                        <div class="message-time">${time}</div>
                    </div>
                `;

                messageDiv.innerHTML = contentHTML;
                messagesContainer.appendChild(messageDiv);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        // Initialisation du chatbot amélioré
        document.addEventListener('DOMContentLoaded', function() {
            window.chatbot = new DialogflowChatbot();
        });
    </script>
</body>
</html>