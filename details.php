<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once __DIR__ . '/includes/utils.php';
$conn = getDBConnection();

$id_clique = $_GET['id'] ?? null;
$filiere = (isset($_GET['filiere']) && $_GET['filiere'] === "PHARMA") ? "PHARMA" : "MMOK";
$rang_liste = 1; // Compteur pour le classement global

// 1. PRÉPARATION DE LA REQUÊTE SQL (Calcul du rang inclus)
$col_tri = ($filiere === "PHARMA") ? "moyenne_pharma" : "moyenne_mmok";

$sql_base = "SELECT *, RANK() OVER (ORDER BY $col_tri DESC) as rank_promo 
             FROM pass_etudiants 
             JOIN pass_resultats USING(id_etudiant)
             WHERE $col_tri IS NOT NULL";

if ($id_clique) {
    $id_safe = $conn->real_escape_string($id_clique);
    $sql = "SELECT * FROM ($sql_base) AS sub WHERE id_etudiant = '$id_safe'";
} else {
    $sql = $sql_base;
}

$detailNote = $conn->query($sql);

include_once __DIR__ . '/includes/head.php';
?>

<body>
    <?php include_once __DIR__ . '/includes/header.php'; ?>

    <main class="container my-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Détail des notes - <?= $filiere ?></h1>
            <hr class="w-25 mx-auto border-primary">
            <div class="btn-group shadow-sm">
                <a href="details.php?filiere=MMOK" class="btn btn-outline-primary <?= $filiere === 'MMOK' ? 'active' : '' ?>">MMOK</a>
                <a href="details.php?filiere=PHARMA" class="btn btn-outline-success <?= $filiere === 'PHARMA' ? 'active' : '' ?>">PHARMA</a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-12">

                <?php if ($detailNote && $detailNote->num_rows > 0): ?>
                    <div class="table-responsive shadow-sm rounded">
                        <table class='table table-hover table-bordered border-primary mb-0'>
                            <thead class='table-dark text-nowrap'>
                                <tr>
                                    <th class="text-center">Rang</th>
                                    <th>Identifiant</th>
                                    <th class="text-center bg-light text-dark small">UE1</th>
                                    <th class="text-center bg-light text-dark small">UE2</th>
                                    <th class="text-center bg-light text-dark small">UE3</th>
                                    <th class="text-center bg-light text-dark small">UE8</th>
                                    <th class="text-center bg-light text-dark small">UE10</th>
                                    <th class="text-center bg-light text-dark small">UE7 TOT</th>
                                    
                                    <?php if ($filiere === 'MMOK'): ?>
                                        <th class="text-center bg-primary text-white small">UE 12 (Anat/HDM)</th>
                                    <?php else: ?>
                                        <th class="text-center bg-success text-white small">UE 13 (Pharma)</th>
                                    <?php endif; ?>

                                    <th class="text-center">Détail</th>
                                    <th class="text-end">Moyenne Générale</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                                <?php if ($filiere === 'MMOK'): ?>
                                    <?php while ($row = $detailNote->fetch_assoc()): ?>
                                        <tr class="<?= ($id_clique == $row['id_etudiant']) ? 'table-success' : '' ?>">
                                            <td class="text-center fw-bold text-primary">
                                                <?= $id_clique ? $row["rank_promo"] : $rang_liste++ ?>
                                            </td>
                                            <td><?= htmlspecialchars($row["id_etudiant"]) ?></td>
                                            <td class="text-center"><?= number_format($row["ue1"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue2"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue3"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue8"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue10"], 3) ?></td>
                                            <td class="text-center "><?= number_format($row["ue7_total"], 3) ?></td>
                                            <td class="text-center fw-bold text-primary"><?= number_format($row["ue12_total"], 3) ?></td>
                                            <td class="text-center">
                                                <a href="details.php?id=<?= $row['id_etudiant'] ?>&filiere=MMOK"><i class="fa fa-eye"></i></a>
                                            </td>
                                            <td class="text-end text-primary fw-bold"><?= number_format($row["moyenne_mmok"], 3) ?></td>
                                        </tr>
                                    <?php endwhile; ?>

                                <?php elseif ($filiere === 'PHARMA'): ?>
                                    <?php while ($row = $detailNote->fetch_assoc()): ?>
                                        <tr class="<?= ($id_clique == $row['id_etudiant']) ? 'table-success' : '' ?>">
                                            <td class="text-center fw-bold text-success">
                                                <?= $id_clique ? $row["rank_promo"] : $rang_liste++ ?>
                                            </td>
                                            <td><?= htmlspecialchars($row["id_etudiant"]) ?></td>
                                            <td class="text-center"><?= number_format($row["ue1"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue2"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue3"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue8"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue10"], 3) ?></td>
                                            <td class="text-center "><?= number_format($row["ue7_total"], 3) ?></td>
                                            <td class="text-center fw-bold text-success"><?= number_format($row["ue13"], 3) ?></td>
                                            <td class="text-center">
                                                <a href="details.php?id=<?= $row['id_etudiant'] ?>&filiere=PHARMA"><i class="fa fa-eye text-success"></i></a>
                                            </td>
                                            <td class="text-end text-success fw-bold"><?= number_format($row["moyenne_pharma"], 3) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class='alert alert-danger text-center'>Aucun résultat trouvé.</div>
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