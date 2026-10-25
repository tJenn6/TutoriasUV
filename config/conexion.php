<?php

$servidor = "localhost";
$usuario = "root";
$contrasena = "";
$base_datos = "tutorias_uv";

$conexion = new mysqli(
    $servidor,
    $usuario,
    $contrasena,
    $base_datos
);

if ($conexion->connect_error) {
    die("Error de conexión con la base de datos.");
}

$conexion->set_charset("utf8mb4");

?>
