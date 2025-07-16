<?php
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener datos del formulario
    $nombre = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);
    $correo = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $idr = (int)$_POST['idr'];

    try {
        // Verificar si el correo ya existe
        $stmt = $conn->prepare("SELECT idu FROM usuarios WHERE correo = :email");
        $stmt->bindParam(':email', $correo);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            header("Location: ../Frontend/registro.html?error=email_existente");
            exit();
        }

        // Insertar nuevo usuario
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, apellidos, correo, contraseña, idr) 
            VALUES (:nombre, :apellidos, :correo, :password, :idr)");
        
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellidos', $apellidos);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':idr', $idr, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            header("Location: ../Frontend/iniciosession.html?registro=exitoso");
            exit();
        } else {
            header("Location: ../Frontend/registro.html?error=registro_fallido");
            exit();
        }
    } catch(PDOException $e) {
        error_log("Error en el registro: " . $e->getMessage());
        header("Location: ../Frontend/registro.html?error=error_bd");
        exit();
    }
} else {
    header("Location: ../Frontend/registro.html");
    exit();
}
?>