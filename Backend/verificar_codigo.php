<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['correo_recuperacion'])) {
    header("Location: ../Frontend/recuperar.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = $_POST['codigo'];
    $correo = $_SESSION['correo_recuperacion'];

    try {
        // Verificar el código
        $stmt = $conn->prepare("SELECT * FROM recuperacion WHERE correo = :correo AND codigo = :codigo AND usado = FALSE AND expiracion > NOW()");
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':codigo', $codigo);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            // Marcar el código como usado
            $stmt = $conn->prepare("UPDATE recuperacion SET usado = TRUE WHERE correo = :correo AND codigo = :codigo");
            $stmt->bindParam(':correo', $correo);
            $stmt->bindParam(':codigo', $codigo);
            $stmt->execute();

            $_SESSION['codigo_verificado'] = true;
            header("Location: ../Frontend/cambiar_password.html");
            exit();
        } else {
            header("Location: ../Frontend/verificar_codigo.html?error=1");
            exit();
        }
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>