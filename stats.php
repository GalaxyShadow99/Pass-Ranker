
<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$title = 'Statistiques';
$subtitle = "Aperçu des métriques du site";
include_once __DIR__ . '/includes/head.php';
?>
<body>
<?php include_once __DIR__ . '/includes/header.php'; ?>

<main class="container my-5">
  <div class="text-center mb-4">
    <h1 class="fw-bold"><?= htmlspecialchars($title) ?></h1>
    <p class="text-muted"><?= htmlspecialchars($subtitle) ?></p>
  </div>

  <section>
    <p>Cette page présente un tableau de bord des statistiques du site :
    nombre de visites, utilisateurs actifs, taux de conversion et graphiques.
    Les données sont représentées ici comme des placeholders — connectez
    vos sources de données pour afficher des métriques réelles.</p>
  </section>

</main>

<?php
include_once __DIR__ . '/includes/footer.php';
include_once __DIR__ . '/includes/scriptsInclude.php';
?>
</body>
</html>
