<?php
require_once 'crud.php';

$error = '';
$success = '';

// --- Lógica de Verificación de Correo (GET) ---
if (isset($_GET['verify'])) {
    $token = $_GET['verify'];
    $db = conectarBD();
    $stmt = $db->prepare("SELECT id, correo FROM usuarios WHERE token = ? AND verificado = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $stmt = $db->prepare("UPDATE usuarios SET verificado = 1, token = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        registrarLog("Cuenta verificada exitosamente", $user['correo']);
        $success = "¡Cuenta verificada exitosamente! Ya puedes iniciar sesión.";
    } else {
        $error = "Enlace de verificación inválido o la cuenta ya está verificada.";
    }
}

// --- Lógica de Login / Registro (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = conectarBD();
    $rut = trim($_POST['rut']);
    $password = $_POST['password'];
    $action = $_POST['action'];

    // --- NUEVA VALIDACIÓN DE RUT ---
    if (!validarRUT($rut)) {
        $error = "El RUT ingresado no es válido. Verifica que el dígito verificador sea correcto.";
    }
    if ($action === 'register') {
        $correo = trim($_POST['correo']);
        $confirmPassword = $_POST['confirm_password'];
        
        if ($password !== $confirmPassword) {
            $error = "Las contraseñas no coinciden.";
        } else {
            // Verificar si RUT o Correo ya existen
            $stmt = $db->prepare("SELECT id, verificado FROM usuarios WHERE rut = ? OR correo = ?");
            $stmt->execute([$rut, $correo]);
            $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

            $debeEnviarCorreo = false;
            $token = bin2hex(random_bytes(32));
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            if ($usuarioExistente) {
                if ($usuarioExistente['verificado'] == 1) {
                    $error = "El RUT o Correo ya está registrado y verificado. Por favor, inicia sesión.";
                } else {
                    $stmt = $db->prepare("UPDATE usuarios SET password = ?, token = ?, correo = ? WHERE id = ?");
                    if ($stmt->execute([$hashed_password, $token, $correo, $usuarioExistente['id']])) {
                        $debeEnviarCorreo = true;
                        $success = "El usuario ya estaba registrado pero no verificado. Hemos reenviado el enlace.";
                    }
                }
            } else {
                $stmt = $db->prepare("INSERT INTO usuarios (rut, correo, password, token) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$rut, $correo, $hashed_password, $token])) {
                    $debeEnviarCorreo = true;
                    $success = "¡Registro exitoso! Revisa tu correo ($correo) para activar tu cuenta.";
                }
            }

            if ($debeEnviarCorreo) {
                require_once 'mailer.php';
                $protocol = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? "https" : "http";
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') $protocol = "https";
                $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST'];
                $verifyLink = $protocol . "://" . $host . "/login.php?verify=" . $token;
                
                $templateData = [
                    'title' => 'Bienvenido a Nuestra Automotriz',
                    'body' => 'Por favor, haz clic en el siguiente botón para verificar tu cuenta en nuestro sistema automotriz.',
                    'button_link' => $verifyLink,
                    'button_text' => 'Verificar mi cuenta'
                ];
                sendTemplatedEmail($correo, "Verifica tu cuenta en Nuestra Automotriz", $templateData);
                registrarLog("Se solicitó verificación de cuenta para RUT: $rut");
            }
        }
    } elseif ($action === 'login') {
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE rut = ?");
        $stmt->execute([$rut]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

       if ($user && password_verify($password, $user['password'])) {
            if ($user['verificado'] == 0) {
                $error = "Debes verificar tu correo antes de iniciar sesión.";
            } else {
                
                // --- CAPTURAR IP REAL DEL USUARIO ---
                $ip = 'Desconocida';
                if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
                } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    // Tomamos la primera IP si hay múltiples proxies
                    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
                } else {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
                }

                // Guardar la nueva IP en el perfil del usuario
                $stmtIp = $db->prepare("UPDATE usuarios SET ip = ? WHERE id = ?");
                $stmtIp->execute([$ip, $user['id']]);

                // Iniciar la sesión normalmente
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['rut'] = $user['rut']; 
                $_SESSION['rol'] = isset($user['rol']) ? $user['rol'] : 'usuario'; 
                
                registrarLog("Inició sesión en el sistema", $user['rut']);
                
                header("Location: servicio.php");
                exit; 
            }
        } else {
            $error = "RUT o contraseña incorrectos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Automotriz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .animate-zoomIn { animation: zoomIn 0.2s ease-out forwards; }
    </style>
</head>
<body class="bg-[#0a0a0a] h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-3xl bg-[#171717] rounded-none md:rounded-lg shadow-2xl overflow-hidden flex flex-col md:flex-row h-auto max-h-[90vh] animate-zoomIn relative">
        
        <div class="hidden md:flex md:w-5/12 relative flex-col justify-between p-10 bg-gradient-to-br from-purple-900 to-black text-white overflow-hidden">
            <div class="absolute inset-0 bg-black/40"></div>
            <div class="relative z-10 text-xs font-bold uppercase tracking-wider text-gray-400">
                Bienvenido a <span class="text-white">Automotriz WEB</span>
                
            <img src="img/car.svg" alt="Logo" style="width: 150px;">
            </div>
            <div class="relative z-10 mt-auto">
                <h2 class="text-3xl font-bold mb-2">Automotriz Web</h2>
                <p class="text-sm text-gray-300">Gestiona el historial técnico y mecánico de tu parque automotriz de forma profesional.</p>
            </div>
        </div>

        <div class="md:w-7/12 bg-[#171717] relative flex flex-col p-8 md:p-10 overflow-y-auto">
            
            <h1 id="form-title" class="text-2xl font-serif font-bold text-white mb-2 max-sm:mb-0 mt-4 max-sm:mt-1">Ingreso</h1>

            <div class="min-h-[48px] mb-1 flex items-center">
                <?php if ($error): ?>
                    <div class="w-full p-2 text-red-500 text-sm rounded border border-red-500/50 bg-red-500/10"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="w-full p-2 text-[#00E59B] text-sm rounded border border-[#00E59B]/50 bg-[#00E59B]/10"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
            </div>

            <form method="POST" action="login.php" class="flex-grow space-y-4" id="auth-form">
                <input type="hidden" name="action" id="action-input" value="login">
                
                <div>
                    <label class="block text-sm font-bold text-white mb-1">RUT</label>
                    <input type="text" name="rut" id="rut_input" placeholder="12.345.678-9" required maxlength="12" oninput="formatearRUT(this)"
                        class="w-full p-2 bg-neutral-800 border border-neutral-700 rounded text-white focus:border-[#00E59B] focus:ring-1 focus:ring-[#00E59B] outline-none transition-colors" />
                </div>
                <div class="relative">
                    <label class="block text-sm font-bold text-white mb-1">Contraseña</label>
                    <input type="password" name="password" id="password" placeholder="*********" required
                           class="w-full p-2 bg-neutral-800 border border-neutral-700 rounded text-white focus:border-[#00E59B] focus:ring-1 focus:ring-[#00E59B] outline-none transition-colors" />
                    <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 top-6 flex items-center px-3 text-gray-400 hover:text-white">
                        👁️
                    </button>
                </div>

                <div id="register-fields" class="hidden space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-white mb-1">Correo Electrónico</label>
                        <input type="email" name="correo" id="correo_input" placeholder="correo@jakrarish.net"
                               class="w-full p-2 bg-neutral-800 border border-neutral-700 rounded text-white focus:border-[#00E59B] focus:ring-1 focus:ring-[#00E59B] outline-none transition-colors" />
                    </div>
                    <ul class="grid grid-cols-2 gap-x-4 gap-y-1 mt-2" id="password-reqs">
                        <li class="flex items-center text-xs text-neutral-500" id="req-length">✗ 8-32 caracteres</li>
                        <li class="flex items-center text-xs text-neutral-500" id="req-upper">✗ Una mayúscula</li>
                        <li class="flex items-center text-xs text-neutral-500" id="req-number">✗ Un número</li>
                        <li class="flex items-center text-xs text-neutral-500" id="req-special">✗ Un especial</li>
                    </ul>

                    <div class="relative">
                        <label class="block text-sm font-bold text-white mb-1">Confirmar Contraseña</label>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="*********"
                               class="w-full p-2 bg-neutral-800 border border-neutral-700 rounded text-white focus:border-[#00E59B] focus:ring-1 focus:ring-[#00E59B] outline-none transition-colors" />
                    </div>
                </div>

                <div id="login-options" class="flex items-center justify-between text-sm">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" class="w-4 h-4 text-[#00E59B] bg-neutral-700 border-neutral-600 rounded focus:ring-[#00E59B]" checked />
                        <span class="text-white">Recordar usuario</span>
                    </label>
                    <button type="button" class="text-white underline hover:text-zinc-300">¿Olvidaste la contraseña?</button>
                </div>

                <button type="submit" id="submit-btn" class="w-full py-3 px-4 bg-[#00E59B] hover:bg-[#00c483] text-gray-900 font-bold text-lg rounded transition duration-200 mt-2">
                    Ingresar
                </button>
            </form>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-neutral-700"></div></div>
                <div class="relative flex justify-center text-sm"><span class="px-2 bg-[#171717] text-neutral-400">O</span></div>
            </div>

            <div class="mt-8 text-center text-sm">
                <span class="text-white" id="toggle-text">¿No tienes cuenta? </span>
                <button type="button" onclick="toggleView()" id="toggle-btn" class="font-bold text-[#00c483] hover:underline">
                    Regístrate
                </button>
            </div>
        </div>
    </div>

    <script>
        let isLoginView = true;

        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        function toggleView() {
            isLoginView = !isLoginView;
            document.getElementById('form-title').innerText = isLoginView ? 'Ingreso' : 'Registro';
            document.getElementById('submit-btn').innerText = isLoginView ? 'Ingresar' : 'Registrarse';
            document.getElementById('action-input').value = isLoginView ? 'login' : 'register';
            document.getElementById('toggle-text').innerText = isLoginView ? '¿No tienes cuenta? ' : '¿Ya tienes una cuenta? ';
            document.getElementById('toggle-btn').innerText = isLoginView ? 'Regístrate' : 'Inicia sesión';
            
            document.getElementById('register-fields').classList.toggle('hidden', isLoginView);
            document.getElementById('login-options').classList.toggle('hidden', !isLoginView);
            
            document.getElementById('confirm_password').required = !isLoginView;
            document.getElementById('correo_input').required = !isLoginView;
        }

        // Validación visual de contraseña en vivo
        document.getElementById('password').addEventListener('input', function(e) {
            if(isLoginView) return;
            const val = e.target.value;
            const reqs = {
                length: val.length >= 8 && val.length <= 32,
                upper: /[A-Z]/.test(val),
                number: /\d/.test(val),
                special: /[!@#$%^&*()_+\-\=\[\]{};':"\\|,.<>\/?]/.test(val)
            };

            const updateReq = (id, met, text) => {
                const el = document.getElementById(id);
                el.className = `flex items-center text-xs ${met ? 'text-green-400' : 'text-neutral-500'}`;
                el.innerText = (met ? '✓ ' : '✗ ') + text;
            };

            updateReq('req-length', reqs.length, '8-32 caracteres');
            updateReq('req-upper', reqs.upper, 'Una mayúscula');
            updateReq('req-number', reqs.number, 'Un número');
            updateReq('req-special', reqs.special, 'Un especial');
        });
        function formatearRUT(input) {
            // Eliminar todo lo que no sea número o la letra K
            let valor = input.value.replace(/[^0-9kK]+/g, '').toUpperCase();
            
            // Si está vacío, salir
            if (valor.length === 0) {
                input.value = '';
                return;
            }
            
            // Separar el DV del cuerpo
            let cuerpo = valor.slice(0, -1);
            let dv = valor.slice(-1);
            
            if (valor.length === 1) {
                input.value = valor;
                return;
            }
            
            // Formatear el cuerpo con puntos
            let cuerpoFormateado = cuerpo.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            
            // Unir cuerpo y DV con guion
            input.value = cuerpoFormateado + '-' + dv;
        }
    </script>
</body>
</html>