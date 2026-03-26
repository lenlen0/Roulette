<?php
require_once '_config.php';

try {
    $bdd = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', 
        DB_USER, 
        DB_PWD, 
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        )
    );
} catch(PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}

// Vérifier si la table contient des données, sinon insérer les données de test
try {
    $stmt = $bdd->query("SELECT COUNT(*) FROM student");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $datafeed = "INSERT INTO `student` (`surname`, `firstname`, `class`) VALUES
        ('BEGUE', 'Theo', 'TEST'),
        ('BEN REJEB', 'Razi', 'TEST'),
        ('BETTINELI', 'Thomas', 'TEST'),
        ('BILLARD', 'Maximilien', 'TEST'),
        ('BOUDRIQUE', 'Victor', 'TEST'),
        ('CHAUWIN', 'Cedric', 'TEST'),
        ('CHAYOT', 'Thibaut', 'TEST'),
        ('COQUET', 'Donovan', 'TEST'),
        ('COURIER', 'Valentin', 'TEST'),
        ('DEMARLY', 'Lucas', 'TEST'),
        ('DOCQ', 'Gregory', 'TEST'),
        ('DUJEUX', 'Aurelien', 'TEST'),
        ('FERNANDES', 'Benoit', 'TEST'),
        ('GESNOT', 'Corentin', 'TEST'),
        ('GRESSIER', 'Dylan', 'TEST'),
        ('HELIOT', 'Timothé', 'TEST'),
        ('KALUZNY', 'Geoffrey', 'TEST'),
        ('LAMBERT', 'Ruddy', 'TEST'),
        ('LARNACK', 'Damien', 'TEST'),
        ('LE GUINIO', 'Florentin', 'TEST'),
        ('LONGNIAUX', 'Guillaume', 'TEST'),
        ('MADAMA', 'Thomas', 'TEST'),
        ('MAILLARD', 'Theo', 'TEST'),
        ('MIDOUX', 'Kevin', 'TEST'),
        ('PADOVAN', 'Alexandre', 'TEST'),
        ('PETITFILS', 'Florian', 'TEST'),
        ('PICHE', 'Alexis', 'TEST'),
        ('PIETOT', 'Maxence', 'TEST'),
        ('PITON', 'Tony', 'TEST'),
        ('PORQUET', 'Vincent', 'TEST'),
        ('REMY', 'Theo', 'TEST'),
        ('ROBERT', 'Julien', 'TEST'),
        ('SAIDI', 'Mohammed', 'TEST'),
        ('TREILLE', 'Alexis', 'TEST')";

        $bdd->exec($datafeed);
        echo "Données de test insérées avec succès.<br>";
    }
} catch(PDOException $e) {
    echo "Erreur lors de l'insertion des données : " . $e->getMessage() . "<br>";
}
?>