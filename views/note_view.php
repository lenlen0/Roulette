<?php
// Protection : vérifier qu'on est bien dans le contexte de l'application
if (!defined('APP_CONTEXT')) {
    define('APP_CONTEXT', true);
}

// Si $students n'est pas définie par le contrôleur, essayer de la récupérer
if (!isset($students) || empty($students)) {
    if (isset($student) && $student) {
        $students = [$student];
    } else {
        // Vérifier qu'on a un ID d'étudiant en session
        if (!isset($_SESSION['drawn_student_ids']) && !isset($_SESSION['drawn_student_id'])) {
            $_SESSION['error'] = "Aucun étudiant sélectionné";
            header('Location: index.php');
            exit;
        }
        
        $drawnIds = $_SESSION['drawn_student_ids'] ?? [$_SESSION['drawn_student_id']];
        
        // Récupérer la connexion BDD si pas déjà disponible
        if (!isset($bdd) || !$bdd) {
            // Essayer de récupérer la connexion depuis les globals
            if (isset($GLOBALS['bdd'])) {
                $bdd = $GLOBALS['bdd'];
            } else {
                // Créer une nouvelle connexion
                try {
                    if (!defined('DB_HOST')) {
                        require_once dirname(__DIR__) . '/_config.php';
                    }
                    $bdd = new PDO(
                        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', 
                        DB_USER, 
                        DB_PWD, 
                        array(
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                        )
                    );
                } catch(PDOException $e) {
                    $_SESSION['error'] = "Erreur de connexion à la base de données";
                    header('Location: index.php');
                    exit;
                }
            }
        }
        
        // Récupérer les étudiants
        try {
            if (!class_exists('StudentR')) {
                require_once dirname(__DIR__) . '/models/StudentR.php';
            }
            if (!class_exists('Student')) {
                require_once dirname(__DIR__) . '/models/Student.php';
            }
            
            $studentR = new StudentR($bdd);
            $students = [];
            foreach ($drawnIds as $id) {
                $s = $studentR->getStudentById($id);
                if ($s) {
                    $students[] = $s;
                }
            }
            
            if (empty($students)) {
                $_SESSION['error'] = "Étudiant(s) introuvable(s)";
                unset($_SESSION['drawn_student_id'], $_SESSION['drawn_student_ids']);
                header('Location: index.php');
                exit;
            }
        } catch(Exception $e) {
            $_SESSION['error'] = "Erreur lors de la récupération des étudiants : " . $e->getMessage();
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attribution de Note - Roulette des Étudiants</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container { 
            max-width: 600px; margin: 0 auto;
            background: white; border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #FF6B6B, #4ECDC4);
            color: white; padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0; font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .content { padding: 40px; }
        
        .drawn-student {
            background: linear-gradient(45deg, #FF6B6B, #4ECDC4);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .drawn-student h2 {
            margin: 0 0 10px 0;
            font-size: 1.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .drawn-student h1 {
            margin: 0;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .note-form {
            text-align: center;
        }
        
        .note-form h3 {
            color: #333;
            margin-bottom: 25px;
            font-size: 1.5em;
        }
        
        .note-input-container {
            margin: 30px 0;
        }
        
        .note-input {
            font-size: 2em;
            text-align: center;
            width: 150px;
            padding: 15px;
            border: 3px solid #4ECDC4;
            border-radius: 10px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .note-input:focus {
            outline: none;
            border-color: #FF6B6B;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
            transform: scale(1.05);
        }
        
        .note-label {
            font-size: 1.5em;
            color: #666;
            margin-left: 10px;
            font-weight: bold;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #FF6B6B, #FF8E8E);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, #4ECDC4, #44A08D);
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.4);
        }
        
        .actions {
            margin-top: 30px;
        }
        
        .back-link {
            margin-top: 30px;
            text-align: center;
        }
        
        .back-link a {
            color: #666;
            text-decoration: none;
            font-size: 1.1em;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #4ECDC4;
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .note-range {
            font-size: 0.9em;
            color: #666;
            margin-top: 10px;
        }
        
        @media (max-width: 768px) {
            .content { padding: 20px; }
            
            .drawn-student h1 {
                font-size: 2em;
            }
            
            .note-input {
                font-size: 1.5em;
                width: 120px;
            }
            
            .btn {
                display: block;
                margin: 10px auto;
                width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Attribution de Note</h1>
        </div>
        
        <div class="content">
            <div class="drawn-student">
                <h2>Étudiant(s) tiré(s) au sort</h2>
                <?php foreach ($students as $s): ?>
                    <h1><?= htmlspecialchars($s->getFullName()) ?></h1>
                <?php endforeach; ?>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="message error">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="post" action="index.php?action=assignNote" class="note-form">
                <h3>Attribuer une note</h3>
                
                <div class="note-input-container">
                    <input type="number" name="note" class="note-input" 
                           min="0" max="20" step="0.5" placeholder="0" autofocus>
                    <span class="note-label">/20</span>
                </div>
                
                <div class="note-range">
                    Note comprise entre 0 et 20 (par demi-points). Laisser vide pour passer sans noter.
                </div>
                
                <div class="actions">
                    <button type="submit" class="btn btn-primary">
                        Valider la note
                    </button>
                    <a href="index.php?action=skipStudent" class="btn btn-secondary">
                        Tout passer sans noter
                    </a>
                </div>
            </form>

            <div class="back-link">
                <a href="index.php">← Retour à l'accueil</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Focus automatique sur le champ de saisie
            const noteInput = document.querySelector('.note-input');
            if (noteInput) {
                noteInput.focus();
                noteInput.select();
            }

            // Validation en temps réel
            if (noteInput) {
                noteInput.addEventListener('input', function() {
                    if (this.value === '') {
                        this.style.borderColor = '#4ECDC4';
                        this.style.backgroundColor = '#f8f9fa';
                        return;
                    }
                    const value = parseFloat(this.value);
                    if (value < 0 || value > 20) {
                        this.style.borderColor = '#FF416C';
                        this.style.backgroundColor = '#ffebee';
                    } else {
                        this.style.borderColor = '#4ECDC4';
                        this.style.backgroundColor = '#f8f9fa';
                    }
                });

                // Soumission avec Enter
                noteInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.form.submit();
                    }
                });
            }

            // Animation d'entrée
            const container = document.querySelector('.container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(50px)';
            
            setTimeout(() => {
                container.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>