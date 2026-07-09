<?php
session_start();
// Si no está logueado o NO es admin, lo pateamos a servicio.php
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') { 
    header("Location: servicio.php"); 
    exit; 
}

require_once 'crud.php';
$logs = obtenerLogsConUsuarios(); // Usamos la nueva función con JOIN
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría de Logs - Automotriz Web</title>
    <style>
        /* Estilos Oscuros Heredados de servicio.php */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #0a0a0a; margin: 0; padding: 20px; color: #e5e5e5; }
        .container { background: #171717; padding: 30px; border-radius: 8px; border: 1px solid #333; max-width: 1000px; margin: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        h1 { color: #00E59B; margin: 0; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #333; }
        th { background-color: #222; color: white; }
        .btn-volver { background: #333; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; transition: background 0.2s; }
        .btn-volver:hover { background: #444; }
        
        /* Enlace de RUT clickable */
        .rut-link { color: #3b82f6; text-decoration: underline; cursor: pointer; background: none; border: none; font-size: 1rem; font-family: inherit; padding: 0; }
        .rut-link:hover { color: #60a5fa; }

        /* Modal de Usuario */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background: rgba(0,0,0,0.85); z-index: 2000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: #171717; padding: 30px; border-radius: 8px; width: 90%; max-width: 400px; border: 1px solid #333; box-shadow: 0 10px 25px rgba(0,0,0,0.9); position: relative; }
        .modal-box h2 { color: white; margin-top: 0; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .close-modal { position: absolute; top: 20px; right: 20px; background: none; border: none; color: #a3a3a3; font-size: 1.5rem; cursor: pointer; }
        .close-modal:hover { color: white; }
        .info-row { margin-bottom: 10px; border-bottom: 1px dashed #333; padding-bottom: 5px; }
        .info-label { font-weight: bold; color: #00E59B; display: block; font-size: 0.85rem; }
        .info-value { color: white; font-size: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <h1>Registro de Actividades (Logs)</h1>
            <a href="servicio.php" class="btn-volver">← Volver a Servicios</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Fecha / Hora</th>
                    <th>Usuario (RUT)</th>
                    <th>Acción Realizada</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): 
                    // Escapar datos para evitar XSS en JS
                    $nombreCompleto = htmlspecialchars(($log['nombre'] ?? '') . ' ' . ($log['apellido'] ?? ''));
                    $correo = htmlspecialchars($log['correo'] ?? 'Sin correo');
                    $nacimiento = htmlspecialchars($log['fecha_nacimiento'] ?? 'No registrada');
                    $direccion = htmlspecialchars($log['direccion'] ?? 'No registrada');
                    $rut = htmlspecialchars($log['usuario']);
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['fecha']); ?></td>
                    <td>
                        <button class="rut-link" onclick="verInfoUsuario('<?php echo $rut; ?>', '<?php echo $nombreCompleto; ?>', '<?php echo $correo; ?>', '<?php echo $nacimiento; ?>', '<?php echo $direccion; ?>')">
                            <?php echo $rut; ?>
                        </button>
                    </td>
                    <td><?php echo htmlspecialchars($log['accion']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="userInfoModal">
        <div class="modal-box">
            <button class="close-modal" onclick="cerrarModal()">×</button>
            <h2>Datos del Usuario</h2>
            
            <div class="info-row">
                <span class="info-label">RUT</span>
                <span class="info-value" id="modal-rut"></span>
            </div>
            <div class="info-row">
                <span class="info-label">Nombre Completo</span>
                <span class="info-value" id="modal-nombre"></span>
            </div>
            <div class="info-row">
                <span class="info-label">Correo Electrónico</span>
                <span class="info-value" id="modal-correo"></span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha de Nacimiento</span>
                <span class="info-value" id="modal-fecha"></span>
            </div>
            <div class="info-row">
                <span class="info-label">Dirección</span>
                <span class="info-value" id="modal-direccion"></span>
            </div>
        </div>
    </div>

    <script>
        function verInfoUsuario(rut, nombre, correo, fecha, direccion) {
            // Llenamos el modal con los datos
            document.getElementById('modal-rut').innerText = rut;
            document.getElementById('modal-nombre').innerText = nombre.trim() !== '' ? nombre : 'Perfil Incompleto';
            document.getElementById('modal-correo').innerText = correo;
            document.getElementById('modal-fecha').innerText = fecha;
            document.getElementById('modal-direccion').innerText = direccion;
            
            // Mostramos el modal
            document.getElementById('userInfoModal').classList.add('active');
        }

        function cerrarModal() {
            document.getElementById('userInfoModal').classList.remove('active');
        }
    </script>
</body>
</html>