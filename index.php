<?php
require_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/includes/head.php';
?>

<body>
  <?php include_once __DIR__ . '/includes/header.php'; ?>

  <main class="container my-5">
    <section class="text-center py-5" style="background: linear-gradient(135deg,#0b1226 0%, #172554 60%); color: #fff; border-radius: .5rem; padding: 4rem 2rem;">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <h1 class="display-5 fw-bold">Bienvenue sur PASSRANKER</h1>
          <p class="lead opacity-75">Une petite plateforme conviviale pour visualiser et suivre les résultats PBI, générer des classements et consulter des statistiques par filière.</p>
          <br>
          <p>Vous pouvez consulter les classements du denrier partiel blanc d'octobre  </p>
          <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="details.php?filiere=MMOK" class="btn btn-light btn-lg">Classement général MMOK</a>
            <a href="details.php?filiere=PHARMA" class="btn btn-outline-light btn-lg">Classement général Pharmacie</a>
          </div>
        </div>
      </div>
    </section>

    <section class="row mt-5 gy-4">
      <div class="col-md-4">
        <div class="card h-100 shadow-sm bg-white">
          <div class="card-body">
            <h5 class="card-title text-center">Clair et rapide</h5>
            <p class="card-text text-center small text-muted">Des tableaux et classements simples pour retrouver rapidement les informations dont vous avez besoin.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 shadow-sm bg-white">
          <div class="card-body">
            <h5 class="card-title text-center">Données fiables</h5>
            <p class="card-text text-center small text-muted">Les résultats sont extraits du document PDF récapitulatif des résultats de partiel, le code source complet du projet est à retrouver <a target="_blank" href="https://github.com/GalaxyShadow99/Pass-Ranker">ici</a> </p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 shadow-sm bg-white">
          <div class="card-body">
            <h5 class="card-title text-center">Conçu pour les PASS</h5>
            <p class="card-text small text-muted text-center">Outils pensés pour les étudiants des filières PASS/ santé : classements, filtres et statistiques.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="text-center mt-5">
      <p class="text-muted">Projet léger développé par un étudiant d'IUT Informatique Caennais, si vous avez la moindre remarque sur un segment du site ou sur les données : <a href="mailto:thomas.constantin27@orange.fr">Contactez moi !</a></p>
    </section>
  </main>

  <?php include_once __DIR__ . '/includes/footer.php'; ?>
  <?php include_once __DIR__ . '/includes/scriptsInclude.php'; ?>
</body>
</html>
