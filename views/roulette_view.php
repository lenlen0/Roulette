<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roulette des Étudiants</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container { 
            max-width: 1200px; margin: 0 auto;
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
        
        .content { padding: 30px; }
        
        .class-selector {
            background: #f8f9fa;
            padding: 20px; border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid #4ECDC4;
        }
        
        .class-selector h3 {
            margin-top: 0; color: #333;
        }
        
        select, button, input {
            padding: 12px 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        select:focus, input:focus {
            outline: none;
            border-color: #4ECDC4;
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.2);
        }
        
        .btn {
            background: linear-gradient(45deg, #FF6B6B, #FF8E8E);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, #4ECDC4, #44A08D);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #FF416C, #FF4B2B);
        }
        
        .btn-danger:hover {
            box-shadow: 0 5px 15px rgba(255, 65, 108, 0.4);
        }
        
        .roulette-section {
            text-align: center;
            margin: 30px 0;
            padding: 25px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
        }
        
        .draw-button {
            font-size: 1.5em;
            padding: 20px 40px;
            background: #FFD700;
            color: #333;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 2px;
        }
        
        .draw-button:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.5);
        }
        
        .students-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .student-list {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            border-top: 4px solid #4ECDC4;
        }
        
        .student-list h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        
        .student-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            margin: 8px 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        
        .student-item:hover {
            transform: translateX(5px);
        }
        
        .student-name {
            font-weight: 500;
            color: #333;
        }
        
        .student-note {
            background: #4ECDC4;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .students-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .actions {
                flex-direction: column;
                align-items: center;
            }
            
            .draw-button {
                font-size: 1.2em;
                padding: 15px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎲 Roulette des Étudiants</h1>
        </div>
        
        <div class="content">
            <!-- Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="message success">
                    <?= htmlspecialchars($_SESSION['message']) ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="message error">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Sélection de classe -->
            <div class="class-selector">
                <h3>📚 Sélection de la classe</h3>
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
            </div>

            <?php if ($selectedClass): ?>
                <!-- Section tirage au sort -->
                <div class="roulette-section">
                    <h2>🎯 Classe: <?= htmlspecialchars($selectedClass) ?></h2>
                    <p>Étudiants restants: <strong><?= count($availableStudents) ?></strong></p>
                    
                    <?php if (count($availableStudents) > 0): ?>
                        <form method="post" action="index.php?action=drawStudent">
                            <button type="submit" class="draw-button">
                                🎲 Tirer au sort !
                            </button>
                        </form>
                    <?php else: ?>
                        <p style="font-size: 1.2em; margin-top: 20px;">
                            ✅ Tous les étudiants sont passés !
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Listes des étudiants -->
                <div class="students-grid">
                    <!-- Étudiants restants -->
                    <div class="student-list">
                        <h3>👥 Étudiants restants (<?= count($availableStudents) ?>)</h3>
                        <?php if (empty($availableStudents)): ?>
                            <p>Aucun étudiant restant</p>
                        <?php else: ?>
                            <?php foreach ($availableStudents as $student): ?>
                                <div class="student-item">
                                    <span class="student-name">
                                        <?= htmlspecialchars($student->getFullName()) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Étudiants passés -->
                    <div class="student-list">
                        <h3>✅ Étudiants passés (<?= count($passedStudents) ?>)</h3>
                        <?php if (empty($passedStudents)): ?>
                            <p>Aucun étudiant passé</p>
                        <?php else: ?>
                            <?php foreach ($passedStudents as $student): ?>
                                <div class="student-item">
                                    <span class="student-name">
                                        <?= htmlspecialchars($student->getFullName()) ?>
                                    </span>
                                    <?php if ($student->getNoteaddition() !== null): ?>
                                        <span class="student-note">
                                            <?= number_format($student->getNoteaddition(), 1) ?>/20
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">Passé</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="actions">
                    <button onclick="if(confirm('Réinitialiser les passages ?')) location.href='index.php?action=resetPassages'" 
                            class="btn btn-secondary">
                        🔄 Réinitialiser les passages
                    </button>
                    <button onclick="if(confirm('Réinitialiser les passages ET les notes ?')) location.href='index.php?action=resetAll'" 
                            class="btn btn-danger">
                        🗑️ Tout réinitialiser
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Animation de la roulette
        document.addEventListener('DOMContentLoaded', function() {
            const drawButton = document.querySelector('.draw-button');
            if (drawButton) {
                drawButton.addEventListener('click', function(e) {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 150);
                });
            }

            // Animation des étudiants
            const studentItems = document.querySelectorAll('.student-item');
            studentItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>