<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }
require_once 'crud.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Usamos la función de crear
    crearRegistro($_POST['vehiculo'], $_POST['servicio'], $_POST['fecha'], $_POST['costo']);
    header("Location: servicio.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Servicio</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5; margin: 0; padding: 0; }
        .navbar { background-color: #1a73e8; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; color: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.2rem; font-weight: bold; display: flex; align-items: center; gap: 8px; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 600px; margin: 40px auto; }
        
        /* Estilos del Formulario */
        form { display: flex; flex-direction: column; gap: 15px; }
        label { font-weight: bold; color: #333; }
        input { padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 1rem; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 12px; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1.1rem; }
        .btn-cancelar { text-align: center; display: block; margin-top: 10px; color: #dc3545; text-decoration: none; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">Automotriz Web</div>
    </nav>

    <div class="container">
        <h1 style="margin-top: 0;">+ Nuevo Servicio</h1>
        <form method="POST" action="agregar.php">
            <label>Vehículo</label>
            <input type="text" name="vehiculo" placeholder="Ej. JAC S2 2015 o Suzuki Swift 1992" required>
            
            <label>Servicio Realizado</label>
            <input type="text" name="servicio" placeholder="Ej. Cambio de aceite y filtro" required>
            
            <label>Fecha</label>
            <input type="date" name="fecha" required>
            
            <label>Costo ($)</label>
            <input type="number" step="0.01" name="costo" placeholder="Ej. 45000" required>
            
            <button type="submit" class="btn-submit">Guardar Registro</button>
        </form>
        <a href="servicio.php" class="btn-cancelar">Cancelar y Volver</a>
    </div>
</body>
</html>