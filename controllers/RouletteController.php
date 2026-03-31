<?php
class RouletteController {
    private $StudentR;
    
    public function __construct($database) {
        $this->StudentR = new StudentR($database);
    }
    
    /**
     * Page d'accueil - Affiche la roulette et les listes d'étudiants
     */
    public function index() {
        try {
            $classes = $this->StudentR->getAllClasses();
            
            // Vérifier si une classe est déjà sélectionnée via GET ou POST
            if (isset($_GET['class']) && !empty($_GET['class'])) {
                $_SESSION['selected_class'] = $_GET['class'];
            } elseif (isset($_POST['class']) && !empty($_POST['class'])) {
                $_SESSION['selected_class'] = $_POST['class'];
            }
            
            // Si pas de classe en session, prendre la première disponible
            $selectedClass = $_SESSION['selected_class'] ?? null;
            if (!$selectedClass && !empty($classes)) {
                $selectedClass = $classes[0];
                $_SESSION['selected_class'] = $selectedClass;
            }
            
            $availableStudents = [];
            $passedStudents = [];
            
            if ($selectedClass && in_array($selectedClass, $classes)) {
                $availableStudents = $this->StudentR->getAvailableStudentsByClass($selectedClass);
                $passedStudents = $this->StudentR->getPassedStudentsByClass($selectedClass);
            } else {
                // Classe invalide, réinitialiser
                unset($_SESSION['selected_class']);
                $selectedClass = null;
            }
            
            // Créer le dossier views s'il n'existe pas
            if (!is_dir('views')) {
                mkdir('views', 0755, true);
            }
            
            // Chercher le fichier de vue
            if (file_exists('views/roulette_view.php')) {
                require_once 'views/roulette_view.php';
            } else if (file_exists('roulette_view.php')) {
                require_once 'roulette_view.php';
            } else {
                $this->renderRouletteView($classes, $selectedClass, $availableStudents, $passedStudents);
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors du chargement : " . $e->getMessage();
            $this->renderRouletteView([], null, [], []);
        }
    }
    
    /**
     * Sélectionne une classe
     */
    public function selectClass() {
        if (isset($_POST['class']) && !empty(trim($_POST['class']))) {
            $_SESSION['selected_class'] = trim($_POST['class']);
            $_SESSION['message'] = "Classe sélectionnée : " . $_SESSION['selected_class'];
        } else {
            $_SESSION['error'] = "Veuillez sélectionner une classe valide";
        }
        header('Location: index.php');
        exit;
    }
    
    /**
     * Tire au sort un étudiant
     */
    public function drawStudent() {
        // Vérifier la classe sélectionnée
        $selectedClass = $_SESSION['selected_class'] ?? null;
        
        if (!$selectedClass) {
            $_SESSION['error'] = "Aucune classe sélectionnée. Veuillez d'abord choisir une classe.";
            header('Location: index.php');
            exit;
        }
        
        try {
            // Vérifier que la classe existe toujours
            $classes = $this->StudentR->getAllClasses();
            if (!in_array($selectedClass, $classes)) {
                $_SESSION['error'] = "La classe sélectionnée n'existe plus.";
                unset($_SESSION['selected_class']);
                header('Location: index.php');
                exit;
            }
            
            $availableStudents = $this->StudentR->getAvailableStudentsByClass($selectedClass);
            
            if (empty($availableStudents)) {
                $_SESSION['message'] = "Aucun étudiant disponible pour le tirage dans la classe " . $selectedClass . " !";
                header('Location: index.php');
                exit;
            }
            
            // Tirage au sort (simple ou groupe)
            if (isset($_POST['group_draw']) && $_POST['group_draw'] === '1' && isset($_POST['group_size'])) {
                $groupSize = (int)$_POST['group_size'];
                $availableCount = count($availableStudents);
                
                // Sécurité: vérifier que la taille du groupe est valide
                if ($groupSize < 1 || $groupSize > $availableCount) {
                    $groupSize = min(1, $availableCount);
                }
                
                if ($groupSize > 1) {
                    // Tirer le groupe
                    $keys = array_rand($availableStudents, $groupSize);
                    if (!is_array($keys)) {
                        $keys = [$keys];
                    }
                    
                    $drawnIds = [];
                    foreach ($keys as $key) {
                        $drawnIds[] = $availableStudents[$key]->getId();
                    }
                    
                    $_SESSION['group_draw_ids'] = $drawnIds;
                    $_SESSION['group_draw_total'] = count($drawnIds);
                    $_SESSION['group_draw_current'] = 1;
                    
                    // Mettre le premier ID comme étudiant en cours
                    $_SESSION['drawn_student_id'] = array_shift($_SESSION['group_draw_ids']);
                    $drawnStudent = $this->StudentR->getStudentById($_SESSION['drawn_student_id']);
                } else {
                    $randomIndex = random_int(0, count($availableStudents) - 1);
                    $drawnStudent = $availableStudents[$randomIndex];
                    $_SESSION['drawn_student_id'] = $drawnStudent->getId();
                    unset($_SESSION['group_draw_ids'], $_SESSION['group_draw_total'], $_SESSION['group_draw_current']);
                }
            } else {
                // Tirage simple
                $randomIndex = random_int(0, count($availableStudents) - 1);
                $drawnStudent = $availableStudents[$randomIndex];
                $_SESSION['drawn_student_id'] = $drawnStudent->getId();
                unset($_SESSION['group_draw_ids'], $_SESSION['group_draw_total'], $_SESSION['group_draw_current']);
            }
            
            // Passer l'étudiant à la vue
            $student = $drawnStudent;
            
            // Chercher le fichier de vue
            if (file_exists('views/note_view.php')) {
                require_once 'views/note_view.php';
            } else if (file_exists('note_view.php')) {
                require_once 'note_view.php';
            } else {
                $this->renderNoteView($drawnStudent);
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors du tirage : " . $e->getMessage();
            header('Location: index.php');
            exit;
        }
    }
    
    /**
     * Attribue une note à l'étudiant tiré au sort
     */
    public function assignNote() {
        if (!isset($_SESSION['drawn_student_id']) || !isset($_POST['note'])) {
            $_SESSION['error'] = "Données manquantes pour l'attribution de note";
            header('Location: index.php');
            exit;
        }
        
        try {
            // Récupérer l'étudiant depuis la base de données
            $student = $this->StudentR->getStudentById($_SESSION['drawn_student_id']);
            
            if (!$student) {
                $_SESSION['error'] = "Étudiant introuvable";
                unset($_SESSION['drawn_student_id']);
                header('Location: index.php');
                exit;
            }
            
            $note = floatval($_POST['note']);
            
            // Valider la note (entre 0 et 20)
            if ($note < 0 || $note > 20) {
                $_SESSION['error'] = "La note doit être comprise entre 0 et 20.";
                
                // Repasser l'étudiant à la vue pour réaffichage
                if (file_exists('views/note_view.php')) {
                    require_once 'views/note_view.php';
                } else if (file_exists('note_view.php')) {
                    require_once 'note_view.php';
                } else {
                    $this->renderNoteView($student);
                }
                return;
            }
            
            // Mettre à jour la base de données
            $this->StudentR->updateStudentNote($student->getId(), $note);
            
            $_SESSION['message'] = "Note attribuée à " . $student->getFullName() . " : " . number_format($note, 1) . "/20";
            unset($_SESSION['drawn_student_id']);
            
            // Si on est en tirage de groupe et qu'il reste des élèves
            if (!empty($_SESSION['group_draw_ids'])) {
                $_SESSION['group_draw_current']++;
                header('Location: index.php?action=continueGroupDraw');
                exit;
            }
            
            // Sinon on a fini ou c'était un tirage simple
            unset($_SESSION['group_draw_total'], $_SESSION['group_draw_current']);
            
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de l'attribution de la note : " . $e->getMessage();
            header('Location: index.php');
            exit;
        }
    }
    
    /**
     * Passe un étudiant sans lui attribuer de note
     */
    public function skipStudent() {
        if (!isset($_SESSION['drawn_student_id'])) {
            $_SESSION['error'] = "Aucun étudiant à passer";
            header('Location: index.php');
            exit;
        }
        
        try {
            // Récupérer l'étudiant depuis la base de données
            $student = $this->StudentR->getStudentById($_SESSION['drawn_student_id']);
            
            if (!$student) {
                $_SESSION['error'] = "Étudiant introuvable";
                unset($_SESSION['drawn_student_id']);
                header('Location: index.php');
                exit;
            }
            
            // Marquer comme passé sans note
            $this->StudentR->markStudentAsPassed($student->getId());
            
            $_SESSION['message'] = $student->getFullName() . " a été passé sans note.";
            unset($_SESSION['drawn_student_id']);
            
            // Si on est en tirage de groupe et qu'il reste des élèves
            if (!empty($_SESSION['group_draw_ids'])) {
                $_SESSION['group_draw_current']++;
                header('Location: index.php?action=continueGroupDraw');
                exit;
            }
            
            // Sinon on a fini ou c'était un tirage simple
            unset($_SESSION['group_draw_total'], $_SESSION['group_draw_current']);
            
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors du passage de l'étudiant : " . $e->getMessage();
            header('Location: index.php');
            exit;
        }
    }
    
    /**
     * Continue le tirage au sort d'un groupe d'étudiants
     */
    public function continueGroupDraw() {
        if (!isset($_SESSION['group_draw_ids']) || empty($_SESSION['group_draw_ids'])) {
            $_SESSION['error'] = "Aucun tirage de groupe en cours.";
            header('Location: index.php');
            exit;
        }
        
        // Prendre le prochain étudiant dans la liste
        $_SESSION['drawn_student_id'] = array_shift($_SESSION['group_draw_ids']);
        
        try {
            if (file_exists('views/note_view.php')) {
                require_once 'views/note_view.php';
            } else if (file_exists('note_view.php')) {
                require_once 'note_view.php';
            } else {
                $student = $this->StudentR->getStudentById($_SESSION['drawn_student_id']);
                $this->renderNoteView($student);
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de la suite du tirage : " . $e->getMessage();
            header('Location: index.php');
            exit;
        }
    }
    
    /**
     * Remet à zéro les passages pour la classe sélectionnée
     */
    public function resetPassages() {
        $selectedClass = $_SESSION['selected_class'] ?? null;
        if (!$selectedClass) {
            $_SESSION['error'] = "Aucune classe sélectionnée";
            header('Location: index.php');
            exit;
        }
        
        try {
            $this->StudentR->resetPassages($selectedClass);
            $_SESSION['message'] = "Les passages ont été réinitialisés pour la classe " . $selectedClass;
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de la réinitialisation : " . $e->getMessage();
        }
        
        header('Location: index.php');
        exit;
    }
    
    /**
     * Remet à zéro les passages et les notes pour la classe sélectionnée
     */
    public function resetAll() {
        $selectedClass = $_SESSION['selected_class'] ?? null;
        if (!$selectedClass) {
            $_SESSION['error'] = "Aucune classe sélectionnée";
            header('Location: index.php');
            exit;
        }
        
        try {
            $this->StudentR->resetPassagesAndNotes($selectedClass);
            $_SESSION['message'] = "Les passages et notes ont été réinitialisés pour la classe " . $selectedClass;
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de la réinitialisation : " . $e->getMessage();
        }
        
        header('Location: index.php');
        exit;
    }
    
    /**
     * Rendu de la vue roulette si le fichier n'existe pas
     */
    private function renderRouletteView($classes, $selectedClass, $availableStudents, $passedStudents) {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Roulette des Étudiants</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0; }
                .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
                .btn { padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
                .btn-danger { background: #dc3545; }
                .student-list { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
            </style>
        </head>
        <body>
            <h1>🎲 Roulette des Étudiants</h1>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="success"><?= htmlspecialchars($_SESSION['message']) ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="error"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <h3>Sélection de la classe</h3>
            <form method="post" action="index.php?action=selectClass">
                <select name="class" onchange="this.form.submit()">
                    <option value="">-- Choisir une classe --</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= htmlspecialchars($class) ?>" 
                                <?= $selectedClass === $class ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if ($selectedClass): ?>
                <h3>Classe: <?= htmlspecialchars($selectedClass) ?></h3>
                <p>Étudiants restants: <?= count($availableStudents) ?></p>
                
                <?php if (count($availableStudents) > 0): ?>
                    <form method="post" action="index.php?action=drawStudent">
                        <button type="submit" class="btn">🎲 Tirer au sort !</button>
                    </form>
                <?php else: ?>
                    <p>✅ Tous les étudiants sont passés !</p>
                <?php endif; ?>

                <div class="student-list">
                    <h4>👥 Étudiants restants (<?= count($availableStudents) ?>)</h4>
                    <?php foreach ($availableStudents as $student): ?>
                        <div><?= htmlspecialchars($student->getFullName()) ?></div>
                    <?php endforeach; ?>
                </div>

                <div class="student-list">
                    <h4>✅ Étudiants passés (<?= count($passedStudents) ?>)</h4>
                    <?php foreach ($passedStudents as $student): ?>
                        <div>
                            <?= htmlspecialchars($student->getFullName()) ?>
                            <?php if ($student->getNoteaddition() !== null): ?>
                                - <?= number_format($student->getNoteaddition(), 1) ?>/20
                            <?php else: ?>
                                - Passé
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button onclick="if(confirm('Réinitialiser les passages ?')) location.href='index.php?action=resetPassages'" 
                        class="btn">🔄 Réinitialiser les passages</button>
                <button onclick="if(confirm('Réinitialiser les passages ET les notes ?')) location.href='index.php?action=resetAll'" 
                        class="btn btn-danger">🗑️ Tout réinitialiser</button>
            <?php endif; ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * Rendu de la vue note si le fichier n'existe pas
     */
    private function renderNoteView($student) {
        // Si on a seulement l'ID, récupérer l'étudiant
        if (!$student && isset($_SESSION['drawn_student_id'])) {
            $student = $this->StudentR->getStudentById($_SESSION['drawn_student_id']);
        }
        
        if (!$student) {
            $_SESSION['error'] = "Erreur: étudiant introuvable";
            header('Location: index.php');
            exit;
        }
        
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Attribution de Note</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; text-align: center; }
                .student-name { font-size: 2em; color: #007bff; margin: 20px 0; }
                .note-input { font-size: 1.5em; padding: 10px; margin: 10px; }
                .btn { padding: 15px 25px; margin: 10px; font-size: 1.1em; border: none; border-radius: 5px; cursor: pointer; }
                .btn-primary { background: #007bff; color: white; }
                .btn-secondary { background: #6c757d; color: white; }
                .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <h1>🎲 Attribution de Note</h1>
            
            <div class="student-name">
                🎉 <?= htmlspecialchars($student->getFullName()) ?>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="error"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="post" action="index.php?action=assignNote">
                <h3>📝 Attribuer une note</h3>
                <input type="number" name="note" class="note-input" 
                       min="0" max="20" step="0.5" placeholder="0" required autofocus>
                <span>/20</span><br><br>
                
                <button type="submit" class="btn btn-primary">✅ Valider la note</button>
                <a href="index.php?action=skipStudent" class="btn btn-secondary">⭐ Passer sans noter</a>
            </form>

            <p><a href="index.php">← Retour à l'accueil</a></p>
        </body>
        </html>
        <?php
    }
}
?>