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
                    $drawnStudents = [];
                    foreach ($keys as $key) {
                        $student = $availableStudents[$key];
                        $drawnIds[] = $student->getId();
                        $drawnStudents[] = $student;
                    }
                    
                    $_SESSION['drawn_student_ids'] = $drawnIds;
                    unset($_SESSION['drawn_student_id'], $_SESSION['group_draw_ids'], $_SESSION['group_draw_total'], $_SESSION['group_draw_current']);
                } else {
                    $randomIndex = random_int(0, count($availableStudents) - 1);
                    $drawnStudent = $availableStudents[$randomIndex];
                    $_SESSION['drawn_student_ids'] = [$drawnStudent->getId()];
                    $drawnStudents = [$drawnStudent];
                    unset($_SESSION['drawn_student_id'], $_SESSION['group_draw_ids'], $_SESSION['group_draw_total'], $_SESSION['group_draw_current']);
                }
            } else {
                // Tirage simple
                $randomIndex = random_int(0, count($availableStudents) - 1);
                $drawnStudent = $availableStudents[$randomIndex];
                $_SESSION['drawn_student_ids'] = [$drawnStudent->getId()];
                $drawnStudents = [$drawnStudent];
                unset($_SESSION['drawn_student_id'], $_SESSION['group_draw_ids'], $_SESSION['group_draw_total'], $_SESSION['group_draw_current']);
            }
            
            // Passer les étudiants à la vue
            $students = $drawnStudents;

            
            if (file_exists('views/note_view.php')) {
                require_once 'views/note_view.php';
            } else if (file_exists('note_view.php')) {
                require_once 'note_view.php';
            } else {
                $this->renderNoteView($students);
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
        if (!isset($_SESSION['drawn_student_ids']) && !isset($_SESSION['drawn_student_id'])) {
            $_SESSION['error'] = "Données manquantes pour l'attribution de note";
            header('Location: index.php');
            exit;
        }
        
        $drawnIds = $_SESSION['drawn_student_ids'] ?? [$_SESSION['drawn_student_id']];
        $notes = $_POST['notes'] ?? [];
        
        // Si une note globale est soumise, on l'applique à tout le groupe
        if (isset($_POST['note']) && !empty($drawnIds)) {
            foreach ($drawnIds as $id) {
                $notes[$id] = $_POST['note'];
            }
        }
        
        try {
            $hasError = false;
            
            foreach ($drawnIds as $studentId) {
                $student = $this->StudentR->getStudentById($studentId);
                if (!$student) continue;
                
                $noteValue = $notes[$studentId] ?? '';
                
                if ($noteValue !== '') {
                    $note = floatval($noteValue);
                    if ($note < 0 || $note > 20) {
                        $_SESSION['error'] = "La note doit être comprise entre 0 et 20 pour " . $student->getFullName();
                        $hasError = true;
                        break;
                    }
                }
            }
            
            if ($hasError) {
                $students = [];
                foreach ($drawnIds as $id) {
                    $s = $this->StudentR->getStudentById($id);
                    if ($s) $students[] = $s;
                }
                
                if (file_exists('views/note_view.php')) {
                    require_once 'views/note_view.php';
                } else if (file_exists('note_view.php')) {
                    require_once 'note_view.php';
                } else {
                    $this->renderNoteView($students);
                }
                return;
            }
            
            $messages = [];
            foreach ($drawnIds as $studentId) {
                $student = $this->StudentR->getStudentById($studentId);
                if (!$student) continue;
                
                $noteValue = $notes[$studentId] ?? '';
                
                if ($noteValue !== '') {
                    $note = floatval($noteValue);
                    $this->StudentR->updateStudentNote($studentId, $note);
                    $messages[] = $student->getFullName() . " (" . number_format($note, 1) . "/20)";
                } else {
                    $this->StudentR->markStudentAsPassed($studentId);
                    $messages[] = $student->getFullName() . " (Passé)";
                }
            }
            
            if (!empty($messages)) {
                $_SESSION['message'] = "Enregistré : " . implode(', ', $messages);
            }
            unset($_SESSION['drawn_student_id'], $_SESSION['drawn_student_ids']);
            
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
        if (!isset($_SESSION['drawn_student_ids']) && !isset($_SESSION['drawn_student_id'])) {
            $_SESSION['error'] = "Aucun étudiant à passer";
            header('Location: index.php');
            exit;
        }
        
        $drawnIds = $_SESSION['drawn_student_ids'] ?? [$_SESSION['drawn_student_id']];
        
        try {
            $messages = [];
            foreach ($drawnIds as $studentId) {
                $student = $this->StudentR->getStudentById($studentId);
                if ($student) {
                    $this->StudentR->markStudentAsPassed($studentId);
                    $messages[] = $student->getFullName();
                }
            }
            
            if (!empty($messages)) {
                $_SESSION['message'] = implode(', ', $messages) . " passé(s) sans note.";
            }
            
            unset($_SESSION['drawn_student_id'], $_SESSION['drawn_student_ids']);
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors du passage : " . $e->getMessage();
            header('Location: index.php');
            exit;
        }
    }
    
    /**
     * Continue le tirage au sort d'un groupe d'étudiants
     */
    public function continueGroupDraw() {
        // Cette méthode n'est plus utilisée, le groupe est noté en même temps
        header('Location: index.php');
        exit;
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
    private function renderNoteView($students) {
        if (!is_array($students)) {
            $students = [$students];
        }
        
        // Si vide, essayer de récupérer
        if (empty($students) && isset($_SESSION['drawn_student_ids'])) {
            foreach ($_SESSION['drawn_student_ids'] as $id) {
                $s = $this->StudentR->getStudentById($id);
                if ($s) $students[] = $s;
            }
        }
        
        if (empty($students)) {
            $_SESSION['error'] = "Erreur: étudiant(s) introuvable(s)";
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
                <?php foreach ($students as $s): ?>
                    🎉 <?= htmlspecialchars($s->getFullName()) ?><br>
                <?php endforeach; ?>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="error"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="post" action="index.php?action=assignNote">
                <h3>📝 Attribuer une note (globale)</h3>
                <input type="number" name="note" class="note-input" 
                       min="0" max="20" step="0.5" placeholder="0" autofocus>
                <span>/20</span><br><br>
                
                <button type="submit" class="btn btn-primary">✅ Valider la note</button>
                <a href="index.php?action=skipStudent" class="btn btn-secondary">⭐ Tout passer sans noter</a>
            </form>

            <p><a href="index.php">← Retour à l'accueil</a></p>
        </body>
        </html>
        <?php
    }
}
?>