<?php
// chatbot-dialogflow.php - VERSION COMPLÈTE
require 'config.php';

header('Content-Type: application/json');

if ($_POST['action'] === 'send_message') {
    $user_message = trim($_POST['message']);
    $session_id = $_POST['session_id'] ?? generateSessionId();
    
    try {
        $response = callMegaBot($user_message);
        
        echo json_encode([
            'success' => true,
            'response' => $response,
            'session_id' => $session_id
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'response' => 'Désolé, service temporairement indisponible. Veuillez reformuler votre question.'
        ]);
    }
}

function generateSessionId() {
    return 'session_' . uniqid() . '_' . time();
}

function callMegaBot($message) {
    $message = strtolower(trim($message));
    
    // ============================================================================
    // DICTIONNAIRE COMPLET
    // ============================================================================
    
    $keywords = [
        // PRODUITS PREMIUM
        'plus cher' => 'get_most_expensive',
        'premium' => 'get_most_expensive',
        'luxe' => 'get_most_expensive',
        'cher' => 'get_most_expensive',

        // NOUVEAUTÉS
        'dernier' => 'get_latest_products',
        'récent' => 'get_latest_products',
        'nouveau' => 'get_latest_products',
        'nouvelle' => 'get_latest_products',

        // CATÉGORIES
        'catégorie' => 'get_categories',
        'type' => 'get_categories',
        'genre' => 'get_categories',

        // STATISTIQUES
        'statistique' => 'get_stats',
        'nombre' => 'get_stats',
        'quantité' => 'get_stats',
        'collection' => 'get_stats',

        // RUPTURE STOCK
        'rupture' => 'get_out_of_stock',
        'stock' => 'get_out_of_stock',
        'disponible' => 'get_out_of_stock',

        // PRIX
        'prix' => 'get_price_range',
        'coût' => 'get_price_range',
        'tarif' => 'get_price_range',
        'budget' => 'get_price_range',

        // PROMOTIONS
        'promo' => 'get_promotions',
        'réduction' => 'get_promotions',
        'abordable' => 'get_promotions',

        // LIVRAISON
        'livraison' => 'get_delivery_info',
        'expédition' => 'get_delivery_info',
        'frais de port' => 'get_delivery_info',

        // CONTACT
        'contact' => 'get_contact_info',
        'téléphone' => 'get_contact_info',
        'email' => 'get_contact_info',
        'adresse' => 'get_contact_info',

        // COMMANDES
        'commande' => 'get_order_info',
        'acheter' => 'get_order_info',
        'achat' => 'get_order_info',

        // HORAIRES
        'heure' => 'get_opening_hours',
        'ouvert' => 'get_opening_hours',
        'horaire' => 'get_opening_hours',

        // ARTISTES
        'artiste' => 'get_artists',
        'créateur' => 'get_artists',
        'peintre' => 'get_artists',

        // TECHNIQUES
        'technique' => 'get_techniques',
        'méthode' => 'get_techniques',
        'procédé' => 'get_techniques',

        // SALUTATIONS
        'bonjour' => 'greet_hello',
        'salut' => 'greet_hello',
        'coucou' => 'greet_hello',

        // REMERCIEMENTS
        'merci' => 'greet_thanks',
        'remerc' => 'greet_thanks',

        // AU REVOIR
        'au revoir' => 'greet_goodbye',
        'bye' => 'greet_goodbye',
    ];

    // RECHERCHE INTELLIGENTE
    $matched_action = null;
    $best_match_score = 0;
    
    foreach ($keywords as $keyword => $action) {
        if (strpos($message, $keyword) !== false) {
            $score = strlen($keyword) * 10;
            if ($score > $best_match_score) {
                $best_match_score = $score;
                $matched_action = $action;
            }
        }
    }

    if ($best_match_score >= 2 && function_exists($matched_action)) {
        return call_user_func($matched_action);
    } else {
        return show_help();
    }
}

// ============================================================================
// FONCTIONS DE RÉPONSES COMPLÈTES
// ============================================================================

function get_most_expensive() {
    global $conn;
    $sql = "SELECT name, price, description, category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status='active' 
            ORDER BY price DESC LIMIT 1";
    $result = mysqli_query($conn, $sql);
    
    if ($product = mysqli_fetch_assoc($result)) {
        return "💰 **NOTRE ŒUVRE LA PLUS PRESTIGIEUSE**\n\n" .
               "🎨 **{$product['name']}**\n" .
               "💎 **Prix** : " . number_format($product['price'], 0, ',', ' ') . " MGA\n" .
               "📂 **Catégorie** : {$product['category_name']}\n" .
               "📝 **Description** : " . (strlen($product['description']) > 120 ? 
                   substr($product['description'], 0, 120) . "..." : $product['description']) .
               "\n\n🌟 *Cette œuvre exceptionnelle représente le sommet de notre collection*";
    }
    return "Aucune œuvre premium n'est actuellement disponible dans notre collection.";
}

function get_latest_products() {
    global $conn;
    $sql = "SELECT name, price, category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status='active' 
            ORDER BY created_at DESC LIMIT 3";
    $result = mysqli_query($conn, $sql);
    
    $response = "🆕 **NOS DERNIÈRES CRÉATIONS**\n\n";
    while($row = mysqli_fetch_assoc($result)) {
        $response .= "• **{$row['name']}** - " . number_format($row['price'], 0, ',', ' ') . " MGA\n";
        $response .= "  📂 {$row['category_name']}\n\n";
    }
    $response .= "🎨 *Découvrez ces œuvres fraîchement ajoutées à notre collection*";
    return $response;
}

function get_categories() {
    global $conn;
    $sql = "SELECT c.nom, COUNT(p.id) as count 
            FROM categories c 
            LEFT JOIN products p ON c.id = p.category_id AND p.status='active' 
            GROUP BY c.id";
    $result = mysqli_query($conn, $sql);
    
    $response = "🎨 **NOS CATÉGORIES D'ART**\n\n";
    while($row = mysqli_fetch_assoc($result)) {
        $response .= "• **{$row['nom']}** - {$row['count']} œuvre(s)\n";
    }
    $response .= "\n🌈 *Chaque catégorie raconte une histoire unique*";
    return $response;
}

function get_stats() {
    global $conn;
    $sql_total = "SELECT COUNT(*) as total FROM products WHERE status='active'";
    $sql_categories = "SELECT COUNT(DISTINCT category_id) as categories FROM products WHERE status='active'";
    $sql_prix = "SELECT MIN(price) as min, MAX(price) as max FROM products WHERE status='active'";
    
    $total = mysqli_fetch_assoc(mysqli_query($conn, $sql_total))['total'];
    $categories = mysqli_fetch_assoc(mysqli_query($conn, $sql_categories))['categories'];
    $prix = mysqli_fetch_assoc(mysqli_query($conn, $sql_prix));
    
    return "📊 **STATISTIQUES DE NOTRE GALERIE**\n\n" .
           "• **Œuvres totales** : {$total}\n" .
           "• **Catégories actives** : {$categories}\n" .
           "• **Prix minimum** : " . number_format($prix['min'], 0, ',', ' ') . " MGA\n" .
           "• **Prix maximum** : " . number_format($prix['max'], 0, ',', ' ') . " MGA\n\n" .
           "📈 *Une collection riche et diversifiée*";
}

function get_price_range() {
    global $conn;
    $sql = "SELECT MIN(price) as min, MAX(price) as max, AVG(price) as avg FROM products WHERE status='active'";
    $prix = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    
    return "💰 **FOURCHETTE DE PRIX**\n\n" .
           "• **À partir de** : " . number_format($prix['min'], 0, ',', ' ') . " MGA\n" .
           "• **Jusqu'à** : " . number_format($prix['max'], 0, ',', ' ') . " MGA\n" .
           "• **Prix moyen** : " . number_format($prix['avg'], 0, ',', ' ') . " MGA\n\n" .
           "💡 *Les prix varient selon la technique, les dimensions et la complexité*";
}

function get_promotions() {
    global $conn;
    $sql = "SELECT name, price, category_name FROM products WHERE status='active' ORDER BY price ASC LIMIT 3";
    $result = mysqli_query($conn, $sql);
    
    $response = "🎁 **NOS ŒUVRES LES PLUS ACCESSIBLES**\n\n";
    while($row = mysqli_fetch_assoc($result)) {
        $response .= "• **{$row['name']}** - " . number_format($row['price'], 0, ',', ' ') . " MGA\n";
        $response .= "  📂 {$row['category_name']}\n\n";
    }
    $response .= "💎 *Parfait pour débuter une collection d'art*";
    return $response;
}

function get_out_of_stock() {
    global $conn;
    $sql = "SELECT name, category_name FROM products WHERE stock = 0 AND status='active' LIMIT 3";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $response = "⚠️ **ŒUVRES EN RUPTURE DE STOCK**\n\n";
        while($row = mysqli_fetch_assoc($result)) {
            $response .= "• {$row['name']} ({$row['category_name']})\n";
        }
        $response .= "\n📞 *Contactez-nous pour connaître les délais de réapprovisionnement*";
    } else {
        $response = "✅ **TOUTES NOS ŒUVRES SONT DISPONIBLES**\n\nParfait pour commencer votre collection dès maintenant !";
    }
    return $response;
}

function get_delivery_info() {
    return "🚚 **LIVRAISON À MADAGASCAR**\n\n" .
           "• **Antananarivo** : Livraison gratuite sous 48h\n" .
           "• **Province** : 3-5 jours (frais variables)\n" .
           "• **Emballage professionnel** inclus\n" .
           "• **Livraison internationale** disponible sur demande\n\n" .
           "📞 *Contactez-nous pour un devis personnalisé*";
}

function get_contact_info() {
    return "📞 **CONTACTEZ NOTRE STUDIO**\n\n" .
           "• **Téléphone** : +261 34 00 000 00\n" .
           "• **Email** : contact@faniloartstudio.mg\n" .
           "• **Adresse** : Antananarivo, Madagascar\n" .
           "• **Horaires** : Lundi - Samedi, 9h-18h\n\n" .
           "💬 *Nous répondons à toutes vos questions sous 24h*";
}

function get_order_info() {
    return "🛒 **PROCESSUS DE COMMANDE**\n\n" .
           "1. **Parcourez** notre galerie en ligne\n" .
           "2. **Cliquez** sur 'Voir détails' sur l'œuvre choisie\n" .
           "3. **Ajoutez** au panier\n" .
           "4. **Finalisez** votre commande\n\n" .
           "💳 **Paiements acceptés** :\n" .
           "• Virement bancaire\n" .
           "• Mobile Money\n" .
           "• Paiement sécurisé en ligne\n\n" .
           "🛡️ *Transaction 100% sécurisée*";
}

function get_opening_hours() {
    return "🕒 **HORAIRES D'OUVERTURE**\n\n" .
           "• **Lundi - Vendredi** : 9h00 - 18h00\n" .
           "• **Samedi** : 9h00 - 16h00\n" .
           "• **Dimanche** : Fermé\n\n" .
           "📞 Service client disponible pendant ces horaires";
}

function get_artists() {
    return "👨‍🎨 **NOS ARTISTES**\n\n" .
           "• **Fanilo** - Fondateur & artiste principal\n" .
           "• **Marie** - Spécialiste aquarelle\n" .
           "• **Jean** - Sculpteur sur bois\n" .
           "• **Sophie** - Artiste digitale\n\n" .
           "🎨 *Des talents variés pour des œuvres uniques*";
}

function get_techniques() {
    return "🛠️ **TECHNIQUES ARTISTIQUES**\n\n" .
           "• **Peinture à l'huile** - Tradition et profondeur\n" .
           "• **Aquarelle** - Légèreté et transparence\n" .
           "• **Sculpture bois** - Artisanat malgache\n" .
           "• **Art digital** - Modernité et innovation\n" .
           "• **Techniques mixtes** - Créativité sans limites\n\n" .
           "✨ *Chaque technique apporte son caractère unique*";
}

// FONCTIONS DE SALUTATION
function greet_hello() {
    return "👋 **Bonjour ! Bienvenue chez Fanilo Art Studio**\n\n" .
           "Je suis votre assistant virtuel. Je peux vous aider à :\n" .
           "• Découvrir nos œuvres d'art\n" .
           "• Connaître les prix et promotions\n" .
           "• Organiser la livraison\n" .
           "• Répondre à toutes vos questions\n\n" .
           "**Comment puis-je vous aider aujourd'hui ?**";
}

function greet_thanks() {
    return "😊 **Je vous en prie !**\n\n" .
           "N'hésitez pas si vous avez d'autres questions.\n" .
           "Bonne journée et à bientôt ! 🌟";
}

function greet_goodbye() {
    return "👋 **Au revoir et merci !**\n\n" .
           "Merci d'avoir visité Fanilo Art Studio.\n" .
           "À très bientôt pour de nouvelles découvertes artistiques ! 🎨";
}

function show_help() {
    return "🎨 **ASSISTANT FANILO ART STUDIO**\n\n" .
           "Je peux vous renseigner sur :\n\n" .
           "**🛍️ CATALOGUE & ŒUVRES**\n" .
           "• Produit le plus cher\n" .
           "• Dernières créations\n" .
           "• Catégories disponibles\n" .
           "• Statistiques collection\n\n" .
           "**💰 PRIX & ACHAT**\n" .
           "• Fourchette de prix\n" .
           "• Œuvres abordables\n" .
           "• Processus commande\n" .
           "• Paiements acceptés\n\n" .
           "**🚚 SERVICES**\n" .
           "• Livraison Madagascar\n" .
           "• Contact et horaires\n" .
           "• Artistes et techniques\n\n" .
           "**💬 Posez-moi votre question !**";
}
?>