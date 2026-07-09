<?php
// Requerir el autoloader de Composer
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendTemplatedEmail($to, $subject, $templateData) {
    $mail = new PHPMailer(true);

    try {
        // --- Configuración del Servidor (Tus variables) ---
        $mail->isSMTP();
        $mail->Host       = 'mail.janyistudios.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreplay@vision.janyistudios.com';
        $mail->Password   = 'PzI!x3#3eK!xvlG-';
        // Usamos STARTTLS porque el puerto es 587
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587;
        
        // Deshabilitar la verificación de certificados (Igual que rejectUnauthorized: false en tu JS)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // --- Remitente y Destinatario ---
        $mail->setFrom('noreplay@vision.janyistudios.com', 'Automotriz');
        $mail->addAddress($to);

        // --- Contenido ---
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Tu plantilla HTML exacta
        $mail->Body = "
            <h1>{$templateData['title']}</h1>
            <p>{$templateData['body']}</p>
            <br>
            <a href='{$templateData['button_link']}' style='background-color: #00E59B; color: #000; padding: 10px 15px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>
                {$templateData['button_text']}
            </a>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Si falla, se guarda en el log de errores de PHP
        error_log("El mensaje no pudo ser enviado. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>