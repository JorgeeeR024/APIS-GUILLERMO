<?php
$conexion = new mysqli("localhost", "root", "", "apis");
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}
?>