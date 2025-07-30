<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'database.php';
require '../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar campos requeridos
    $required = ['nombre', 'apellidos', 'email', 'password', 'idr', 'idp'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            header("Location: ../Frontend/registro.php?error=missing_$field");
            exit();
        }
    }

    // Asignar variables
    $nombre = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);
    $correo = trim($_POST['email']);
    $passwd = trim($_POST['password']);
    $idr = (int)$_POST['idr'];
    $idp = (int)$_POST['idp'];

    // Validaciones
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../Frontend/registro.php?error=invalid_email");
        exit();
    }

    if (strlen($passwd) < 8) {
        header("Location: ../Frontend/registro.php?error=short_password");
        exit();
    }

    try {
        // Verificar si el correo ya existe
        $stmt = $conn->prepare("SELECT idu FROM usuarios WHERE correo = :correo");
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            header("Location: ../Frontend/registro.php?error=email_exists");
            exit();
        }

        // Verificar que el plan existe
        $stmt = $conn->prepare("SELECT idp FROM planes WHERE idp = :idp");
        $stmt->bindParam(':idp', $idp);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            header("Location: ../Frontend/registro.php?error=invalid_plan");
            exit();
        }

        // Crear usuario
        $hashed_password = password_hash($passwd, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, apellidos, correo, passwd, idr, idp, verificado) 
                              VALUES (:nombre, :apellidos, :correo, :passwd, :idr, :idp, FALSE)");
        
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellidos', $apellidos);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':passwd', $hashed_password);
        $stmt->bindParam(':idr', $idr);
        $stmt->bindParam(':idp', $idp);

        if ($stmt->execute()) {
            $usuario_id = $conn->lastInsertId();

            // Generar token de verificación
            $token = bin2hex(random_bytes(32));
            $expiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $stmt = $conn->prepare("INSERT INTO verificacion_email (usuario_id, token, expiracion) 
                                  VALUES (:usuario_id, :token, :expiracion)");
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':expiracion', $expiracion);
            $stmt->execute();

            // Enviar correo de verificación
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
                
                $verificationLink = $_ENV['APP_URL']."/Backend/verificar_email.php?token=$token";
                
                $mail->isHTML(true);
                $mail->Subject = 'Verifica tu cuenta';
                $mail->Body = "
                    <h1>¡Gracias por registrarte!</h1>
                    <p>Has seleccionado el plan: ".($idp == 1 ? 'Básico' : 'Premium')."</p>
                    <p>Por favor verifica tu correo electrónico haciendo clic en el siguiente enlace:</p>
                    <a href='$verificationLink'>Verificar mi cuenta</a>
                    <p>Este enlace expirará en 24 horas.</p>
                ";
                
                $mail->send();
                header("Location: ../Frontend/iniciosession.html?registro=exitoso");
                exit();
            } catch (Exception $e) {
                error_log("Error al enviar correo: ".$mail->ErrorInfo);
                header("Location: ../Frontend/registro.php?error=email_send_failed");
                exit();
            }
        } else {
            header("Location: ../Frontend/registro.php?error=db_error");
            exit();
        }
    } catch(PDOException $e) {
        error_log("Error en registro: ".$e->getMessage());
        header("Location: ../Frontend/registro.php?error=db_exception");
        exit();
    }
} else {
    header("Location: ../Frontend/registro.php");
    exit();
}