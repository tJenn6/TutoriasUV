<?php
require_once "config/conexion.php";
require_once "config/sesion.php";
requerir_login('tutor');

$tutor_id = (int)$_SESSION['usuario_id'];
$hoy = (new DateTime('today', new DateTimeZone('America/Mexico_City')))->format('Y-m-d');

// Guarda un aviso en la sesión, regresa a esta página y termina.
function aviso($tipo, $texto) {
    $_SESSION['aviso'] = ['tipo' => $tipo, 'texto' => $texto];
    header('Location: tutor_horarios.php');
    exit;
}

// Revisa que el texto sea una fecha/hora real con el formato indicado.
function formato_ok($valor, $formato) {
    $d = DateTime::createFromFormat($formato, $valor);
    return $d !== false && $d->format($formato) === $valor;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!token_valido($_POST['token'] ?? '')) {
        aviso('error', 'El formulario expiró. Intenta de nuevo.');
    }

    $accion = $_POST['accion'] ?? '';

    // Publicar una tutoría nueva
    if ($accion === 'crear') {
        $fecha  = $_POST['fecha'] ?? '';
        $inicio = $_POST['hora_inicio'] ?? '';
        $fin    = $_POST['hora_fin'] ?? '';
        $enlace = trim($_POST['enlace_virtual'] ?? '');

        if (!formato_ok($fecha, 'Y-m-d') || !formato_ok($inicio, 'H:i') || !formato_ok($fin, 'H:i')) {
            aviso('error', 'Revisa la fecha y las horas.');
        }
        if ($fecha < $hoy) {
            aviso('error', 'La fecha no puede ser anterior a hoy.');
        }
        if ($fin <= $inicio) {
            aviso('error', 'La hora de fin debe ser posterior a la hora de inicio.');
        }
        if (!enlace_valido($enlace)) {
            aviso('error', 'El enlace debe empezar con http:// o https://');
        }

        $enlace_bd = ($enlace === '') ? null : $enlace;
        $consulta = $conexion->prepare(
            "INSERT INTO horarios (tutor_id, fecha, hora_inicio, hora_fin, enlace_virtual)
             VALUES (?, ?, ?, ?, ?)"
        );
        $consulta->bind_param("issss", $tutor_id, $fecha, $inicio, $fin, $enlace_bd);
        $consulta->execute();
        $consulta->close();
        aviso('ok', 'Tutoría publicada.');
    }

    // Cancelar o reactivar una tutoría
    if ($accion === 'cancelar' || $accion === 'reactivar') {
        $id = (int)($_POST['id'] ?? 0);
        $disponible = ($accion === 'reactivar') ? 1 : 0;
        $consulta = $conexion->prepare(
            "UPDATE horarios SET disponible = ? WHERE id = ? AND tutor_id = ?"
        );
        $consulta->bind_param("iii", $disponible, $id, $tutor_id);
        $consulta->execute();
        $consulta->close();
        aviso('ok', $disponible ? 'Tutoría reactivada.' : 'Tutoría cancelada.');
    }

    // Guardar o quitar el enlace de la sesión virtual
    if ($accion === 'guardar_enlace') {
        $id     = (int)($_POST['id'] ?? 0);
        $enlace = trim($_POST['enlace_virtual'] ?? '');

        if (!enlace_valido($enlace)) {
            aviso('error', 'El enlace debe empezar con http:// o https://');
        }

        $enlace_bd = ($enlace === '') ? null : $enlace;
        $consulta = $conexion->prepare(
            "UPDATE horarios SET enlace_virtual = ? WHERE id = ? AND tutor_id = ?"
        );
        $consulta->bind_param("sii", $enlace_bd, $id, $tutor_id);
        $consulta->execute();
        $consulta->close();
        aviso('ok', $enlace === '' ? 'Enlace quitado.' : 'Enlace guardado.');
    }

    aviso('error', 'Acción no válida.');
}

// Aviso guardado antes de redirigir (se muestra una sola vez).
$aviso = $_SESSION['aviso'] ?? null;
unset($_SESSION['aviso']);

// Tutorías de hoy en adelante.
$consulta = $conexion->prepare(
    "SELECT id, fecha, hora_inicio, hora_fin, enlace_virtual, disponible
     FROM horarios
     WHERE tutor_id = ? AND fecha >= ?
     ORDER BY fecha, hora_inicio"
);
$consulta->bind_param("is", $tutor_id, $hoy);
$consulta->execute();
$horarios = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
$consulta->close();

encabezado_privado("Mis horarios");
?>

      <section class="section">
        <div class="section-head">
          <div>
            <span class="kicker">Panel del tutor</span>
            <h1 class="section-title">Mis horarios</h1>
          </div>
          <a class="back-link" href="panel_tutor.php">← Volver al panel</a>
        </div>

        <?php if ($aviso): ?>
          <div class="alert <?= $aviso['tipo'] === 'ok' ? 'alert--ok' : 'alert--error' ?>" role="alert"><?= h($aviso['texto']) ?></div>
        <?php endif; ?>
      </section>

      <section class="section">
        <div class="access-card access-card--teacher">
          <div>
            <h2 class="card-title">Publicar una tutoría</h2>
            <p class="card-text">Tus tutorados la verán con su fecha y hora. El enlace virtual es opcional: ponlo solo si acuerdan una sesión virtual.</p>

            <form class="login-form" method="post" action="tutor_horarios.php">
              <input type="hidden" name="token" value="<?= h(token_csrf()) ?>">
              <input type="hidden" name="accion" value="crear">

              <div class="form-row">
                <div class="form-field">
                  <label class="form-label" for="fecha">Fecha</label>
                  <input class="form-input" type="date" id="fecha" name="fecha" min="<?= h($hoy) ?>" required>
                </div>
                <div class="form-field">
                  <label class="form-label" for="hora_inicio">Hora de inicio</label>
                  <input class="form-input" type="time" id="hora_inicio" name="hora_inicio" required>
                </div>
                <div class="form-field">
                  <label class="form-label" for="hora_fin">Hora de fin</label>
                  <input class="form-input" type="time" id="hora_fin" name="hora_fin" required>
                </div>
              </div>

              <div class="form-field">
                <label class="form-label" for="enlace_virtual">Enlace de la sesión virtual (opcional)</label>
                <input class="form-input" type="url" id="enlace_virtual" name="enlace_virtual" maxlength="500" placeholder="https://meet.google.com/...">
              </div>

              <button class="btn btn--dark btn--block" type="submit">Publicar tutoría</button>
            </form>
          </div>
        </div>
      </section>

      <section class="section">
        <div class="section-head">
          <div>
            <h2 class="section-title">Próximas tutorías</h2>
          </div>
        </div>

        <?php if (count($horarios) === 0): ?>
          <p class="card-text">Aún no has publicado tutorías próximas.</p>
        <?php else: ?>
          <div class="session-list">
            <?php foreach ($horarios as $hr): ?>
              <article class="session<?= $hr['disponible'] ? '' : ' session--cancelled' ?>">
                <div class="session__info">
                  <h3 class="card-title session__title">Tutoría <?= h(texto_tutoria($hr['fecha'], $hr['hora_inicio'], $hr['hora_fin'])) ?></h3>
                  <p class="session__meta">
                    <?php if (!$hr['disponible']): ?>
                      Cancelada
                    <?php elseif ($hr['enlace_virtual']): ?>
                      Virtual · <a href="<?= h($hr['enlace_virtual']) ?>" target="_blank" rel="noopener">Abrir enlace</a>
                    <?php else: ?>
                      Presencial
                    <?php endif; ?>
                  </p>
                </div>

                <div class="session__actions">
                  <form class="session__form" method="post" action="tutor_horarios.php">
                    <input type="hidden" name="token" value="<?= h(token_csrf()) ?>">
                    <input type="hidden" name="accion" value="guardar_enlace">
                    <input type="hidden" name="id" value="<?= (int)$hr['id'] ?>">
                    <input class="form-input" type="url" name="enlace_virtual" maxlength="500"
                           value="<?= h($hr['enlace_virtual']) ?>" placeholder="Enlace virtual (opcional)">
                    <button class="btn btn--dark btn--sm" type="submit">Guardar enlace</button>
                  </form>

                  <form method="post" action="tutor_horarios.php">
                    <input type="hidden" name="token" value="<?= h(token_csrf()) ?>">
                    <input type="hidden" name="accion" value="<?= $hr['disponible'] ? 'cancelar' : 'reactivar' ?>">
                    <input type="hidden" name="id" value="<?= (int)$hr['id'] ?>">
                    <button class="btn btn--outline btn--sm" type="submit"<?= $hr['disponible'] ? ' onclick="return confirm(\'¿Cancelar esta tutoría?\')"' : '' ?>>
                      <?= $hr['disponible'] ? 'Cancelar tutoría' : 'Reactivar tutoría' ?>
                    </button>
                  </form>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

<?php pie_privado(); ?>