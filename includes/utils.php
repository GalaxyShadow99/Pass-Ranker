<?php
// On s'assure que Dotenv est bien chargé si on appelle ce fichier
use Dotenv\Dotenv;

/**
 * Initialise la connexion à la base de données
 */
function getDBConnection() {
    // Les variables sont récupérées depuis l'environnement
    $servername = "127.0.0.1";
    $username   = $_ENV['USER'] ?? null;
    $password   = $_ENV['PASSWORD'] ?? null;
    $dbname     = $_ENV['DB'] ?? null;

    if (!$password) {
        die("ERREUR : Configuration .env manquante.");
    }

    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Échec de la connexion : " . $conn->connect_error);
    }

    // On force l'encodage en UTF-8 pour les noms/prénoms
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/**
 * Fonction pour récupérer le top 10 d'une filière
 */
function getTopRank($conn, $filiere = 'mmok', $limit = 10) {
    $column = "moyenne_" . $filiere;
    $sql = "SELECT id_etudiant, $column FROM pass_etudiants 
            WHERE $column IS NOT NULL 
            ORDER BY $column DESC LIMIT $limit";
    return $conn->query($sql);
}

function getAllStudentsGrades($conn, $filiere = "MMOK") {
    $column = "moyenne_" . $filiere;
    $sql = "SELECT * FROM pass_etudiants JOIN pass_resultats USING(id_etudiant) 
            WHERE $column IS NOT NULL 
            ORDER BY $column DESC";
    return $conn->query($sql);
}
function getStudentRank($conn,$id_etu) {
    $sql = "SELECT rank FROM (
        SELECT id_etudiant, moyenne_mmok,RANK() OVER (ORDER BY moyenne_mmok DESC) as rank
        FROM pass_etudiants
        WHERE moyenne_mmok IS NOT NULL
    ) AS classement
    WHERE id_etudiant=$id_etu;";
    return $conn->query($sql);
}
/**
 * Formate les notes proprement (ex: 15.386)
 */
function formatNote($note, $decimals = 3) {
    return number_format((float)$note, $decimals, '.', '');
}