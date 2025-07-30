<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['usuario']) || $_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['id_curso'])) {
    header("Location: home.php");
    exit();
}

$id_curso = (int)$_POST['id_curso'];
$puntuacion = (int)$_POST['puntuacion'];
$comentario = trim($_POST['comentario']);

// Validar puntuación
if ($puntuacion < 1 || $puntuacion > 5) {
    header("Location: ver_curso.php?id=$id_curso&error=puntuacion_invalida");
    exit();
}

// Verificar que el usuario tiene acceso al curso
try {
    $stmt = $conn->prepare("SELECT c.es_gratis, COUNT(co.idcompra) as comprado 
                          FROM cursos c 
                          LEFT JOIN compras co ON c.idcurso = co.idcurso AND co.idusuario = :idu
                          WHERE c.idcurso = :id_curso
                          GROUP BY c.idcurso");
    $stmt->bindParam(':idu', $_SESSION['idusuario']);
    $stmt->bindParam(':id_curso', $id_curso);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: home.php?error=curso_no_encontrado");
        exit();
    }
    
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
    $tiene_acceso = $curso['es_gratis'] || $curso['comprado'] > 0;
    
    if (!$tiene_acceso) {
        header("Location: ver_curso.php?id=$id_curso&error=acceso_denegado");
        exit();
    }
} catch(PDOException $e) {
    error_log("Error al verificar acceso: " . $e->getMessage());
    header("Location: ver_curso.php?id=$id_curso&error=bd");
    exit();
}

// Verificar que el usuario no ha dejado ya una reseña para este curso
try {
    $stmt = $conn->prepare("SELECT idreseña FROM resenas WHERE idusuario = :idu AND idcurso = :id_curso");
    $stmt->bindParam(':idu', $_SESSION['idusuario']);
    $stmt->bindParam(':id_curso', $id_curso);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        header("Location: ver_curso.php?id=$id_curso&error=ya_reseñado");
        exit();
    }
} catch(PDOException $e) {
    error_log("Error al verificar reseña existente: " . $e->getMessage());
    header("Location: ver_curso.php?id=$id_curso&error=bd");
    exit();
}

// Insertar reseña
try {
    $stmt = $conn->prepare("INSERT INTO reseñas 
                          (idusuario, idcurso, puntuacion, comentario) 
                          VALUES (:idu, :id_curso, :puntuacion, :comentario)");
    $stmt->bindParam(':idu', $_SESSION['idusuario']);
    $stmt->bindParam(':id_curso', $id_curso);
    $stmt->bindParam(':puntuacion', $puntuacion);
    $stmt->bindParam(':comentario', $comentario);
    $stmt->execute();
    
    // Actualizar puntuación promedio del curso
    $stmt = $conn->prepare("UPDATE cursos c
                          SET c.puntuacion = (
                              SELECT AVG(r.puntuacion) 
                              FROM reseñas r 
                              WHERE r.idcurso = c.idcurso
                          )
                          WHERE c.idcurso = :id_curso");
    $stmt->bindParam(':id_curso', $id_curso);
    $stmt->execute();
    
    header("Location: ver_curso.php?id=$id_curso&success=reseña_agregada");
    exit();
} catch(PDOException $e) {
    error_log("Error al agregar reseña: " . $e->getMessage());
    header("Location: ver_curso.php?id=$id_curso&error=bd");
    exit();
}