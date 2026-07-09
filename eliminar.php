<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }
require_once 'crud.php';

// Si recibimos un ID, lo eliminamos a través de nuestra función
if (isset($_GET['id'])) {
    eliminarRegistro($_GET['id']);
}

header("Location: servicio.php");
exit;
?>