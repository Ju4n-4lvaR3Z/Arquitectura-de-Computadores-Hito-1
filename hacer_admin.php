<?php
require_once 'crud.php';
session_start();
$db = conectarBD();
// Reemplaza con el RUT que usaste para crear tu cuenta
$tu_rut = '21.583.496-4'; 

$db->exec("UPDATE usuarios SET rol = 'admin' WHERE rut = '$tu_rut'");
echo "Listo, el RUT $tu_rut ahora es administrador. Cierra sesión y vuelve a entrar.";
?>