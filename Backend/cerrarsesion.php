<?php
session_start();
require_once 'database.php';

if (isset($_SESSION['idusuario']) && isset($_SESSION['sesion_id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM sesiones_activas 
                               WHERE idu = :idu AND session_id = :session_id");
        $stmt->bindParam(':idu', $_SESSION['idusuario']);
        $stmt->bindParam(':session_id', $_SESSION['sesion_id']);
        $stmt->execute();
    } catch(PDOException $e) {
        error_log("Error al eliminar sesión activa: " . $e->getMessage());
    }
}

session_unset();
session_destroy();
header("Location: ../Frontend/iniciosession.html");
exit();
?>