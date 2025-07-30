<?php
session_start();
require_once 'database.php';

// Verificar permisos (solo profesores o admin pueden eliminar)
if (!isset($_SESSION['usuario']) || ($_SESSION['idr'] != 1 && $_SESSION['idr'] != 2)) {
    header("Location: ../Frontend/iniciosession.html");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: mis_cursos.php?error=id_no_provisto");
    exit();
}

$id_clase = (int)$_GET['id'];

try {
    // Verificar que el usuario es el profesor dueño del curso
    $stmt = $conn->prepare("SELECT c.id_profesor 
                          FROM clases cl
                          JOIN cursos c ON cl.idcurso = c.idcurso
                          WHERE cl.idclase = ?");
    $stmt->execute([$id_clase]);
    $clase = $stmt->fetch();

    if (!$clase || ($clase['id_profesor'] != $_SESSION['idusuario'] && $_SESSION['idr'] != 1)) {
        header("Location: mis_cursos.php?error=no_autorizado");
        exit();
    }

    // Eliminar la clase
    $stmt = $conn->prepare("DELETE FROM clases WHERE idclase = ?");
    $stmt->execute([$id_clase]);

    // Redirigir con mensaje de éxito
    header("Location: editar_curso.php?id=" . $clase['idcurso'] . "&success=clase_eliminada");
    exit();

} catch(PDOException $e) {
    error_log("Error al eliminar clase: " . $e->getMessage());
    header("Location: editar_curso.php?error=bd");
    exit();
}