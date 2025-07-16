<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['correo_recuperacion']) || !isset($_SESSION['codigo_verificado'])) {
    header("Location: ../Frontend/recuperar.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $correo = $_SESSION['correo_recuperacion'];

    if ($password !== $confirm_password) {
        header("Location: ../Frontend/cambiar_password.html?error=1");
        exit();
    }

    try {
        // Actualizar contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET contraseña = :password WHERE correo = :correo");
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        // Limpiar sesión
        unset($_SESSION['correo_recuperacion']);
        unset($_SESSION['codigo_verificado']);

        header("Location: ../Frontend/iniciosession.html?success=1");
        exit();
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>