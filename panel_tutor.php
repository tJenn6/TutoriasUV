<?php
require_once "config/conexion.php";
require_once "config/sesion.php";
requerir_login('tutor');

$tutor_id = (int)$_SESSION['usuario_id'];
$ahora = new DateTimeImmutable('now', zona_tutorias());

// Estudiantes asignados a este tutor.
$consulta = $conexion->prepare(
    "SELECT u.nombre, u.correo
     FROM asignaciones a
     JOIN usuarios u ON u.id = a.estudiante_id
     WHERE a.tutor_id = ?
     ORDER BY u.nombre"
);
$consulta->bind_param("i", $tutor_id);
$consulta->execute();
$tutorados = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
$consulta->close();

// Sesiones del tutor (próximas y pasadas).
$consulta = $conexion->prepare(
    "SELECT id, fecha, hora_inicio, hora_fin, enlace_virtual
     FROM horarios
     WHERE tutor_id = ?
     ORDER BY fecha, hora_inicio"
);
$consulta->bind_param("i", $tutor_id);
$consulta->execute();
$horarios = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
$consulta->close();

$aviso = $_SESSION['aviso'] ?? null;
unset($_SESSION['aviso']);

$proximas = [];
$pasadas = [];

foreach ($horarios as $hr) {
    $estado = estado_horario($hr, $ahora);
    $hr['estado'] = $estado;
    if ($estado === 'pasada') {
        $pasadas[] = $hr;
    } else {
        $proximas[] = $hr;
    }
}

function etiqueta_estado($estado) {

    if ($estado === 'pasada') {
        return 'Pasada';
    }
    if ($estado === 'en_curso') {
        return 'En curso';
    }
    return 'Próxima';
}

encabezado_privado("Panel del Tutor");
?>

      <section class="section">
        <div class="section-head">
          <div>
            <span class="kicker">Panel del tutor</span>
            <h1 class="section-title">Hola, <?= h($_SESSION['usuario_nombre']) ?></h1>
          </div>
        </div>
      </section>

      <section class="section">
        <div class="section-head">
          <div>
            <h2 class="section-title">Sesiones</h2>
          </div>
          <a class="back-link panel-action-link" href="tutor_horarios.php#nueva-sesion">Nueva sesión</a>
        </div>

        <?php if ($aviso): ?>
          <div class="alert <?= $aviso['tipo'] === 'ok' ? 'alert--ok' : 'alert--error' ?>" role="alert"><?= h($aviso['texto']) ?></div>
        <?php endif; ?>

        <?php if (count($horarios) === 0): ?>
          <p class="card-text">Aún no tienes sesiones publicadas. Usa <strong>Nueva sesión</strong> para publicar la primera.</p>
        <?php else: ?>
          <div class="section section--tight">
            <div>
              <h3 class="section-subtitle">Pendientes y próximas</h3>
              <?php if (count($proximas) === 0): ?>
                <p class="card-text">No hay sesiones pendientes por ahora.</p>
              <?php else: ?>
                <div class="session-list">
                  <?php foreach ($proximas as $hr): ?>
                    <article class="session">
                      <div class="session__info">
                        <h4 class="card-title session__title">Tutoría <?= h(texto_tutoria($hr['fecha'], $hr['hora_inicio'], $hr['hora_fin'])) ?></h4>
                        <p class="session__meta">
                          <span class="session-badge session-badge--<?= h($hr['estado']) ?>"><?= h(etiqueta_estado($hr['estado'])) ?></span>
                          <?= $hr['enlace_virtual'] ? 'Virtual · ' : 'Presencial' ?>
                          <?php if ($hr['enlace_virtual']): ?>
                            <a href="<?= h($hr['enlace_virtual']) ?>" target="_blank" rel="noopener">Abrir enlace</a>
                          <?php endif; ?>
                        </p>
                      </div>

                      <div class="session__actions">
                        <form class="session__form" method="post" action="tutor_horarios.php">
                          <input type="hidden" name="token" value="<?= h(token_csrf()) ?>">
                          <input type="hidden" name="return_to" value="panel_tutor.php">
                          <input type="hidden" name="accion" value="guardar_enlace">
                          <input type="hidden" name="id" value="<?= (int)$hr['id'] ?>">
                          <input class="form-input" type="url" name="enlace_virtual" maxlength="500"
                                 value="<?= h($hr['enlace_virtual']) ?>" placeholder="Enlace virtual (opcional)">
                          <button class="btn btn--dark btn--sm" type="submit">Guardar enlace</button>
                        </form>

                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <div>
              <h3 class="section-subtitle">Sesiones pasadas</h3>
              <?php if (count($pasadas) === 0): ?>
                <p class="card-text">Aún no hay sesiones pasadas.</p>
              <?php else: ?>
                <div class="session-list">
                  <?php foreach ($pasadas as $hr): ?>
                    <article class="session">
                      <div class="session__info">
                        <h4 class="card-title session__title">Tutoría <?= h(texto_tutoria($hr['fecha'], $hr['hora_inicio'], $hr['hora_fin'])) ?></h4>
                        <p class="session__meta">
                          <span class="session-badge session-badge--pasada">Pasada</span>
                          <?= $hr['enlace_virtual'] ? 'Virtual · ' : 'Presencial' ?>
                          <?php if ($hr['enlace_virtual']): ?>
                            <a href="<?= h($hr['enlace_virtual']) ?>" target="_blank" rel="noopener">Abrir enlace</a>
                          <?php endif; ?>
                        </p>
                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </section>

      <section class="section">
        <div class="section-head">
          <div>
            <h2 class="section-title">Mis tutorados</h2>
          </div>
        </div>

        <?php if (count($tutorados) === 0): ?>
          <p class="card-text">Aún no tienes estudiantes asignados.</p>
        <?php else: ?>
          <div class="panel-grid">
            <?php foreach ($tutorados as $t): ?>
              <div class="info-card">
                <span class="info-card__label">Estudiante</span>
                <span class="info-card__value"><?= h($t['nombre']) ?></span>
                <span class="info-card__mail"><?= h($t['correo']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

<?php pie_privado(); ?>
