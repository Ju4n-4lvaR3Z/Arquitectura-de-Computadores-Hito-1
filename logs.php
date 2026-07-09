<?php
session_start();
// Si no está logueado o NO es admin, lo pateamos a servicio.php
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') { 
    header("Location: servicio.php"); 
    exit; 
}

require_once 'crud.php';
$db = conectarBD();

$mensaje = '';

// --- LÓGICA PARA HACER ADMIN A UN USUARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hacer_admin') {
    $rut_destino = trim($_POST['rut_destino']);
    
    // Verificar si existe el usuario
    $stmt = $db->prepare("SELECT id, rol FROM usuarios WHERE rut = ?");
    $stmt->execute([$rut_destino]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        if ($usuario['rol'] === 'admin') {
            $mensaje = "<div style='background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 10px; border: 1px solid #ef4444; border-radius: 4px; margin-bottom: 20px;'>El usuario ya es Administrador.</div>";
        } else {
            // Actualizamos el rol
            $upd = $db->prepare("UPDATE usuarios SET rol = 'admin' WHERE rut = ?");
            $upd->execute([$rut_destino]);
            
            // Registramos la acción en el log
            registrarLog("Promovió a Administrador al usuario RUT: " . $rut_destino);
            
            $mensaje = "<div style='background: rgba(0, 229, 155, 0.1); color: #00E59B; padding: 10px; border: 1px solid #00E59B; border-radius: 4px; margin-bottom: 20px;'>Usuario promovido a Administrador exitosamente.</div>";
        }
    } else {
        $mensaje = "<div style='background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 10px; border: 1px solid #ef4444; border-radius: 4px; margin-bottom: 20px;'>El RUT ingresado no existe en el sistema.</div>";
    }
}

$logs = obtenerLogsConUsuarios(); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría de Logs - Automotriz Web</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #0a0a0a; margin: 0; padding: 20px; color: #e5e5e5; }
        .container { background: #171717; padding: 30px; border-radius: 8px; border: 1px solid #333; max-width: 1000px; margin: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        h1 { color: #00E59B; margin: 0; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #333; }
        th { background-color: #222; color: white; }
        
        .btn-volver { background: #333; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; transition: background 0.2s; }
        .btn-volver:hover { background: #444; }
        
        .btn-admin { background: #eab308; color: black; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; transition: background 0.2s; }
        .btn-admin:hover { background: #ca8a04; }
        
        .rut-link { color: #3b82f6; text-decoration: underline; cursor: pointer; background: none; border: none; font-size: 1rem; font-family: inherit; padding: 0; }
        .rut-link:hover { color: #60a5fa; }

        /* Modales */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background: rgba(0,0,0,0.85); z-index: 2000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: #171717; padding: 30px; border-radius: 8px; width: 90%; max-width: 400px; border: 1px solid #333; box-shadow: 0 10px 25px rgba(0,0,0,0.9); position: relative; }
        .modal-box h2 { color: white; margin-top: 0; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .close-modal { position: absolute; top: 20px; right: 20px; background: none; border: none; color: #a3a3a3; font-size: 1.5rem; cursor: pointer; }
        .close-modal:hover { color: white; }
        
        .info-row { margin-bottom: 10px; border-bottom: 1px dashed #333; padding-bottom: 5px; }
        .info-label { font-weight: bold; color: #00E59B; display: block; font-size: 0.85rem; }
        .info-value { color: white; font-size: 1rem; }

        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; font-size: 0.9rem; font-weight: bold; margin-bottom: 5px; color: #d4d4d4; }
        .form-group input { width: 100%; padding: 10px; background: #262626; border: 1px solid #404040; color: white; border-radius: 4px; box-sizing: border-box; font-size: 1rem; outline: none; }
        .form-group input:focus { border-color: #eab308; }
        .btn-submit { width: 100%; padding: 12px; border: none; border-radius: 4px; font-weight: bold; font-size: 1rem; cursor: pointer; margin-top: 10px; background: #eab308; color: black; }
        .btn-submit:hover { background: #ca8a04; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <h1>Registro de Actividades (Logs)</h1>
            <div style="display: flex; gap: 10px;">
                <button class="btn-admin" onclick="abrirModal('adminModal')">👑 Promover a Admin</button>
                <a href="servicio.php" class="btn-volver">← Volver a Servicios</a>
            </div>
        </div>
        
        <?php echo $mensaje; // Mostrar alertas de error o éxito ?>

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
                    $nombreCompleto = htmlspecialchars(($log['nombre'] ?? '') . ' ' . ($log['apellido'] ?? ''));
                    $correo = htmlspecialchars($log['correo'] ?? 'Sin correo');
                    $nacimiento = htmlspecialchars($log['fecha_nacimiento'] ?? 'No registrada');
                    $direccion = htmlspecialchars($log['direccion'] ?? 'No registrada');
                    $rut = htmlspecialchars($log['usuario']);
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['fecha']); ?></td>
                    <td>
                        <button class="rut-link" onclick="verInfoUsuario('<?php echo $rut; ?>', '<?php echo $nombreCompleto; ?>', '<?php echo $correo; ?>', '<?php echo $nacimiento; ?>', '<?php echo $direccion; ?>', '<?php echo htmlspecialchars($log['ultima_ip'] ?? 'Desconocida'); ?>')">
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
            <button class="close-modal" onclick="cerrarModal('userInfoModal')">×</button>
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
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">Última IP de Ingreso</span>
                <span class="info-value" id="modal-ip" style="font-family: monospace; color: #3b82f6;"></span>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="adminModal">
        <div class="modal-box">
            <button class="close-modal" onclick="cerrarModal('adminModal')">×</button>
            <h2 style="color: #eab308;">👑 Promover a Administrador</h2>
            <form method="POST" action="logs.php">
                <input type="hidden" name="action" value="hacer_admin">
                
                <div class="form-group">
                    <label>RUT del Usuario</label>
                    <input type="text" name="rut_destino" placeholder="Ej: 12.345.678-9" required>
                </div>
                
                <p style="color: #a3a3a3; font-size: 0.85rem; margin-bottom: 20px; line-height: 1.4;">
                    Al convertir a un usuario en administrador, le estarás dando acceso completo a esta vista de Auditoría, permitiéndole ver los registros, direcciones IP y promover a otros usuarios.
                </p>
                
                <button type="submit" class="btn-submit">Otorgar Permisos de Administrador</button>
            </form>
        </div>
    </div>

    <script>
        // Función para abrir modales genéricos
        function abrirModal(id) {
            document.getElementById(id).classList.add('active');
        }

        // Función para cerrar modales genéricos
        function cerrarModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        // Función para inyectar datos y abrir el modal de usuario
        function verInfoUsuario(rut, nombre, correo, fecha, direccion, ip) {
            document.getElementById('modal-rut').innerText = rut;
            document.getElementById('modal-nombre').innerText = nombre.trim() !== '' ? nombre : 'Perfil Incompleto';
            document.getElementById('modal-correo').innerText = correo;
            document.getElementById('modal-fecha').innerText = fecha;
            document.getElementById('modal-direccion').innerText = direccion;
            document.getElementById('modal-ip').innerText = ip;
            
            abrirModal('userInfoModal');
        }
    </script>
</body>
</html>