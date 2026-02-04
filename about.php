<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À propos - Fanilo Art Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a3d5d;
            --secondary: #2a7abf;
            --accent: #eef6fb;
        }
        
        .hero-about {
            background: linear-gradient(rgba(26, 61, 93, 0.8), rgba(26, 61, 93, 0.6)), 
                        url('https://images.unsplash.com/photo-1578301978693-85fa9c0320b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .team-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            height: 100%;
            transition: transform 0.3s ease;
        }
        
        .team-card:hover {
            transform: translateY(-5px);
        }
        
        .team-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--accent);
            margin-bottom: 20px;
        }
        
        .university-badge {
            background: var(--primary);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .value-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            text-align: center;
            height: 100%;
            border-left: 4px solid var(--primary);
        }
        
        .value-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 20px;
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
                    <li class="nav-item"><a class="nav-link" href="boutique.php">Boutique</a></li>
                    <li class="nav-item"><a class="nav-link active" href="about.php">À propos</a></li>
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

    <!-- Hero Section -->
    <section class="hero-about">
        <div class="container">
            <h1 class="display-4 fw-bold">Notre Histoire</h1>
            <p class="lead">Découvrez les passionnés derrière Fanilo Art Studio</p>
        </div>
    </section>

    <div class="container py-5">
        <!-- Notre Histoire -->
        <div class="row align-items-center mb-5">
            <div class="col-md-6">
                <h2 class="text-primary mb-4">L'Émergence d'une Passion</h2>
                <p class="lead">Fanilo Art Studio est le fruit d'une collaboration entre deux étudiants passionnés de l'USVPA.</p>
                <p>Né en 2024 de notre amour commun pour l'art et le design, notre studio représente la fusion entre la créativité artistique et l'innovation numérique.</p>
                <p>Chaque création raconte une histoire, mêlant techniques traditionnelles et approches contemporaines pour offrir des œuvres uniques et authentiques.</p>
                
                <div class="mt-4">
                    <div class="university-badge">
                        <i class="fas fa-graduation-cap me-2"></i>Université USVPA
                    </div>
                    <p class="text-muted">Projet académique et entrepreneurial</p>
                </div>
            </div>
            <div class="col-md-6">
                <img src="https://images.unsplash.com/photo-1541961017774-22349e4a1262?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80" 
                     alt="Atelier Fanilo Art Studio" class="img-fluid rounded shadow">
            </div>
        </div>

        <!-- Nos Valeurs -->
        <div class="row mb-5">
            <div class="col-12 text-center mb-5">
                <h2 class="text-primary">Nos Valeurs</h2>
                <p class="lead">Ce qui guide notre démarche créative</p>
            </div>
            <div class="col-md-4 mb-4">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h4>Innovation</h4>
                    <p>Allier techniques traditionnelles et technologies modernes pour créer des œuvres uniques et contemporaines.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h4>Passion</h4>
                    <p>Chaque création est le résultat d'un travail passionné et d'un engagement artistique authentique.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4>Collaboration</h4>
                    <p>Croiser les compétences et les visions pour enrichir notre processus créatif et nos réalisations.</p>
                </div>
            </div>
        </div>

        <!-- Notre Équipe -->
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="text-primary">Notre Équipe</h2>
                <p class="lead">Les passionnés derrière chaque création</p>
            </div>
            
            <!-- Fanilo -->
            <div class="col-md-6 mb-4">
                <div class="team-card">
                    <?php if(file_exists('photos/fanilo.jpg')): ?>
                        <img src="photos/fanilo.jpg" alt="Fanilo" class="team-photo">
                    <?php else: ?>
                        <div class="team-photo bg-primary d-flex align-items-center justify-content-center text-white mx-auto">
                            <i class="fas fa-user fa-3x"></i>
                        </div>
                    <?php endif; ?>
                    
                    <h4>Fanilo</h4>
                    <div class="university-badge mb-2">
                        <i class="fas fa-graduation-cap me-1"></i>Étudiant USVPA
                    </div>
                    <p class="text-muted mb-3">Designer & Fondateur</p>
                    
                    <p class="mb-3">Passionné par le design et l'art numérique, je mets mes compétences créatives au service de projets innovants qui allient esthétique et fonctionnalité.</p>
                    
                    <div class="specialties">
                        <span class="badge bg-light text-dark me-1">Design</span>
                        <span class="badge bg-light text-dark me-1">Art Digital</span>
                        <span class="badge bg-light text-dark">Création</span>
                    </div>
                </div>
            </div>

            <!-- Luc -->
            <div class="col-md-6 mb-4">
                <div class="team-card">
                    <?php if(file_exists('photos/luc.jpg')): ?>
                        <img src="photos/luc.jpg" alt="Luc" class="team-photo">
                    <?php else: ?>
                        <div class="team-photo bg-success d-flex align-items-center justify-content-center text-white mx-auto">
                            <i class="fas fa-user fa-3x"></i>
                        </div>
                    <?php endif; ?>
                    
                    <h4>Luc</h4>
                    <div class="university-badge mb-2">
                        <i class="fas fa-graduation-cap me-1"></i>Étudiant USVPA
                    </div>
                    <p class="text-muted mb-3">Sponsor & Partenaire</p>
                    
                    <p class="mb-3">Convaincu par le potentiel créatif de Fanilo, j'apporte mon soutien à ce projet innovant qui représente l'excellence de la formation USVPA.</p>
                    
                    <div class="specialties">
                        <span class="badge bg-light text-dark me-1">Sponsoring</span>
                        <span class="badge bg-light text-dark me-1">Management</span>
                        <span class="badge bg-light text-dark">Stratégie</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notre Vision -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="team-card">
                    <h3 class="text-primary text-center mb-4">Notre Vision</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>🎯 Notre Mission</h5>
                            <p>Démocratiser l'art malgache en créant des œuvres accessibles qui célèbrent la richesse culturelle de Madagascar tout en intégrant une approche design moderne et innovante.</p>
                        </div>
                        <div class="col-md-6">
                            <h5>🚀 Notre Ambition</h5>
                            <p>Devenir une référence dans l'art digital malgache, en créant un pont entre les traditions artistiques locales et les technologies créatives contemporaines.</p>
                        </div>
                    </div>
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
                    <p>Projet académique et entrepreneurial de l'USVPA. Création d'art et design innovant.</p>
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
                        <li><a href="boutique.php" class="text-light">Boutique</a></li>
                        <li><a href="about.php" class="text-light">À propos</a></li>
                        <li><a href="contact.php" class="text-light">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i> Antananarivo, Madagascar</li>
                        <li><i class="fas fa-university me-2"></i> Université USVPA</li>
                        <li><i class="fas fa-envelope me-2"></i> contact@faniloartstudio.mg</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; 2025 Fanilo Art Studio - Projet USVPA. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>