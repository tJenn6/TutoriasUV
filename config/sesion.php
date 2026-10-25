<?php
// Inicia la sesión y define funciones de ayuda para todo el sitio.

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS'])
    ]);
    session_start();
}

// Escapa texto antes de mostrarlo en HTML (evita XSS).
function h($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

// Token para proteger los formularios que modifican datos (CSRF).
function token_csrf() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function token_valido($token) {
    return isset($_SESSION['csrf'])
        && is_string($token)
        && hash_equals($_SESSION['csrf'], $token);
}

// Página de inicio de cada tipo de usuario.
function ruta_panel($tipo) {
    return $tipo === 'tutor' ? 'panel_tutor.php' : 'panel_estudiante.php';
}

// Protege páginas privadas: exige sesión iniciada y el tipo de usuario correcto.
function requerir_login($tipo) {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: login.php?tipo=' . urlencode($tipo));
        exit;
    }
    if ($_SESSION['usuario_tipo'] !== $tipo) {
        http_response_code(403);
        die('No tienes permiso para ver esta página.');
    }
}
// Parte de arriba de toda página privada (head, encabezado y apertura de <main>).
function encabezado_privado($titulo) {
    $tipo_usuario = $_SESSION['usuario_tipo'] ?? 'estudiante';
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($titulo) ?> · Tutorías UV</title>
  <link rel="icon" type="image/png" href="media/uvlogo.webp">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
</head>
<body>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="<?= h(ruta_panel($tipo_usuario)) ?>">
        <img class="brand__logo" src="media/uvlogo.webp" alt="Logotipo Universidad Veracruzana">
        <div class="brand__text">
          <span class="brand__title">Tutorías <span>UV</span></span>
          <span class="brand__sub">Universidad Veracruzana</span>
        </div>
      </a>

      <div class="user-bar">
        <span class="user-bar__name"><?= h($_SESSION['usuario_nombre'] ?? '') ?></span>
        <form method="post" action="login.php">
          <input type="hidden" name="accion" value="logout">
          <input type="hidden" name="token" value="<?= h(token_csrf()) ?>">
          <button class="btn btn--dark btn--sm" type="submit">Cerrar sesión</button>
        </form>
      </div>
    </div>
  </header>

  <main>
    <div class="container page">
<?php
}

// Parte de abajo de toda página privada (cierra <main> y pone el pie).
function pie_privado() {
    ?>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p class="footer-text">Proyecto académico estudiantil. No es un portal oficial de la Universidad Veracruzana ni sustituye al Sistema Institucional de Tutorías.</p>
    </div>
  </footer>

</body>
</html>
<?php
}



// Texto de una tutoría, por ejemplo: "jueves 05 de oct · 2:00 pm a 3:00 pm".
function texto_tutoria($fecha, $hora_inicio, $hora_fin) {
    $dias  = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($fecha);
    return $dias[(int)date('w', $t)] . ' ' . date('d', $t) . ' de ' . $meses[(int)date('n', $t) - 1]
        . ' · ' . date('g:i a', strtotime($hora_inicio)) . ' a ' . date('g:i a', strtotime($hora_fin));
}

// Un enlace es válido si está vacío o empieza con http:// o https:// (máximo 500 caracteres).
function enlace_valido($enlace) {
    if ($enlace === '') {
        return true;
    }
    return strlen($enlace) <= 500
        && filter_var($enlace, FILTER_VALIDATE_URL) !== false
        && preg_match('#^https?://#i', $enlace) === 1;
}