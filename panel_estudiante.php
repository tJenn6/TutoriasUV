<?php
require_once "config/conexion.php";
require_once "config/sesion.php";
requerir_login('estudiante');

$estudiante_id = (int)$_SESSION['usuario_id'];
$ahora = new DateTimeImmutable('now', zona_tutorias());

$consulta = $conexion->prepare(
    "SELECT u.id, u.nombre, u.correo
     FROM asignaciones a
     JOIN usuarios u ON u.id = a.tutor_id
     WHERE a.estudiante_id = ?
     ORDER BY u.nombre
     LIMIT 1"
);
$consulta->bind_param("i", $estudiante_id);
$consulta->execute();
$tutor = $consulta->get_result()->fetch_assoc();
$consulta->close();

$sesiones = [];
if ($tutor) {
    $consulta = $conexion->prepare(
        "SELECT id, fecha, hora_inicio, hora_fin, enlace_virtual, disponible
         FROM horarios
         WHERE tutor_id = ?
         ORDER BY fecha, hora_inicio"
    );
    $consulta->bind_param("i", $tutor['id']);
    $consulta->execute();
    $sesiones = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
    $consulta->close();
}

foreach ($sesiones as &$sesion) {
    $sesion['estado'] = estado_horario($sesion, $ahora);
}
unset($sesion);

function estado_legible($estado) {
    if ($estado === 'cancelada') {
        return 'Cancelada';
    }
    if ($estado === 'pasada') {
        return 'Pasada';
    }
    if ($estado === 'en_curso') {
        return 'En curso';
    }
    return 'Próxima';
}

encabezado_privado("Panel del estudiante");
?>

      <section class="section">
        <div class="section-head">
          <div>
            <span class="kicker">Panel del tutorado</span>
            <h1 class="section-title">Hola, <?= h($_SESSION['usuario_nombre']) ?></h1>
          </div>
        </div>
      </section>

      <section class="section">
        <div class="section-head">
          <div>
            <h2 class="section-title">Mi tutor asignado</h2>
          </div>
        </div>

        <?php if (!$tutor): ?>
          <p class="card-text">Aún no tienes tutor asignado. Cuando exista una asignación, aquí verás sus datos y sesiones.</p>
        <?php else: ?>
          <div class="panel-grid panel-grid--two">
            <div class="info-card">
              <span class="info-card__label">Tutor</span>
              <span class="info-card__value"><?= h($tutor['nombre']) ?></span>
              <span class="info-card__mail"><?= h($tutor['correo']) ?></span>
            </div>
          </div>
        <?php endif; ?>
      </section>

      <section class="section">
        <div class="section-head">
          <div>
            <h2 class="section-title">Sesiones publicadas</h2>
          </div>
        </div>

        <?php if (!$tutor): ?>
          <p class="card-text">Sin tutor asignado no hay sesiones por mostrar.</p>
        <?php elseif (count($sesiones) === 0): ?>
          <p class="card-text">Tu tutor aún no publica sesiones.</p>
        <?php else: ?>
          <div class="session-list">
            <?php foreach ($sesiones as $sesion): ?>
              <article class="session<?= $sesion['estado'] === 'cancelada' ? ' session--cancelled' : '' ?>">
                <div class="session__info">
                  <h3 class="card-title session__title">Tutoría <?= h(texto_tutoria($sesion['fecha'], $sesion['hora_inicio'], $sesion['hora_fin'])) ?></h3>
                  <p class="session__meta">
                    <span class="session-badge session-badge--<?= h($sesion['estado']) ?>"><?= h(estado_legible($sesion['estado'])) ?></span>
                    <?= $sesion['enlace_virtual'] ? 'Virtual · ' : 'Presencial' ?>
                    <?php if ($sesion['enlace_virtual']): ?>
                      <a href="<?= h($sesion['enlace_virtual']) ?>" target="_blank" rel="noopener">Abrir enlace</a>
                    <?php endif; ?>
                  </p>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="section">
        <div class="section-head">
          <div>
            <h2 class="section-title">Seguimiento de asistencia</h2>
          </div>
        </div>
        <p class="card-text">Esta sección queda preparada para mostrar asistencias cuando se confirme la estructura oficial para registro en la base de datos actual.</p>
      </section>

      <section class="section">
        <p class="card-text">Esta pantalla deja lista la base de próximas entregas: foro, materiales, solicitudes de tutoría, temas/acuerdos y calificación con comentarios.</p>
      </section>

<?php pie_privado(); ?>
