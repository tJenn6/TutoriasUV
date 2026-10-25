<?php
require_once "config/sesion.php";
requerir_login('estudiante');

?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel del estudiante · Tutorías UV</title>
  <link rel="icon" type="image/png" href="media/uvlogo.webp">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
  <link href="css/style.css"rel="stylesheet">
</head>
<body>
  <header class="site-header">
    <div class="container">
      <a class="brand" href="index.html">
        <img class="brand__logo" src="media/uvlogo.webp" alt="Logotipo Universidad Veracruzana">
        <div class="brand__text">
          <span class="brand__title">Tutorías <span>UV</span></span>
          <span class="brand__sub">Universidad Veracruzana</span>
        </div>
      </a>
        <form method="post" action="login.php">
        <input type="hidden" name="accion" value="logout">
        <input type="hidden" name="token" value="<?= h(token_csrf()) ?>">
        <button class="btn btn--dark btn--sm" type="submit">Cerrar sesión</button>
        </form>
    </div>
  </header>

  <main>
    <div class="container page">
      <section class="section">
        <h1 class="section-title">Hola, <?= h($_SESSION['usuario_nombre']) ?></h1>
        <p class="card-text">Panel del estudiante </p>
      </section>
    </div>
  </main>
</body>
</html>