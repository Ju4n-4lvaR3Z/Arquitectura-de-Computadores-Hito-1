<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function validarRUT($rut) {
    // Eliminar puntos y guion, dejar solo números y la letra K
    $rut = preg_replace('/[^kK0-9]/i', '', $rut);
    
    // Un RUT válido debe tener al menos 8 caracteres (ej: 1.000.000-0)
    if (strlen($rut) < 8) return false;
    
    // Separar el dígito verificador del resto del número
    $dv = substr($rut, -1);
    $numero = substr($rut, 0, strlen($rut) - 1);
    
    // Algoritmo Módulo 11
    $i = 2;
    $suma = 0;
    foreach (array_reverse(str_split($numero)) as $v) {
        if ($i == 8) $i = 2;
        $suma += $v * $i;
        ++$i;
    }
    
    $dvr = 11 - ($suma % 11);
    if ($dvr == 11) $dvr = 0;
    if ($dvr == 10) $dvr = 'K';
    
    // Comparar el DV calculado con el ingresado
    return strtoupper($dv) == strtoupper($dvr);
}
function conectarBD() {
    // Usamos una variable estática para guardar la conexión y no abrirla 2 veces
    static $db = null; 
    
    // Si la conexión no existe, la creamos
    if ($db === null) {
        $db = new PDO('sqlite:mantenimiento.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Timeout de 5 segundos (evita que tire error inmediatamente si está ocupada)
        $db->setAttribute(PDO::ATTR_TIMEOUT, 5);
        
        // Tabla original
        $db->exec("CREATE TABLE IF NOT EXISTS registros (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vehiculo TEXT NOT NULL,
            servicio TEXT NOT NULL,
            fecha DATE NOT NULL,
            costo REAL NOT NULL
        )");

        // Nueva tabla de Usuarios (con RUT y Perfil)
        $db->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            rut TEXT UNIQUE NOT NULL,
            correo TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            verificado INTEGER DEFAULT 0,
            token TEXT,
            nombre TEXT,
            apellido TEXT,
            fecha_nacimiento DATE,
            direccion TEXT
        )");

        // Nueva tabla de Logs
        $db->exec("CREATE TABLE IF NOT EXISTS logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario TEXT NOT NULL,
            accion TEXT NOT NULL,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }
    try {
        $db->exec("ALTER TABLE usuarios ADD COLUMN rol TEXT DEFAULT 'usuario'");
    } catch (Exception $e) {}
    return $db;
}
// --- SISTEMA DE LOGS ---
function registrarLog($accion, $usuario_forzado = null) {
    $db = conectarBD();
    // Si pasamos un usuario explícito, lo usa. Si no, busca en la sesión. Si no hay nada, pone 'Sistema'
    $usuario = $usuario_forzado ? $usuario_forzado : (isset($_SESSION['rut']) ? $_SESSION['rut'] : 'Sistema');
    
    $stmt = $db->prepare("INSERT INTO logs (usuario, accion) VALUES (?, ?)");
    $stmt->execute([$usuario, $accion]);
}

function obtenerLogs() {
    $db = conectarBD();
    $stmt = $db->query("SELECT * FROM logs ORDER BY fecha DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- OPERACIONES CRUD REGISTROS ---

function crearRegistro($vehiculo, $servicio, $fecha, $costo) {
    $db = conectarBD();
    $stmt = $db->prepare("INSERT INTO registros (vehiculo, servicio, fecha, costo) VALUES (?, ?, ?, ?)");
    $exito = $stmt->execute([$vehiculo, $servicio, $fecha, $costo]);
    if ($exito) registrarLog("Creó un servicio para el vehículo: $vehiculo ($servicio)");
    return $exito;
}

function obtenerRegistros() {
    $db = conectarBD();
    $stmt = $db->query("SELECT * FROM registros ORDER BY fecha DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerRegistroPorId($id) {
    $db = conectarBD();
    $stmt = $db->prepare("SELECT * FROM registros WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function actualizarRegistro($id, $vehiculo, $servicio, $fecha, $costo) {
    $db = conectarBD();
    $stmt = $db->prepare("UPDATE registros SET vehiculo = ?, servicio = ?, fecha = ?, costo = ? WHERE id = ?");
    $exito = $stmt->execute([$vehiculo, $servicio, $fecha, $costo, $id]);
    if ($exito) registrarLog("Actualizó el registro #$id del vehículo: $vehiculo");
    return $exito;
}

function eliminarRegistro($id) {
    $db = conectarBD();
    $registro = obtenerRegistroPorId($id);
    $stmt = $db->prepare("DELETE FROM registros WHERE id = ?");
    $exito = $stmt->execute([$id]);
    if ($exito && $registro) registrarLog("Eliminó el registro #$id (Vehículo: " . $registro['vehiculo'] . ")");
    return $exito;
}
function obtenerLogsConUsuarios() {
    $db = conectarBD();
    // Hacemos un JOIN para traer los datos del usuario junto con su log
    $stmt = $db->query("SELECT l.*, u.nombre, u.apellido, u.correo, u.fecha_nacimiento, u.direccion 
                        FROM logs l 
                        LEFT JOIN usuarios u ON l.usuario = u.rut 
                        ORDER BY l.fecha DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>