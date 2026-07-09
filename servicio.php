<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }
require_once 'crud.php';

$db = conectarBD();
$usuario_actual = $_SESSION['rut'] ?? 'Sistema';

// --- 1. PROCESAMIENTO DE PERFIL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'completar_perfil' || $_POST['action'] === 'actualizar_perfil') {
        if (strtotime($_POST['fecha_nacimiento']) > time()) {
            die("<script>alert('La fecha de nacimiento no puede ser en el futuro.'); window.history.back();</script>");
        }
        $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, fecha_nacimiento = ?, direccion = ? WHERE id = ?");
        $stmt->execute([$_POST['nombre'], $_POST['apellido'], $_POST['fecha_nacimiento'], $_POST['direccion'], $_SESSION['usuario_id']]);
        $mensajeLog = ($_POST['action'] === 'completar_perfil') ? "Completó su perfil personal inicial" : "Actualizó sus datos personales en 'Mi Cuenta'";
        registrarLog($mensajeLog);
        header("Location: servicio.php"); 
        exit;
    }
}

// --- 2. PROCESAMIENTO CRUD UNIFICADO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crud_action'])) {
    $accion = $_POST['crud_action'];
    
    if ($accion === 'agregar') {
        crearRegistro($_POST['vehiculo'], $_POST['servicio'], $_POST['fecha'], $_POST['costo']);
    } elseif ($accion === 'editar') {
        actualizarRegistro($_POST['id'], $_POST['vehiculo'], $_POST['servicio'], $_POST['fecha'], $_POST['costo']);
    } elseif ($accion === 'eliminar') {
        eliminarRegistro($_POST['id']);
    }
    
    header("Location: servicio.php");
    exit;
}

// --- 3. OBTENER DATOS PARA LA VISTA ---
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuarioActual = $stmt->fetch(PDO::FETCH_ASSOC);

$faltaPerfil = empty($usuarioActual['nombre']);
$registros = obtenerRegistros();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios - Automotriz Web</title>
    <style>
        /* --- Tema Oscuro Base --- */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #0a0a0a; margin: 0; padding: 0; color: #e5e5e5; }
        
        /* --- Navbar --- */
        .navbar { background-color: #171717; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; position: sticky; top: 0; z-index: 100;}
        .navbar-brand { font-size: 1.2rem; font-weight: bold; display: flex; align-items: center; gap: 8px; color: white; }
        .navbar-brand img { width: 150px; height: auto; filter: invert(1) brightness(100); }
        .navbar-links { display: flex; align-items: center; gap: 15px; }
        .navbar-links a, .navbar-links button { background: none; border: none; color: #a3a3a3; font-size: 0.95rem; text-decoration: none; cursor: pointer; transition: color 0.2s; font-family: inherit; display: flex; align-items: center; gap: 5px; }
        .navbar-links a:hover, .navbar-links button:hover { color: white; }
        
        /* Botones CRUD especiales en Navbar */
        .btn-nav-crud { border: 1px solid #333 !important; padding: 6px 12px; border-radius: 4px; background: #222 !important; }
        .btn-nav-crud:hover { background: #333 !important; color: #00E59B !important; border-color: #00E59B !important; }
        
        .menu-toggle { display: none; background: none; border: none; color: white; font-size: 1.8rem; cursor: pointer; }
        
        /* --- Sidebar Móvil --- */
        .sidebar { position: fixed; top: 0; right: -250px; width: 250px; height: 100vh; background-color: #171717; border-left: 1px solid #333; transition: right 0.3s ease; z-index: 1000; display: flex; flex-direction: column; padding-top: 60px; }
        .sidebar.open { right: 0; } 
        .sidebar a, .sidebar button { color: #a3a3a3; text-decoration: none; padding: 15px 25px; font-size: 1.1rem; border-bottom: 1px solid #333; background: none; border: none; text-align: left; cursor: pointer; width: 100%; display: block; }
        .sidebar a:hover, .sidebar button:hover { background-color: #222; color: white; }
        .close-btn { position: absolute; top: 15px; right: 20px; background: none; border: none; color: white; font-size: 2rem; cursor: pointer; }
        
        /* --- Tabla y Contenedores --- */
        .container { background: #171717; padding: 30px; border-radius: 8px; border: 1px solid #333; max-width: 1000px; margin: 40px auto; overflow-x: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h1 { color: white; margin-top: 0; font-size: 1.5rem; border-bottom: 1px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #333; }
        th { background-color: #222; color: #00E59B; font-weight: 600; }
        
        .btn-edit { background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 0.9rem; cursor: pointer; }
        .btn-delete { background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 0.9rem; cursor: pointer; }
        
        /* --- Modales --- */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background: rgba(0,0,0,0.85); z-index: 2000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: #171717; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; border: 1px solid #333; box-shadow: 0 10px 25px rgba(0,0,0,0.9); position: relative; max-height: 90vh; overflow-y: auto; }
        .modal-box h2 { color: white; margin-top: 0; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .close-modal { position: absolute; top: 20px; right: 20px; background: none; border: none; color: #a3a3a3; font-size: 1.5rem; cursor: pointer; }
        .close-modal:hover { color: white; }
        
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; font-size: 0.9rem; font-weight: bold; margin-bottom: 5px; color: #d4d4d4; }
        .form-group input, .form-group select { width: 100%; padding: 10px; background: #262626; border: 1px solid #404040; color: white; border-radius: 4px; box-sizing: border-box; font-size: 1rem; outline: none; transition: border-color 0.2s; }
        .form-group input:focus, .form-group select:focus { border-color: #00E59B; }
        
        .btn-submit { background: #00E59B; color: black; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 1rem; margin-top: 10px; transition: background 0.3s; }
        .btn-submit:hover { background: #00c483; }
        
        @media (max-width: 900px) {
            .navbar-links { display: none; }
            .menu-toggle { display: block; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">
            <img src="img/car.svg" alt="Logo">
            Automotriz Web
        </div>
        <div class="navbar-links">
            <button class="btn-nav-crud" onclick="abrirModal('agregarModal')">➕ Agregar</button>
            <button class="btn-nav-crud" onclick="abrirModal('editarModal')">✏️ Editar</button>
            <button class="btn-nav-crud" onclick="abrirModal('eliminarModal')">🗑️ Eliminar</button>
            
            <div style="width: 1px; height: 20px; background: #333; margin: 0 10px;"></div>
            
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <a href="logs.php">📊 Auditoría</a>
            <?php endif; ?>
            <button onclick="abrirModal('cuentaModal')" style="color: #00E59B;">👤 Mi Cuenta</button>
        </div>
        <button class="menu-toggle" onclick="toggleMenu()">☰</button>
    </nav>

    <aside class="sidebar" id="sidebar">
        <button class="close-btn" onclick="toggleMenu()">×</button>
        <button onclick="abrirModal('agregarModal'); toggleMenu();">➕ Agregar Servicio</button>
        <button onclick="abrirModal('editarModal'); toggleMenu();">✏️ Editar Servicio</button>
        <button onclick="abrirModal('eliminarModal'); toggleMenu();">🗑️ Eliminar Servicio</button>
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
            <a href="logs.php">📊 Auditoría</a>
        <?php endif; ?>
        <button onclick="abrirModal('cuentaModal'); toggleMenu();" style="color: #00E59B;">👤 Mi Cuenta</button>
    </aside>

    <div class="container">
        <h1>Historial de Mantenimientos</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vehículo / Producto</th>
                    <th>Servicio Realizado</th>
                    <th>Fecha</th>
                    <th>Costo</th>
                    <th>Acciones Rápidas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $fila): ?>
                <tr>
                    <td><?php echo htmlspecialchars($fila['id']); ?></td>
                    <td><?php echo htmlspecialchars($fila['vehiculo']); ?></td>
                    <td><?php echo htmlspecialchars($fila['servicio']); ?></td>
                    <td><?php echo htmlspecialchars($fila['fecha']); ?></td>
                    <td>$<?php echo number_format($fila['costo'], 0, ',', '.'); ?></td>
                    <td>
                        <button class="btn-edit" onclick="prepararEdicion(<?php echo $fila['id']; ?>)">✏️ Editar</button>
                        <button class="btn-delete" onclick="prepararEliminacion(<?php echo $fila['id']; ?>)">🗑️ Eliminar</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($registros)): ?>
                <tr><td colspan="6" style="text-align: center; color: #a3a3a3; padding: 20px;">No hay registros disponibles.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="agregarModal">
        <div class="modal-box">
            <button class="close-modal" onclick="cerrarModal('agregarModal')">×</button>
            <h2 style="color: #00E59B;">➕ Registrar Nuevo Servicio</h2>
            <form method="POST" action="servicio.php">
                <input type="hidden" name="crud_action" value="agregar">
                
                <div class="form-group">
                    <label>Vehículo / Producto</label>
                    <input type="text" name="vehiculo" placeholder="Ej. JAC S2 2015" required>
                </div>
                <div class="form-group">
                    <label>Servicio Realizado</label>
                    <input type="text" name="servicio" placeholder="Ej. Cambio de aceite" required>
                </div>
                <div class="form-group">
                    <label>Fecha</label>
                    <input type="date" name="fecha" max="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Costo ($)</label>
                    <input type="number" step="0.01" name="costo" required>
                </div>
                <button type="submit" class="btn-submit">Guardar Registro</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="editarModal">
        <div class="modal-box">
            <button class="close-modal" onclick="cerrarModal('editarModal')">×</button>
            <h2 style="color: #3b82f6;">✏️ Editar Servicio</h2>
            <form method="POST" action="servicio.php">
                <input type="hidden" name="crud_action" value="editar">
                
                <div class="form-group">
                    <label>Seleccionar Registro a Editar</label>
                    <select name="id" id="select_editar" onchange="cargarDatosFormulario(this.value)" required>
                        <option value="">-- Seleccione un ID --</option>
                        <?php foreach ($registros as $r): ?>
                            <option value="<?php echo $r['id']; ?>">ID: <?php echo $r['id']; ?> - <?php echo htmlspecialchars($r['vehiculo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="campos_edicion" style="display: none;">
                    <div class="form-group"><label>Vehículo</label><input type="text" name="vehiculo" id="edit_vehiculo" required></div>
                    <div class="form-group"><label>Servicio</label><input type="text" name="servicio" id="edit_servicio" required></div>
                    <div class="form-group"><label>Fecha</label><input type="date" name="fecha" id="edit_fecha" max="<?php echo date('Y-m-d'); ?>" required></div>
                    <div class="form-group"><label>Costo</label><input type="number" step="0.01" name="costo" id="edit_costo" required></div>
                    <button type="submit" class="btn-submit" style="background: #3b82f6; color: white;">💾 Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="eliminarModal">
        <div class="modal-box" style="text-align: center;">
            <button class="close-modal" onclick="cerrarModal('eliminarModal')">×</button>
            <h2 style="color: #ef4444; border-bottom: none;">🗑️ Eliminar Registro</h2>
            <form method="POST" action="servicio.php">
                <input type="hidden" name="crud_action" value="eliminar">
                
                <div class="form-group" style="text-align: left;">
                    <label>Seleccionar Registro a Eliminar</label>
                    <select name="id" id="select_eliminar" required>
                        <option value="">-- Seleccione un ID --</option>
                        <?php foreach ($registros as $r): ?>
                            <option value="<?php echo $r['id']; ?>">ID: <?php echo $r['id']; ?> - <?php echo htmlspecialchars($r['vehiculo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p style="color: #a3a3a3; font-size: 0.9rem; margin-top: 20px;">Esta acción borrará el registro permanentemente.</p>
                <button type="submit" class="btn-submit" style="background: #ef4444; color: white;">Confirmar Eliminación</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="cuentaModal">
        <div class="modal-box">
            <button class="close-modal" onclick="cerrarModal('cuentaModal')">×</button>
            <h2>Configuración de Cuenta</h2>
            <form method="POST" action="servicio.php">
                <input type="hidden" name="action" value="actualizar_perfil">
                <div class="form-group"><label>RUT</label><input type="text" value="<?php echo htmlspecialchars($usuarioActual['rut'] ?? ''); ?>" disabled style="opacity: 0.5;"></div>
                <div class="form-group"><label>Nombre(s)</label><input type="text" name="nombre" value="<?php echo htmlspecialchars($usuarioActual['nombre'] ?? ''); ?>" required></div>
                <div class="form-group"><label>Apellidos</label><input type="text" name="apellido" value="<?php echo htmlspecialchars($usuarioActual['apellido'] ?? ''); ?>" required></div>
                <div class="form-group"><label>Fecha de Nacimiento</label><input type="date" name="fecha_nacimiento" value="<?php echo htmlspecialchars($usuarioActual['fecha_nacimiento'] ?? ''); ?>" max="<?php echo date('Y-m-d'); ?>" required></div>
                <div class="form-group"><label>Dirección</label><input type="text" name="direccion" value="<?php echo htmlspecialchars($usuarioActual['direccion'] ?? ''); ?>" required></div>
                <button type="submit" class="btn-submit">💾 Guardar Cambios</button>
            </form>
            <a href="logout.php" style="display: block; text-align: center; color: #ef4444; border: 1px solid #ef4444; padding: 10px; border-radius: 4px; margin-top: 15px; text-decoration: none; font-weight: bold;">Cerrar Sesión</a>
        </div>
    </div>

    <?php if ($faltaPerfil): ?>
    <div class="modal-overlay active" style="z-index: 9999;">
        <div class="modal-box">
            <h2 style="color: #00E59B;">¡Bienvenido/a!</h2>
            <p style="color: #a3a3a3; font-size: 0.9rem; margin-bottom: 20px;">Necesitamos algunos datos básicos para tu cuenta.</p>
            <form method="POST" action="servicio.php">
                <input type="hidden" name="action" value="completar_perfil">
                <div class="form-group"><label>Nombre(s)</label><input type="text" name="nombre" required></div>
                <div class="form-group"><label>Apellidos</label><input type="text" name="apellido" required></div>
                <div class="form-group"><label>Fecha de Nacimiento</label><input type="date" name="fecha_nacimiento" max="<?php echo date('Y-m-d'); ?>" required></div>
                <div class="form-group"><label>Dirección</label><input type="text" name="direccion" required></div>
                <button type="submit" class="btn-submit">Guardar e Ingresar</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Exportar los registros de PHP a un Array de JavaScript de forma segura
        const registrosDB = <?php echo json_encode($registros, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

        function toggleMenu() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function abrirModal(idModal) {
            document.getElementById(idModal).classList.add('active');
        }

        function cerrarModal(idModal) {
            document.getElementById(idModal).classList.remove('active');
        }

        // Lógica para cuando se cambia el Select en el Modal de Edición
        function cargarDatosFormulario(id_seleccionado) {
            const container = document.getElementById('campos_edicion');
            if (!id_seleccionado) {
                container.style.display = 'none';
                return;
            }
            
            // Buscar el registro en el array
            const registro = registrosDB.find(r => r.id == id_seleccionado);
            if (registro) {
                document.getElementById('edit_vehiculo').value = registro.vehiculo;
                document.getElementById('edit_servicio').value = registro.servicio;
                document.getElementById('edit_fecha').value = registro.fecha;
                document.getElementById('edit_costo').value = registro.costo;
                container.style.display = 'block';
            }
        }

        // Si se presiona el botón "Editar" de la tabla
        function prepararEdicion(id) {
            abrirModal('editarModal');
            const select = document.getElementById('select_editar');
            select.value = id;
            cargarDatosFormulario(id); // Disparar la carga de datos visuales
        }

        // Si se presiona el botón "Eliminar" de la tabla
        function prepararEliminacion(id) {
            abrirModal('eliminarModal');
            document.getElementById('select_eliminar').value = id;
        }
    </script>
</body>
</html>