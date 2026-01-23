<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once __DIR__ . '/includes/utils.php';
$conn = getDBConnection();

$id_clique = $_GET['id'] ?? null;
$filiere = (isset($_GET['filiere']) && $_GET['filiere'] === "PHARMA") ? "PHARMA" : "MMOK";
$rang_liste = 1;
$notes_eleve = null;

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
if ($id_clique && $detailNote && $detailNote->num_rows > 0) {
    $all_rows = $detailNote->fetch_all(MYSQLI_ASSOC);

    foreach ($all_rows as $row_data) {
        if ($row_data['id_etudiant'] == $id_clique) {
            $ue_spec = ($filiere === "PHARMA") ? $row_data['ue13'] : $row_data['ue12_total'];
            $moy_spec = ($filiere === "PHARMA") ? $row_data['moyenne_pharma'] : $row_data['moyenne_mmok'];

            $notes_eleve = [
                (float) $row_data['ue1'],
                (float) $row_data['ue2'],
                (float) $row_data['ue3'],
                (float) $row_data['ue8'],
                (float) $row_data['ue10'],
                (float) $ue_spec,
                (float) $moy_spec
            ];
            break;
        }
    }
    $detailNote->data_seek(0);
}

// --- RÉCUPÉRATION DES MOYENNES DE LA PROMO ---
$promo_averages = [];
$col_moy_promo = ($filiere === "PHARMA") ? "moyenne_pharma" : "moyenne_mmok";
$ue_spec_col = ($filiere === "PHARMA") ? "ue13" : "ue12_total";

$sql_avg = "SELECT 
                AVG(ue1) as avg_ue1, 
                AVG(ue2) as avg_ue2, 
                AVG(ue3) as avg_ue3, 
                AVG(ue8) as avg_ue8, 
                AVG(ue10) as avg_ue10, 
                AVG($ue_spec_col) as avg_ue_spec,
                AVG($col_moy_promo) as avg_generale
            FROM pass_resultats 
            JOIN pass_etudiants
            WHERE $col_moy_promo IS NOT NULL";

$res_avg = $conn->query($sql_avg);
if ($res_avg) {
    $row_avg = $res_avg->fetch_assoc();
    $promo_averages = [
        (float) $row_avg['avg_ue1'],
        (float) $row_avg['avg_ue2'],
        (float) $row_avg['avg_ue3'],
        (float) $row_avg['avg_ue8'],
        (float) $row_avg['avg_ue10'],
        (float) $row_avg['avg_ue_spec'],
        (float) $row_avg['avg_generale']
    ];
}

// --- DONNÉES SOURCE : Règlement admission Rouen 2025-2026 ---
define('PLACES_PASS_MEDECINE', 130);
define('PLACES_PASS_TOTAL_MMOPK', 248); // Somme des places PASS

// Seuil Grand Admis (Max 50% de la capacité PASS sans oraux)
define('SEUIL_GRAND_ADMIS', 124);

/**
 * Retourne la zone de résultat selon le rang SQL
 */
function getSurvivalZone($rank)
{
    if ($rank <= SEUIL_GRAND_ADMIS) {
        return ['class' => 'table-success', 'label' => 'Grand Admis (Probable)'];
    }
    if ($rank <= PLACES_PASS_TOTAL_MMOPK) {
        return ['class' => 'table-warning', 'label' => 'Admissible Oral / Autres filières'];
    }
    return ['class' => 'table-danger', 'label' => 'Zone de Risque (Réorientation)'];
}

include_once __DIR__ . '/includes/head.php';
?>

<style>
    .clickable-row {
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .clickable-row:hover {
        background-color: rgba(0, 0, 0, 0.05) !important;
        box-shadow: inset 0 -2px 0 0 var(--bs-primary);
    }

    .table-bordered> :not(caption)>*>* {
        border-width: 1px !important;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<body>
    <?php include_once __DIR__ . '/includes/header.php'; ?>

    <main class="container my-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Détail des notes - <?= $filiere ?></h1>
            <hr class="w-25 mx-auto border-primary">
            <div class="btn-group shadow-sm">
                <a href="details.php?filiere=MMOK"
                    class="btn btn-outline-primary <?= $filiere === 'MMOK' ? 'active' : '' ?>">MMOK</a>
                <a href="details.php?filiere=PHARMA"
                    class="btn btn-outline-success <?= $filiere === 'PHARMA' ? 'active' : '' ?>">PHARMA</a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-12">

                <?php if ($detailNote && $detailNote->num_rows > 0): ?>

                    <?php if ($id_clique): ?>
                        <div class="alert alert-info shadow-sm mb-4">
                            <i class="fa fa-user me-2"></i> Vue détaillée de l'étudiant :
                            <strong><?= htmlspecialchars($id_clique) ?></strong>
                        </div>
                    <?php endif; ?>

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
                                    <th
                                        class="text-center <?= $filiere === 'MMOK' ? 'bg-primary' : 'bg-success' ?> text-white small">
                                        <?= $filiere === 'MMOK' ? 'UE 12 (Anat/HDM)' : 'UE 13 (Pharma)' ?>
                                    </th>
                                    <th class="text-center">Action</th>
                                    <th class="text-end">Moyenne Générale</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php if ($filiere === 'MMOK'): ?>
                                    <?php while ($row = $detailNote->fetch_assoc()): ?>
                                        <?php
                                        // CAPTURE DES NOTES POUR LE GRAPH s'il s'agit de l'étudiant cliqué
                                        if ($id_clique == $row['id_etudiant']) {
                                            $notes_eleve = [(float) $row['ue1'], (float) $row['ue2'], (float) $row['ue3'], (float) $row['ue8'], (float) $row['ue10'], (float) $row['ue12_total'], (float) $row['moyenne_mmok']];
                                        }
                                        ?>
                                        <tr class="<?= ($id_clique == $row['id_etudiant']) ? 'table-success' : 'clickable-row' ?>"
                                            <?php if ($id_clique != $row['id_etudiant']): ?>
                                                onclick="window.location.href='details.php?id=<?= $row['id_etudiant'] ?>&filiere=<?= $filiere ?>'"
                                            <?php endif; ?>
                                            style="<?= ($id_clique == $row['id_etudiant']) ? '' : 'cursor: pointer;' ?>">
                                            <td class="text-center fw-bold text-primary">
                                                <?= $id_clique ? $row["rank_promo"] : $rang_liste++ ?>
                                            </td>
                                            <td><?= htmlspecialchars($row["id_etudiant"]) ?></td>
                                            <td class="text-center"><?= number_format($row["ue1"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue2"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue3"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue8"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue10"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue7_total"], 3) ?></td>
                                            <td class="text-center fw-bold text-primary"><?= number_format($row["ue12_total"], 3) ?>
                                            </td>
                                            <td class="text-center"><i class="fa fa-eye text-primary"></i></td>
                                            <td class="text-end fw-bold text-primary"><?= number_format($row["moyenne_mmok"], 3) ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>

                                <?php elseif ($filiere === 'PHARMA'): ?>
                                    <?php while ($row = $detailNote->fetch_assoc()): ?>
                                        <?php
                                        if ($id_clique == $row['id_etudiant']) {
                                            $notes_eleve = [(float) $row['ue1'], (float) $row['ue2'], (float) $row['ue3'], (float) $row['ue8'], (float) $row['ue10'], (float) $row['ue13'], (float) $row['moyenne_pharma']];
                                        }
                                        ?>
                                        <tr class="<?= ($id_clique == $row['id_etudiant']) ? 'table-success' : 'clickable-row' ?>"
                                            <?php if ($id_clique != $row['id_etudiant']): ?>
                                                onclick="window.location.href='details.php?id=<?= $row['id_etudiant'] ?>&filiere=<?= $filiere ?>'"
                                            <?php endif; ?>
                                            style="<?= ($id_clique == $row['id_etudiant']) ? '' : 'cursor: pointer;' ?>">
                                            <td class="text-center fw-bold text-success">
                                                <?= $id_clique ? $row["rank_promo"] : $rang_liste++ ?>
                                            </td>
                                            <td><?= htmlspecialchars($row["id_etudiant"]) ?></td>
                                            <td class="text-center"><?= number_format($row["ue1"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue2"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue3"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue8"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue10"], 3) ?></td>
                                            <td class="text-center"><?= number_format($row["ue7_total"], 3) ?></td>
                                            <td class="text-center fw-bold text-success"><?= number_format($row["ue13"], 3) ?></td>
                                            <td class="text-center"><i class="fa fa-eye text-success"></i></td>
                                            <td class="text-end fw-bold text-success">
                                                <?= number_format($row["moyenne_pharma"], 3) ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>

                    <?php if ($id_clique && $notes_eleve): ?>
                        <div class="card mt-5 shadow-sm border-primary">
                            <div class="card-body">
                                <h5 class="card-title text-center mb-4">Analyse des UE - Étudiant
                                    <?= htmlspecialchars($id_clique) ?>
                                </h5>
                                <div style="max-width: 800px; margin: auto;">
                                    <canvas id="barChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <script>
                            document.addEventListener("DOMContentLoaded", function () {
                                const canvas = document.getElementById('barChart');
                                if (!canvas) return;
                                const ctx = canvas.getContext('2d');
                                // --- FORCE LA TAILLE SANS TOUCHER AU HTML ---
                                // On donne une hauteur fixe au parent pour que le graph s'étire
                                canvas.parentNode.style.height = '600px';
                                canvas.parentNode.style.width = '100%';
                                const studentData = <?= json_encode($notes_eleve) ?>;
                                const promoData = <?= json_encode($promo_averages) ?>;
                                if (studentData) {
                                    new Chart(ctx, {
                                        type: 'bar',
                                        data: {
                                            labels: ['UE1', 'UE2', 'UE3', 'UE8', 'UE10', '<?= $filiere == "MMOK" ? "UE12" : "UE13" ?>', 'MOYENNE'],
                                            datasets: [
                                                {
                                                    label: 'Vos Notes',
                                                    data: studentData,
                                                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                                                    borderColor: 'rgba(54, 162, 235, 1)',
                                                    borderWidth: 1,
                                                    order: 2
                                                },
                                                {
                                                    label: 'Moy Promo (Rouen 2026)',
                                                    data: promoData,
                                                    borderColor: '#ffc107',
                                                    backgroundColor: 'rgba(255, 193, 7, 0.2)',
                                                    type: 'line',
                                                    borderWidth: 4,
                                                    pointRadius: 6,
                                                    pointBackgroundColor: '#ffc107',
                                                    fill: false,
                                                    tension: 0.3,
                                                    order: 1
                                                }
                                            ]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false, // Permet d'utiliser toute la hauteur définie plus haut
                                            scales: {
                                                y: {
                                                    beginAtZero: true,
                                                    max: 20,
                                                    ticks: {
                                                        font: { size: 14, weight: 'bold' }
                                                    },
                                                    title: {
                                                        display: true,
                                                        text: 'Note sur 20',
                                                        font: { size: 16 }
                                                    }
                                                },
                                                x: {
                                                    ticks: {
                                                        font: { size: 12, weight: 'bold' }
                                                    }
                                                }
                                            },
                                            plugins: {
                                                legend: {
                                                    labels: { font: { size: 14 } }
                                                }
                                            }
                                        }
                                    });
                                }
                            });
                        </script>
                    <?php endif; ?>

                <?php else: ?>
                    <div class='alert alert-danger text-center shadow-sm'><strong>Erreur :</strong> Aucun résultat trouvé.
                    </div>
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