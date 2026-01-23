
<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$title = 'Contact';
$subtitle = "Nous contacter";
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
    <p>Informations de contact et formulaire : nom, email, message. Placeholder :\n
    remplacez par le traitement du formulaire et la validation côté serveur.</p>
  </section>

</main>

<?php
include_once __DIR__ . '/includes/footer.php';
include_once __DIR__ . '/includes/scriptsInclude.php';
?>
</body>
</html>
