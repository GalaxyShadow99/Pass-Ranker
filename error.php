<?php
$code = $_GET['code'] ?? '404'; // Par défaut 404 si rien n'est passé

// Définition des messages selon le code
$errors = [
    '403' => ['title' => 'Accès Interdit', 'msg' => 'Vous n\'avez pas les droits pour accéder à cette zone.'],
    '404' => ['title' => 'Page Introuvable', 'msg' => 'La ressource demandée n\'existe pas.'],
    '500' => ['title' => 'Erreur Serveur', 'msg' => 'Notre serveur fait une pause forcée, revenez plus tard.']
];

$error = $errors[$code] ?? $errors['404'];

// On envoie le vrai code HTTP au navigateur
http_response_code((int)$code);

include_once __DIR__ . '/includes/head.php';
include_once __DIR__ . '/includes/header.php';
?>

<main class="container my-5 text-center">
    <div class="py-5 shadow-sm border rounded bg-light">
        <h1 class="display-1 fw-bold text-primary"><?= $code ?></h1>
        <h2 class="fw-bold"><?= $error['title'] ?></h2>
        <p class="text-muted"><?= $error['msg'] ?></p>
        <hr class="w-25 mx-auto">
        <a href="index.php" class="btn btn-primary mt-3">Retour à l'accueil</a>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>