<?php
require 'config.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

$success = $error = '';

// Créer la table orders
$sql_orders = "CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    customer_address TEXT NOT NULL,
    notes TEXT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql_orders)) {
    $success .= "Table 'orders' créée avec succès.<br>";
} else {
    $error .= "Erreur création table 'orders': " . mysqli_error($conn) . "<br>";
}

// Créer la table order_items
$sql_items = "CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    product_name VARCHAR(255) NOT NULL,
    artist VARCHAR(255),
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql_items)) {
    $success .= "Table 'order_items' créée avec succès.<br>";
} else {
    $error .= "Erreur création table 'order_items': " . mysqli_error($conn) . "<br>";
}

// Ajouter des données d'exemple
$sample_order = "INSERT INTO orders (customer_name, customer_email, customer_phone, customer_address, total_amount, status) 
VALUES 
('Marie Lambert', 'marie@email.com', '0345678901', '45 Avenue de l\'Indépendance, Antananarivo', 250000, 'pending'),
('Paul Martin', 'paul@email.com', '0329876543', 'Lot IVC 56, Antsirabe', 180000, 'confirmed'),
('Sophie Ranaivo', 'sophie@email.com', '0331234567', 'Immeuble Galaxy, Andraharo', 320000, 'shipped')";

if (mysqli_query($conn, $sample_order)) {
    $success .= "Commandes d'exemple ajoutées.<br>";
    
    // Ajouter des articles
    $sample_items = "INSERT INTO order_items (order_id, product_id, product_name, artist, quantity, unit_price) 
    VALUES 
    (1, 1, 'Tableau Abstrait', 'Fanilo', 1, 150000),
    (1, 2, 'Sculpture Bois', 'Jean', 1, 100000),
    (2, 3, 'Peinture Paysage', 'Marie', 1, 180000),
    (3, 1, 'Tableau Abstrait', 'Fanilo', 2, 150000)";
    
    if (mysqli_query($conn, $sample_items)) {
        $success .= "Articles d'exemple ajoutés.<br>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création Tables - Fanilo Art Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Création des Tables de Commandes</h1>
        
        <?php if($success): ?>
            <div class="alert alert-success">
                <h4>✅ Succès :</h4>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger">
                <h4>❌ Erreurs :</h4>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="orders.php" class="btn btn-primary">Voir les commandes</a>
            <a href="dashboard.php" class="btn btn-secondary">Retour au tableau de bord</a>
        </div>
    </div>
</body>
</html>