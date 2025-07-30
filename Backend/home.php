<?php
session_start();
require_once 'database.php';

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../Frontend/iniciosession.html");
    exit();
}

// Obtener cursos destacados
try {
    $stmt = $conn->prepare("SELECT c.*, u.nombre as profesor 
                          FROM cursos c 
                          JOIN usuarios u ON c.id_profesor = u.idu
                          ORDER BY c.fecha_creacion DESC LIMIT 6");
    $stmt->execute();
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error al obtener cursos: " . $e->getMessage());
    $cursos = [];
}

// Verificar si tiene suscripción activa
$tiene_suscripcion = false;
$suscripcion = null;
try {
    $stmt = $conn->prepare("SELECT s.*, p.nombre as plan, p.descuento_cursos 
                          FROM suscripciones s 
                          JOIN planes p ON s.idplan = p.idp
                          WHERE s.idusuario = :idu AND s.estado = 'activa' AND s.fecha_fin > NOW()");
    $stmt->bindParam(':idu', $_SESSION['idusuario']);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $tiene_suscripcion = true;
        $suscripcion = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    error_log("Error al verificar suscripción: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Cursos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        .badge-gratis {
            background-color: #28a745;
        }
        .badge-premium {
            background-color: #ffc107;
            color: #212529;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        .sidebar {
            position: sticky;
            top: 20px;
        }
    </style>
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="home.php">CursosPro</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="home.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="explorar_cursos.php">Explorar Cursos</a>
                    </li>
                    <?php if ($tiene_suscripcion): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="mis_cursos.php">Mis Cursos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="crear_curso.php">Crear Curso</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (!$tiene_suscripcion): ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white me-2" href="elegir_plan.php">Obtener Suscripción</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['usuario']) ?>&background=random" class="user-avatar me-1">
                            <?= htmlspecialchars($_SESSION['usuario']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="perfil.php">Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="mis_cursos.php">Mis Cursos</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="cerrarsesion.php">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-9">
                <h2 class="mb-4">Cursos Destacados</h2>
                
                <?php if (!$tiene_suscripcion): ?>
                <div class="alert alert-warning mb-4">
                    <h4 class="alert-heading">¡Obtén una suscripción para acceder a los cursos!</h4>
                    <p>Con nuestra suscripción premium podrás acceder a todos los cursos con un 20% de descuento y en hasta 3 dispositivos simultáneos.</p>
                    <a href="elegir_plan.php" class="btn btn-primary">Ver Planes</a>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <?php foreach ($cursos as $curso): ?>
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
                                <a href="ver_curso.php?id=<?= $curso['idcurso'] ?>" class="btn btn-outline-primary btn-sm">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-lg-3">
                <div class="card sidebar mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Categorías</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="#" class="text-decoration-none">Programación</a>
                                <span class="badge bg-primary rounded-pill">14</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="#" class="text-decoration-none">Diseño</a>
                                <span class="badge bg-primary rounded-pill">8</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="#" class="text-decoration-none">Negocios</a>
                                <span class="badge bg-primary rounded-pill">5</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <?php if ($tiene_suscripcion): ?>
                <div class="card sidebar mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Mi Suscripción</h5>
                        <p class="card-text">Plan: <strong><?= $suscripcion['plan'] ?></strong></p>
                        <p class="card-text">Vence: <?= date('d/m/Y', strtotime($suscripcion['fecha_fin'])) ?></p>
                        <p class="card-text">Descuento: <?= $suscripcion['descuento_cursos'] ?>%</p>
                        <a href="#" class="btn btn-outline-secondary btn-sm">Administrar</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>