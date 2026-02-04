<?php
// config.php - Version avec vraie base de données
session_start();

// Afficher les erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration MySQL pour Linux
$host = 'localhost';
$username = 'fanilo_user';
$password = 'fanilo123';
$database = 'fanilo_art';
$port = 3306;

$conn = mysqli_connect($host, $username, $password, $database, $port);

if (!$conn) {
    die("Erreur de connexion MySQL: " . mysqli_connect_error());
}

// Créer les tables si elles n'existent pas
$tables_sql = [
    "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        description TEXT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        full_description TEXT,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(255),
        category_id INT,
        stock INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        artist VARCHAR(100),
        dimensions VARCHAR(50),
        materials VARCHAR(100),
        year INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($tables_sql as $sql) {
    mysqli_query($conn, $sql);
}// Réparer la table admins
mysqli_query($conn, "DROP TABLE IF EXISTS admins");
mysqli_query($conn, "CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insérer admin avec mot de passe simple (pour test)
mysqli_query($conn, "INSERT INTO admins (username, password, email) VALUES 
    ('admin', 'admin123', 'admin@faniloartstudio.mg')
");

// Insérer les données de base
mysqli_query($conn, "INSERT IGNORE INTO categories (id, nom, description) VALUES 
    (1, 'Peintures', 'Œuvres peintes sur toile'),
    (2, 'Sculptures', 'Sculptures en bois et autres matériaux'),
    (3, 'Photographies', 'Clichés artistiques'),
    (4, 'Dessins', 'Dessins et croquis'),
    (5, 'Art Digital', 'Créations numériques')
");

// Insérer un admin (mot de passe: admin123)
mysqli_query($conn, "INSERT IGNORE INTO admins (username, password, email) VALUES 
    ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@faniloartstudio.mg')
");

mysqli_set_charset($conn, "utf8mb4");
$use_database = true;

// Fonctions utilitaires
function get_category_icon($category_id) {
    $icons = [1 => 'tree', 2 => 'water', 3 => 'mountain', 4 => 'sun', 5 => 'leaf'];
    return $icons[$category_id] ?? 'image';
}

function format_price($price) {
    return number_format($price, 0, ',', ' ') . ' MGA';
}

function secure_data($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}
?>