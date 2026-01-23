<?php

// https://www.php.net/manual/fr/book.mysqli.php

// charger les données du .env 
use Dotenv\Dotenv;
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$servername = "127.0.0.1";
$username   = $_ENV['USER']     ?? null;
$password   = $_ENV['PASSWORD'] ?? null;
$dbname     = $_ENV['DB']       ?? null;

if (!$password) {
    die("ERREUR : Le mot de passe n'est pas chargé depuis le .env. Vérifie le fichier.");
}
// Create connection
$conn = new mysqli($servername, $username, $password,$dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";

$sql = "SELECT id_etudiant, moyenne_pharma FROM pass_etudiants WHERE moyenne_pharma IS NOT NULL ORDER BY moyenne_pharma desc;";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h3>Étudiants PHRAMA :</h3>";
    // On boucle sur toutes les lignes retournées
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["id_etudiant"] . " | Note: " . $row["moyenne_pharma"] . " <br>";
    }
} else {
    echo "Aucun étudiant sans note trouvé.";
}

$conn->close();
echo "Connection DB closed"
?> 