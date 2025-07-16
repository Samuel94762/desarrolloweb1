<?php
session_start();
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = trim($_POST['correo']);
    $contraseña = trim($_POST['contraseña']);

    try {
        $stmt = $conn->prepare("SELECT u.*, r.nombre as rol 
                               FROM usuarios u 
                               JOIN roles r ON u.idr = r.idr 
                               WHERE u.correo = :correo");
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($contraseña, $usuario['contraseña'])) {
                $_SESSION['idusuario'] = $usuario['idu'];
                $_SESSION['usuario'] = $usuario['nombre'];
                $_SESSION['correo'] = $usuario['correo'];
                $_SESSION['idr'] = $usuario['idr'];
                $_SESSION['rol'] = $usuario['rol'];
                
                // Redirección según rol
                if ($usuario['idr'] == 1) {
                    header("Location: homeADM.php");
                } else {
                    header("Location: homeINVT.php");
                }
                exit();
            }
        }
        
        // Si llega aquí es porque falló la autenticación
        header("Location: ../Frontend/iniciosession.html?error=credenciales_invalidas");
        exit();
        
    } catch(PDOException $e) {
        error_log("Error en autenticación: " . $e->getMessage());
        header("Location: ../Frontend/iniciosession.html?error=error_bd");
        exit();
    }
} else {
    header("Location: ../Frontend/iniciosession.html");
    exit();
}
?>