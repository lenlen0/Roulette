<?php
class Student {
    private $id;
    private $surname;
    private $firstname;
    private $class;
    private $ldap;
    private $bool;
    private $passage;
    private $absence;
    private $noteaddition;
    private $notetotal;
    private $average;
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }
    
    public function hydrate($data) {
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getSurname() { return $this->surname; }
    public function getFirstname() { return $this->firstname; }
    public function getClass() { return $this->class; }
    public function getLdap() { return $this->ldap; }
    public function getBool() { return $this->bool; }
    public function getPassage() { return $this->passage; }
    public function getAbsence() { return $this->absence; }
    public function getNoteaddition() { return $this->noteaddition; }
    public function getNotetotal() { return $this->notetotal; }
    public function getAverage() { return $this->average; }
    
    // Setters
    public function setId($id) { $this->id = $id; }
    public function setSurname($surname) { $this->surname = $surname; }
    public function setFirstname($firstname) { $this->firstname = $firstname; }
    public function setClass($class) { $this->class = $class; }
    public function setLdap($ldap) { $this->ldap = $ldap; }
    public function setBool($bool) { $this->bool = $bool; }
    public function setPassage($passage) { $this->passage = $passage; }
    public function setAbsence($absence) { $this->absence = $absence; }
    public function setNoteaddition($noteaddition) { $this->noteaddition = $noteaddition; }
    public function setNotetotal($notetotal) { $this->notetotal = $notetotal; }
    public function setAverage($average) { $this->average = $average; }
    
    // Méthodes utilitaires
    public function getFullName() {
        return $this->firstname . ' ' . $this->surname;
    }
    
    public function hasPassedAlready() {
        return $this->passage > 0;
    }
}
?>