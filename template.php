<?php
// ID ETU Romane 22501425
use Dotenv\Dotenv;
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$servername = "127.0.0.1";
$username   = $_ENV['USER'] ?? null;
$password   = $_ENV['PASSWORD'] ?? null;
$dbname     = $_ENV['DB'] ?? null;

if (!$password) {
    die("ERREUR : Configuration .env manquante.");
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$sql = "SELECT id_etudiant, moyenne_mmok FROM pass_etudiants 
        WHERE moyenne_mmok IS NOT NULL 
        ORDER BY moyenne_mmok DESC";
$result = $conn->query($sql);

include_once __DIR__ . '/includes/head.php';
?>

<body>
  <?php include_once __DIR__ . '/includes/header.php'; ?>

  <main class="container my-5">
    <div class="text-center mb-5">
      <h1 class="fw-bold">Template</h1>
      <p class="text-muted">...</p>
      <hr class="w-25 mx-auto border-primary">
    </div>

  </main>

  <?php 
  $conn->close();
  include_once __DIR__ . '/includes/footer.php'; 
  include_once __DIR__ . '/includes/scriptsInclude.php'; 
  ?>
</body>
</html>