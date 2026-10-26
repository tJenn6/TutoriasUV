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

$seguimiento = null;
$tiene_tabla_asistencias = false;
$columnas_asistencia = [];
$tabla = $conexion->query("SHOW TABLES LIKE 'asistencias'");
if ($tabla && $tabla->num_rows > 0) {
    $tiene_tabla_asistencias = true;
    $cols = $conexion->query("SHOW COLUMNS FROM asistencias");
    if ($cols) {
        while ($col = $cols->fetch_assoc()) {
            $columnas_asistencia[] = $col['Field'];
        }
    }
}

if (
    $tiene_tabla_asistencias
    && in_array('estudiante_id', $columnas_asistencia, true)
    && in_array('asistio', $columnas_asistencia, true)
) {
    // Compatibilidad: si existe horario_id, filtramos por tutor para evitar mezclar otras tutorías.
    if ($tutor && in_array('horario_id', $columnas_asistencia, true)) {
        $consulta = $conexion->prepare(
            "SELECT COUNT(*) AS total_registros,
                    COALESCE(SUM(CASE WHEN a.asistio = 1 THEN 1 ELSE 0 END), 0) AS asistidas,
                    COALESCE(SUM(CASE WHEN a.asistio = 0 THEN 1 ELSE 0 END), 0) AS faltas
             FROM asistencias a
             JOIN horarios h ON h.id = a.horario_id
             WHERE a.estudiante_id = ? AND h.tutor_id = ?"
        );
        $consulta->bind_param("ii", $estudiante_id, $tutor['id']);
    } else {
        $consulta = $conexion->prepare(
            "SELECT COUNT(*) AS total_registros,
                    COALESCE(SUM(CASE WHEN asistio = 1 THEN 1 ELSE 0 END), 0) AS asistidas,
                    COALESCE(SUM(CASE WHEN asistio = 0 THEN 1 ELSE 0 END), 0) AS faltas
             FROM asistencias
             WHERE estudiante_id = ?"
        );
        $consulta->bind_param("i", $estudiante_id);
    }

    $consulta->execute();
    $seguimiento = $consulta->get_result()->fetch_assoc();
    $consulta->close();
}

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

        <?php if ($seguimiento): ?>
          <div class="panel-grid panel-grid--two">
            <div class="info-card">
              <span class="info-card__label">Sesiones registradas</span>
              <span class="info-card__value"><?= (int)$seguimiento['total_registros'] ?></span>
            </div>
            <div class="info-card">
              <span class="info-card__label">Asistencias</span>
              <span class="info-card__value"><?= (int)$seguimiento['asistidas'] ?></span>
              <span class="info-card__mail">Faltas: <?= (int)$seguimiento['faltas'] ?></span>
            </div>
          </div>
        <?php elseif ($tiene_tabla_asistencias): ?>
          <p class="card-text">Todavía no hay asistencias registradas para tus sesiones.</p>
        <?php else: ?>
          <p class="card-text">El seguimiento de asistencia aún no está habilitado en la base de datos. Ejecuta la migración <code>migrations/20261026_create_asistencias.sql</code> para activarlo.</p>
        <?php endif; ?>
      </section>

      <section class="section">
        <p class="card-text">Esta pantalla deja lista la base de próximas entregas: foro, materiales, solicitudes de tutoría, temas/acuerdos y calificación con comentarios.</p>
      </section>

<?php pie_privado(); ?>
