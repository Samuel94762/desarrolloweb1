<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: home.php");
    exit();
}

// Obtener cursos creados por el usuario (como profesor)
try {
    $stmt = $conn->prepare("SELECT * FROM cursos WHERE id_profesor = :id_profesor ORDER BY fecha_creacion DESC");
    $stmt->bindParam(':id_profesor', $_SESSION['idusuario']);
    $stmt->execute();
    $cursos_creados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error al obtener cursos creados: " . $e->getMessage());
    $cursos_creados = [];
}

// Obtener cursos comprados por el usuario (como estudiante)
try {
    $stmt = $conn->prepare("SELECT c.*, u.nombre as profesor 
                          FROM compras co 
                          JOIN cursos c ON co.idcurso = c.idcurso
                          JOIN usuarios u ON c.id_profesor = u.idu
                          WHERE co.idusuario = :idusuario
                          ORDER BY co.fecha DESC");
    $stmt->bindParam(':idusuario', $_SESSION['idusuario']);
    $stmt->execute();
    $cursos_comprados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error al obtener cursos comprados: " . $e->getMessage());
    $cursos_comprados = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Cursos - CursosPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .curso-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        .curso-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .curso-img {
            height: 180px;
            object-fit: cover;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #6c5ce7, #00b894);
        }
        .nav-pills .nav-link {
            color: #6c5ce7;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">Mis Cursos</h1>
        
        <ul class="nav nav-pills mb-4">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="pill" href="#cursos-comprados">Como Estudiante</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" href="#cursos-creados">Como Profesor</a>
            </li>
        </ul>
        
        <div class="tab-content">
            <!-- Pestaña de cursos comprados -->
            <div class="tab-pane fade show active" id="cursos-comprados">
                <?php if (empty($cursos_comprados)): ?>
                <div class="alert alert-info">
                    Aún no has comprado ningún curso. <a href="explorar_cursos.php">Explora nuestros cursos</a>.
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($cursos_comprados as $curso): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card curso-card">
                            <img src="https://via.placeholder.com/300x180?text=<?= urlencode($curso['titulo']) ?>" class="card-img-top curso-img" alt="<?= htmlspecialchars($curso['titulo']) ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge <?= $curso['es_gratis'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                                        <?= $curso['es_gratis'] ? 'Gratis' : '$' . $curso['precio'] ?>
                                    </span>
                                    <small class="text-muted"><?= $curso['puntuacion'] ?> <i class="fas fa-star text-warning"></i></small>
                                </div>
                                <h5 class="card-title"><?= htmlspecialchars($curso['titulo']) ?></h5>
                                <p class="card-text text-muted">Por <?= htmlspecialchars($curso['profesor']) ?></p>
                                <a href="ver_curso.php?id=<?= $curso['idcurso'] ?>" class="btn btn-outline-primary btn-sm">Continuar</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Pestaña de cursos creados -->
            <div class="tab-pane fade" id="cursos-creados">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Mis cursos creados</h4>
                    <a href="crear_curso.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo Curso
                    </a>
                </div>
                
                <?php if (empty($cursos_creados)): ?>
                <div class="alert alert-info">
                    Aún no has creado ningún curso. <a href="crear_curso.php">Crea tu primer curso</a>.
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($cursos_creados as $curso): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card curso-card">
                            <img src="https://via.placeholder.com/300x180?text=<?= urlencode($curso['titulo']) ?>" class="card-img-top curso-img" alt="<?= htmlspecialchars($curso['titulo']) ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge <?= $curso['es_gratis'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                                        <?= $curso['es_gratis'] ? 'Gratis' : '$' . $curso['precio'] ?>
                                    </span>
                                    <small class="text-muted"><?= $curso['puntuacion'] ?> <i class="fas fa-star text-warning"></i></small>
                                </div>
                                <h5 class="card-title"><?= htmlspecialchars($curso['titulo']) ?></h5>
                                <div class="d-flex justify-content-between mt-3">
                                    <a href="editar_curso.php?id=<?= $curso['idcurso'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="ver_curso.php?id=<?= $curso['idcurso'] ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>