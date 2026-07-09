<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }
require_once 'crud.php';

// Guardar los cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    actualizarRegistro($_POST['id'], $_POST['vehiculo'], $_POST['servicio'], $_POST['fecha'], $_POST['costo']);
    header("Location: servicio.php");
    exit;
}

// Cargar datos actuales
$registro = null;
if (isset($_GET['id'])) {
    $registro = obtenerRegistroPorId($_GET['id']);
}

if (!$registro) {
    header("Location: servicio.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Servicio</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5; margin: 0; padding: 0; }
        .navbar { background-color: #1a73e8; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; color: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.2rem; font-weight: bold; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 600px; margin: 40px auto; }
        
        form { display: flex; flex-direction: column; gap: 15px; }
        label { font-weight: bold; color: #333; }
        input { padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 1rem; }
        .btn-update { background: #ffc107; color: black; border: none; padding: 12px; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1.1rem; }
        .btn-cancelar { text-align: center; display: block; margin-top: 10px; color: #666; text-decoration: none; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">Automotriz Web</div>
    </nav>

    <div class="container">
        <h1 style="margin-top: 0; color: #d39e00;">Editar Servicio #<?php echo htmlspecialchars($registro['id']); ?></h1>
        <form method="POST" action="editar.php">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($registro['id']); ?>">
            
            <label>Vehículo</label>
            <input type="text" name="vehiculo" value="<?php echo htmlspecialchars($registro['vehiculo']); ?>" required>
            
            <label>Servicio Realizado</label>
            <input type="text" name="servicio" value="<?php echo htmlspecialchars($registro['servicio']); ?>" required>
            
            <label>Fecha</label>
            <input type="date" name="fecha" value="<?php echo htmlspecialchars($registro['fecha']); ?>" required>
            
            <label>Costo ($)</label>
            <input type="number" step="0.01" name="costo" value="<?php echo htmlspecialchars($registro['costo']); ?>" required>
            
            <button type="submit" class="btn-update">💾 Guardar Cambios</button>
        </form>
        <a href="servicio.php" class="btn-cancelar">Cancelar</a>
    </div>
</body>
</html>