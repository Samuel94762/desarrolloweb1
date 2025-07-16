<?php
require_once 'database.php';

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

        // Guardar en la base de datos
        $stmt = $conn->prepare("INSERT INTO recuperacion (correo, codigo, expiracion) VALUES (:correo, :codigo, :expiracion)");
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':expiracion', $expiracion);
        $stmt->execute();

        // Enviar correo (simulado)
        // En un entorno real, usarías PHPMailer o similar
        $asunto = "Código de recuperación de contraseña";
        $mensaje = "Tu código de verificación es: $codigo\n\nEste código expirará en 30 minutos.";
        $headers = "From: no-reply@tusitio.com";
        
        // mail($correo, $asunto, $mensaje, $headers);
        
        // Guardar el correo en sesión para el siguiente paso
        session_start();
        $_SESSION['correo_recuperacion'] = $correo;
        
        header("Location: ../Frontend/verificar_codigo.html");
        exit();
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>