<?php
require 'config.php';

echo "<h1>🔍 Diagnostic Base de Données</h1>";

// Vérifier les produits
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE status='active'");
$row = mysqli_fetch_assoc($result);
echo "<h3>Produits actifs: " . $row['count'] . "</h3>";

// Afficher tous les produits
$products = mysqli_query($conn, "SELECT * FROM products WHERE status='active'");
echo "<h4>Liste des produits:</h4>";
while($p = mysqli_fetch_assoc($products)) {
    echo "<p>ID: {$p['id']} - {$p['name']} - {$p['price']} MGA - Stock: {$p['stock']}</p>";
}

// Vérifier les catégories
$cats = mysqli_query($conn, "SELECT * FROM categories WHERE status='active'");
echo "<h4>Catégories:</h4>";
while($c = mysqli_fetch_assoc($cats)) {
    echo "<p>ID: {$c['id']} - {$c['nom']}</p>";
}
?>