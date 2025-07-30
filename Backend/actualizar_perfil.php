<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: perfil.php");
    exit();
}

$nombre = trim($_POST['nombre']);
$apellidos = trim($_POST['apellidos']);

try {
    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, apellidos = ? WHERE idu = ?");
    $stmt->execute([$nombre, $apellidos, $_SESSION['idusuario']]);
    
    // Actualizar datos en sesión
    $_SESSION['usuario'] = $nombre;
    
    header("Location: perfil.php?success=1");
} catch (PDOException $e) {
    header("Location: perfil.php?error=bd");
}