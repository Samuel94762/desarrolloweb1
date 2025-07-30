<?php
require_once 'database.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Buscar token válido
        $stmt = $conn->prepare("SELECT u.idu, u.correo 
                               FROM verificacion_email ve 
                               JOIN usuarios u ON ve.usuario_id = u.idu 
                               WHERE ve.token = :token 
                               AND ve.usado = FALSE 
                               AND ve.expiracion > NOW()");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Marcar usuario como verificado
            $stmt = $conn->prepare("UPDATE usuarios SET verificado = TRUE WHERE idu = :id");
            $stmt->bindParam(':id', $usuario['idu']);
            $stmt->execute();
            
            // Marcar token como usado
            $stmt = $conn->prepare("UPDATE verificacion_email SET usado = TRUE WHERE token = :token");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            // Redirigir con mensaje de éxito
            header("Location: ../Frontend/iniciosession.html?verificado=1");
        } else {
            // Token inválido o expirado
            header("Location: ../Frontend/iniciosession.html?error=token_invalido");
        }
    } catch(PDOException $e) {
        error_log("Error en verificación: " . $e->getMessage());
        header("Location: ../Frontend/iniciosession.html?error=bd");
    }
} else {
    header("Location: ../Frontend/iniciosession.html");
}
exit();
?>