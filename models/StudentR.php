<?php
class StudentR {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Récupère toutes les classes distinctes
     */
    public function getAllClasses() {
        $stmt = $this->db->prepare("SELECT DISTINCT class FROM student ORDER BY class");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupère tous les étudiants d'une classe
     */
    public function getStudentsByClass($class) {
        $stmt = $this->db->prepare("SELECT * FROM student WHERE class = ? ORDER BY surname, firstname");
        $stmt->execute([$class]);
        $students = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $students[] = new Student($row);
        }
        return $students;
    }
    
    /**
     * Récupère les étudiants disponibles (non passés) d'une classe
     */
    public function getAvailableStudentsByClass($class) {
        $stmt = $this->db->prepare("SELECT * FROM student WHERE class = ? AND passage = 0 ORDER BY surname, firstname");
        $stmt->execute([$class]);
        $students = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $students[] = new Student($row);
        }
        return $students;
    }
    
    /**
     * Récupère les étudiants déjà passés d'une classe
     */
    public function getPassedStudentsByClass($class) {
        $stmt = $this->db->prepare("SELECT * FROM student WHERE class = ? AND passage > 0 ORDER BY passage ASC");
        $stmt->execute([$class]);
        $students = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $students[] = new Student($row);
        }
        return $students;
    }
    
    /**
     * Met à jour la note d'un étudiant et incrémente son passage
     */
    public function updateStudentNote($id, $note) {
        $stmt = $this->db->prepare("UPDATE student SET noteaddition = ?, passage = passage + 1 WHERE id = ?");
        return $stmt->execute([$note, $id]);
    }
    
    /**
     * Marque un étudiant comme passé sans note
     */
    public function markStudentAsPassed($id) {
        $stmt = $this->db->prepare("UPDATE student SET passage = passage + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Remet à zéro les passages pour une classe
     */
    public function resetPassages($class) {
        $stmt = $this->db->prepare("UPDATE student SET passage = 0 WHERE class = ?");
        return $stmt->execute([$class]);
    }
    
    /**
     * Remet à zéro les passages et les notes pour une classe
     */
    public function resetPassagesAndNotes($class) {
        $stmt = $this->db->prepare("UPDATE student SET passage = 0, noteaddition = NULL, notetotal = NULL, average = NULL WHERE class = ?");
        return $stmt->execute([$class]);
    }
    
    /**
     * Récupère un étudiant par son ID
     */
    public function getStudentById($id) {
        $stmt = $this->db->prepare("SELECT * FROM student WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new Student($row) : null;
    }
}
?>