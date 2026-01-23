
<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$title = 'Inscription';
$subtitle = "Créer un compte utilisateur";
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
    <p>Formulaire d'inscription : nom, email, mot de passe, et confirmations.
    Placeholder : remplacez par votre logique d'enregistrement et validation.</p>
  </section>

</main>

<?php
include_once __DIR__ . '/includes/footer.php';
include_once __DIR__ . '/includes/scriptsInclude.php';
?>
</body>
</html>