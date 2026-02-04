<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande confirmée - Fanilo Art Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="card shadow-lg border-0">
                    <div class="card-body py-5">
                        <i class="fas fa-check-circle text-success mb-4" style="font-size: 4rem;"></i>
                        <h1 class="text-success mb-3">Commande Confirmée !</h1>
                        <p class="lead mb-4">Merci pour votre achat chez Fanilo Art Studio</p>
                        <p class="text-muted mb-4">Votre commande a été enregistrée avec succès et sera traitée dans les plus brefs délais.</p>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="index.php" class="btn btn-primary me-md-2">
                                <i class="fas fa-home me-2"></i>Retour à l'accueil
                            </a>
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-palette me-2"></i>Continuer mes achats
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>