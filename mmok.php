<?php
use Dotenv\Dotenv;
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/utils.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$servername = "127.0.0.1";
$username = $_ENV['USER'] ?? null;
$password = $_ENV['PASSWORD'] ?? null;
$dbname = $_ENV['DB'] ?? null;

if (!$password) {
    die("ERREUR : Configuration .env manquante.");
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$sql = "SELECT id_etudiant, moyenne_mmok,filiere FROM pass_etudiants 
        WHERE moyenne_mmok IS NOT NULL 
        ORDER BY moyenne_mmok DESC";
$result = $conn->query($sql);

include_once __DIR__ . '/includes/head.php';
?>

<body>
    <?php include_once __DIR__ . '/includes/header.php'; ?>

    <main class="container my-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Classement MMOK</h1>
            <p class="text-muted">Résultats Partiel intermédiaire Octobre 2025</p>
            <hr class="w-25 mx-auto border-primary">
        </div>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="table-responsive shadow-sm rounded">
                        <table class='table table-hover table-bordered border-primary mb-0'>
                            <thead class='table-dark'>
                                <tr>
                                    <th class="text-center">Rang</th>
                                    <th>Identifiant</th>
                                    <th>Détail des notes</th>
                                    <th class="text-end">Moyenne Générale MMOK</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rang = 1;
                                while ($row = $result->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?= $rang++ ?></td>
                                        <td><?= htmlspecialchars(string: $row["id_etudiant"]) ?></td>
                                        <td>
                                            <a href="details.php?id=<?= $row['id_etudiant'] ?>&filiere=<?= $row['filiere'] ?>"
                                                class="text-decoration-none fw-bold">
                                                <?= htmlspecialchars($row["id_etudiant"]) ?>
                                            </a>
                                        </td>
                                        <td class="text-end text-primary fw-bold">
                                            <?= number_format(num: $row["moyenne_mmok"], decimals: 3) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class='alert alert-info text-center'>Aucun résultat disponible pour cette filière.</div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php
    $conn->close();
    include_once __DIR__ . '/includes/footer.php';
    include_once __DIR__ . '/includes/scriptsInclude.php';
    ?>
</body>

</html>