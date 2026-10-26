<?php

require_once "config/conexion.php";
require_once "config/sesion.php";

// Cerrar sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'logout') {
    if (token_valido($_POST['token'] ?? '')) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
    header('Location: index.html');
    exit;
}

// Si ya inició sesión, lo mandamos a su panel
if (!empty($_SESSION['usuario_id'])) {
    header('Location: ' . ruta_panel($_SESSION['usuario_tipo']));
    exit;
}

// Portal elegido
$tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? 'estudiante';
if ($tipo !== 'estudiante' && $tipo !== 'tutor') {
    $tipo = 'estudiante';
}

$error = "";
$correo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave  = $_POST['clave'] ?? '';

    if (!token_valido($_POST['token'] ?? '')) {
        $error = "El formulario expiró. Intenta de nuevo.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $clave === '') {
        $error = "Escribe un correo válido y tu contraseña.";
    } else {
        $consulta = $conexion->prepare(
            "SELECT id, nombre, contrasena, tipo FROM usuarios WHERE correo = ?"
        );
        $consulta->bind_param("s", $correo);
        $consulta->execute();
        $fila = $consulta->get_result()->fetch_assoc();
        $consulta->close();

        if (!$fila || $fila['tipo'] !== $tipo) {
            $error = "No existe una cuenta de " . ($tipo === 'tutor' ? 'tutor' : 'estudiante') . " con ese correo.";
        } else {
            $contrasena_guardada = (string)$fila['contrasena'];
            $algo_hash = password_get_info($contrasena_guardada)['algo'];
            $usa_hash = $algo_hash !== null && $algo_hash !== 0;
            $contrasena_ok = $usa_hash
                ? password_verify($clave, $contrasena_guardada)
                : hash_equals($contrasena_guardada, $clave);

            if (!$contrasena_ok) {
                $error = "La contraseña es incorrecta.";
            } else {
                session_regenerate_id(true);
                $_SESSION['usuario_id']     = (int)$fila['id'];
                $_SESSION['usuario_nombre'] = $fila['nombre'];
                $_SESSION['usuario_tipo']   = $fila['tipo'];
                header('Location: ' . ruta_panel($fila['tipo']));
                exit;
            }
        }
    }
}

$esTutor = ($tipo === 'tutor');
?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión · Tutorías UV</title>
  <link rel="icon" type="image/png" href="media/uvlogo.webp">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
</head>
<body>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="/TutoriasUV/">
        <img class="brand__logo" src="media/uvlogo.webp" alt="Logotipo Universidad Veracruzana">
        <div class="brand__text">
          <span class="brand__title">Tutorías <span>UV</span></span>
          <span class="brand__sub">Universidad Veracruzana</span>
        </div>
      </a>
    </div>
  </header>

  <main class="login-page">
    <div class="container">
      <section class="access-card <?= $esTutor ? 'access-card--teacher' : 'access-card--student' ?> login-card">
        <div>
          <h1 class="card-title"><?= $esTutor ? 'Portal del Docente Tutor' : 'Portal del Tutorado' ?></h1>
          <p class="card-text">Escribe tu correo y contraseña para continuar.</p>

          <?php if ($error !== ""): ?>
            <div class="alert alert--error" role="alert"><?= h($error) ?></div>
          <?php endif; ?>

          <form class="login-form" method="post" action="login.php" novalidate>
            <input type="hidden" name="token" value="<?= h(token_csrf()) ?>">
            <input type="hidden" name="tipo" value="<?= h($tipo) ?>">

            <div class="form-field">
              <label class="form-label" for="correo">Correo</label>
              <input class="form-input" type="email" id="correo" name="correo"
                     value="<?= h($correo) ?>" autocomplete="username" required>
            </div>

            <div class="form-field">
              <label class="form-label" for="clave">Contraseña</label>
              <input class="form-input" type="password" id="clave" name="clave"
                     autocomplete="current-password" required>
            </div>

            <button class="btn <?= $esTutor ? 'btn--dark' : 'btn--green' ?> btn--block" type="submit">
              Iniciar sesión
            </button>
          </form>

          <a class="back-link" href="/TutoriasUV/">← Volver al inicio</a>
        </div>
      </section>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p class="footer-text">Proyecto académico estudiantil. No es un portal oficial de la Universidad Veracruzana ni sustituye al Sistema Institucional de Tutorías.</p>
    </div>
  </footer>

</body>
</html>