<?php
session_start();
require_once 'crud.php';

// Si hay una sesión activa, registramos el log de salida
if (isset($_SESSION['rut'])) {
    registrarLog("Cerró sesión en el sistema", $_SESSION['rut']);
}

// Destruimos todas las variables y la sesión
session_unset();
session_destroy();

// Redirigimos de vuelta al login
header("Location: login.php");
exit;
?>