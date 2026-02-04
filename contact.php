<?php
session_start();

// Traitement du formulaire de contact
$message_sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simulation d'envoi d'email
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Ici vous ajouterez le vrai code d'envoi d'email
    $message_sent = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Fanilo Art Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a3d5d;
            --secondary: #2a7abf;
            --accent: #eef6fb;
            --light: #f8f9fa;
            --dark: #333;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .contact-hero {
            background: linear-gradient(rgba(26, 61, 93, 0.8), rgba(26, 61, 93, 0.6)), 
                        url('https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .contact-form {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .contact-info {
            background: var(--accent);
            border-radius: 15px;
            padding: 30px;
            height: 100%;
        }
        
        .contact-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        footer {
            background-color: #2c3e50;
            color: white;
            padding: 40px 0 20px;
            margin-top: 60px;
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
                    <li class="nav-item"><a class="nav-link active" href="contact.php">Contact</a></li>
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
    <section class="contact-hero">
        <div class="container">
            <h1 class="display-4 fw-bold">Contactez-nous</h1>
            <p class="lead">Nous sommes à votre écoute pour toutes vos questions</p>
        </div>
    </section>

    <div class="container py-5">
        <?php if($message_sent): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Merci !</strong> Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Formulaire de Contact -->
            <div class="col-md-8">
                <div class="contact-form">
                    <h3 class="text-primary mb-4">Envoyez-nous un message</h3>
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nom complet *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Adresse email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Sujet *</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer le message
                        </button>
                    </form>
                </div>
            </div>

            <!-- Informations de Contact -->
            <div class="col-md-4">
                <div class="contact-info">
                    <h4 class="text-primary mb-4">Nos coordonnées</h4>
                    
                    <div class="mb-4">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5>Adresse</h5>
                        <p>Lot IVC 87 Bis<br>Antananarivo 101<br>Madagascar</p>
                    </div>
                    
                    <div class="mb-4">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h5>Téléphone</h5>
                        <p>+261 34 00 000 00<br>+261 20 22 000 00</p>
                    </div>
                    
                    <div class="mb-4">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5>Email</h5>
                        <p>contact@faniloartstudio.mg<br>ventes@faniloartstudio.mg</p>
                    </div>
                    
                    <div class="mb-4">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h5>Horaires d'ouverture</h5>
                        <p>Lun - Ven: 9h00 - 18h00<br>Sam: 9h00 - 13h00<br>Dim: Fermé</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carte -->
<div class="row mt-5">
    <div class="col-12">
        <div class="contact-form">
            <h4 class="text-primary mb-4">Notre localisation</h4>
            <div class="ratio ratio-16x9">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3777.234298511104!2d47.57696549373389!3d-18.890404857098627!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTjCsDUzJzI1LjUiUyA0N8KwMzQnMzcuMSJF!5e0!3m2!1sfr!2smg!4v1700000000000!5m2!1sfr!2smg" 
                    style="border:0; border-radius: 10px;" 
                    allowfullscreen="" 
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
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
                        <li><i class="fas fa-phone me-2"></i> +261 34 00 000 00</li>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>