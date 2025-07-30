<?php
session_start();
require_once 'database.php';


if (isset($_GET['token'])) {
    $token = $_GET['token'];
    echo $token;
    sleep(5);
    try {
        
        $stmt = $conn->prepare("SELECT correo FROM recuperacion 
                              WHERE token = :token 
                              AND usado = FALSE 
                              AND expiracion > NOW()");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        //CAMBIO HECHO POR SAMUEL PARA PROBAR GIT 
        if ($stmt->rowCount() == 1) {
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['correo_recuperacion'] = $resultado['correo'];
            $_SESSION['codigo_verificado'] = true;
            
            // Marcar token como usado
            $stmt = $conn->prepare("UPDATE recuperacion SET usado = TRUE WHERE token = :token");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            // Redirigir al formulario de cambio de contraseña
            header("Location: ../Frontend/cambiar_password.html");
            exit();
        } else {
            header("Location: ../Frontend/recuperar.html?error=token_invalido");
            exit();
        }
    } catch(PDOException $e) {
        error_log("Error al validar token: " . $e->getMessage());
        header("Location: ../Frontend/recuperar.html?error=bd");
        exit();
    }
} elseif (!isset($_SESSION['codigo_verificado'])) {
    header("Location: ../Frontend/recuperar.html");
    exit();
}

if (!isset($_SESSION['correo_recuperacion'])) {
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
        $stmt = $conn->prepare("UPDATE usuarios SET passwd = :password WHERE correo = :correo");
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