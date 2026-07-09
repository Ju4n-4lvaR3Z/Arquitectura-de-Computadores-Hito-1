<?php
require_once 'crud.php';

try {
    $db = conectarBD();
    // Eliminamos las tablas para que crud.php las vuelva a crear limpias
    $db->exec("DROP TABLE IF EXISTS usuarios");
    $db->exec("DROP TABLE IF EXISTS logs");
    
    echo "<h3 style='color: green;'>¡Base de datos limpiada con éxito!</h3>";
    echo "<p>Las tablas 'usuarios' y 'logs' han sido eliminadas.</p>";
    echo "<a href='login.php'>Volver al Login</a>";
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
}
?>