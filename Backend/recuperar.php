<?php
require_once __DIR__ . '/database.php';
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $_POST['correo'];
    
    try {
        // Verificar si el correo existe
        $stmt = $conn->prepare("SELECT idu FROM usuarios WHERE correo = :correo");
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            header("Location: ../Frontend/recuperar.html?error=1");
            exit();
        }

        // Generar código de 6 dígitos
        $codigo = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiracion = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        
        // Generar token para enlace
        $token = bin2hex(random_bytes(32));
        $token_expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Guardar en la base de datos
        $stmt = $conn->prepare("INSERT INTO recuperacion (correo, codigo, expiracion, token) VALUES (:correo, :codigo, :expiracion, :token)");
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':expiracion', $expiracion);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        // Configurar PHPMailer
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USER']; 
            $mail->Password = $_ENV['SMTP_PASSWORD']; 
            $mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION']; 
            $mail->Port = $_ENV['SMTP_PORT']; 

            $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
            $mail->addAddress($correo);

            // Crear enlace de recuperación
            $resetLink = $_ENV['APP_URL'] . "/Backend/cambiar_password.php?token=$token";
            
            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de contraseña';
            $mail->Body = "
                <h1>Recuperación de contraseña</h1>
                <p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
                <p>Tu código de verificación es: <strong>$codigo</strong></p>
                <p>O puedes hacer clic en el siguiente enlace:</p>
                <a href='$resetLink'>Restablecer contraseña</a>
                <p>Este enlace expirará en 1 hora.</p>
                <p>Si no solicitaste este cambio, ignora este mensaje.</p>
            ";
            
            $mail->send();
            
            // Guardar el correo en sesión para el siguiente paso
            session_start();
            $_SESSION['correo_recuperacion'] = $correo;
            
            header("Location: ../Frontend/verificar_codigo.html");
            exit();
            
        } catch (Exception $e) {
            error_log("Error al enviar el correo: {$mail->ErrorInfo}");
            header("Location: ../Frontend/recuperar.html?error=envio_correo");
            exit();
        }
        
    } catch(PDOException $e) {
        error_log("Error en recuperación: " . $e->getMessage());
        header("Location: ../Frontend/recuperar.html?error=bd");
        exit();
    }
}
?>