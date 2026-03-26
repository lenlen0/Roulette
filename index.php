<?php
// Démarrage de session sécurisé
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration des erreurs pour le développement
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclusion des fichiers nécessaires
$required_files = [
    '_config.php',
    'connexion_sql.php',
    'models/Student.php',
    'models/StudentR.php',
    'controllers/RouletteController.php'
];

foreach ($required_files as $file) {
    // Chercher le fichier dans plusieurs emplacements possibles
    $found = false;
    $possible_paths = [$file, './' . $file];
    
    // Ajouter des chemins alternatifs si les dossiers n'existent pas
    if (strpos($file, 'models/') === 0) {
        $possible_paths[] = str_replace('models/', '', $file);
    }
    if (strpos($file, 'controllers/') === 0) {
        $possible_paths[] = str_replace('controllers/', '', $file);
    }
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        die("Erreur : Le fichier '$file' est introuvable. Vérifiez l'arborescence des fichiers.");
    }
}

// Vérification de la connexion à la base de données
if (!isset($bdd)) {
    die("Erreur : Connexion à la base de données non établie.");
}

try {
    // Instancier le contrôleur
    $controller = new RouletteController($bdd);

    // Router simple avec validation
    $action = $_GET['action'] ?? 'index';
    $allowed_actions = ['index', 'selectClass', 'drawStudent', 'assignNote', 'skipStudent', 'resetPassages', 'resetAll'];
    
    if (!in_array($action, $allowed_actions)) {
        $action = 'index';
    }

    switch ($action) {
        case 'selectClass':
            $controller->selectClass();
            break;
        case 'drawStudent':
            $controller->drawStudent();
            break;
        case 'assignNote':
            $controller->assignNote();
            break;
        case 'skipStudent':
            $controller->skipStudent();
            break;
        case 'resetPassages':
            $controller->resetPassages();
            break;
        case 'resetAll':
            $controller->resetAll();
            break;
        default:
            $controller->index();
            break;
    }
} catch (Exception $e) {
    // Gestion globale des erreurs
    $_SESSION['error'] = "Erreur système : " . $e->getMessage();
    
    // Affichage d'urgence si le contrôleur ne peut pas être créé
    if (!isset($controller)) {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Erreur - Roulette des Étudiants</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 50px; text-align: center; }
                .error { color: red; background: #ffebee; padding: 20px; border-radius: 10px; border: 1px solid #ffcdd2; }
                .info { color: blue; background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <h1>🚨 Erreur Système</h1>
            <div class="error">
                <h3>Une erreur critique est survenue :</h3>
                <p><?= htmlspecialchars($e->getMessage()) ?></p>
            </div>
            
            <div class="info">
                <h4>Vérifications à effectuer :</h4>
                <ul style="text-align: left; max-width: 600px; margin: 0 auto;">
                    <li>La base de données est-elle créée et accessible ?</li>
                    <li>Les paramètres de connexion dans _config.php sont-ils corrects ?</li>
                    <li>Tous les fichiers sont-ils présents ?</li>
                    <li>Le serveur web est-il configuré pour PHP ?</li>
                </ul>
            </div>
            
            <p><a href="index.php" style="color: #007bff;">🔄 Réessayer</a></p>
        </body>
        </html>
        <?php
        exit;
    } else {
        // Rediriger vers la page d'accueil avec le message d'erreur
        header('Location: index.php');
        exit;
    }
}
?>