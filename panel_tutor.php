<?php
require_once "config/conexion.php";
require_once "config/sesion.php";
requerir_login('tutor');

$id = $_SESSION['usuario_id'];

// Estudiantes asignados a este tutor.
$consulta = $conexion->prepare(
    "SELECT u.nombre, u.correo
     FROM asignaciones a
     JOIN usuarios u ON u.id = a.estudiante_id
     WHERE a.tutor_id = ?
     ORDER BY u.nombre"
);
$consulta->bind_param("i", $id);
$consulta->execute();
$tutorados = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
$consulta->close();


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
        <div class="panel-grid">
          <a class="info-card" href="tutor_horarios.php">
            <span class="info-card__label">Tutorías</span>
            <span class="info-card__value">Mis horarios</span>
            <span class="info-card__mail">Publica y administra tus tutorías</span>
          </a>
        </div>
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