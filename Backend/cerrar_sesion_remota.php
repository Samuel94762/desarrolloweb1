<?php
session_start();
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ids'])) {
    $ids = (int)$_POST['ids'];
    
    try {
        // Verificar que la sesión pertenece al usuario
        if (isset($_SESSION['login_attempt'])) {
            $idu = $_SESSION['login_attempt']['idu'];
            
            $stmt = $conn->prepare("DELETE FROM sesiones_activas WHERE ids = :ids AND idu = :idu");
            $stmt->bindParam(':ids', $ids);
            $stmt->bindParam(':idu', $idu);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Redirigir de nuevo a auth.php para intentar el login
                header("Location: auth.php");
                exit();
            }
        }
        
        // Si falla, volver al control de sesiones
        header("Location: ../Frontend/control_sesiones.php");
        exit();
        
    } catch(PDOException $e) {
        error_log("Error al cerrar sesión remota: " . $e->getMessage());
        header("Location: ../Frontend/control_sesiones.php?error=1");
        exit();
    }
} else {
    header("Location: ../Frontend/iniciosession.html");
    exit();
}
?>