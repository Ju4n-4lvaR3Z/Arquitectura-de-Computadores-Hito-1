<?php
require_once 'crud.php';

try {
    $db = conectarBD();
    // Eliminamos solo la tabla de usuarios para forzar su recreación
    $db->exec("DROP TABLE IF EXISTS usuarios");
    
    echo "<h3 style='color: green;'>¡Tabla de usuarios reseteada con éxito!</h3>";
    echo "<p>Vuelve al login para intentar registrarte de nuevo.</p>";
    echo "<a href='login.php'>Ir al Login</a>";
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
}
?>