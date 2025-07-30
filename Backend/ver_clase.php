<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['usuario']) || !isset($_GET['id'])) {
    header("Location: home.php");
    exit();
}

$id_clase = (int)$_GET['id'];

// Obtener información de la clase y verificar acceso
try {
    $stmt = $conn->prepare("SELECT cl.*, c.id_profesor, c.titulo as titulo_curso, c.es_gratis 
                          FROM clases cl 
                          JOIN cursos c ON cl.idcurso = c.idcurso
                          WHERE cl.idclase = :id_clase");
    $stmt->bindParam(':id_clase', $id_clase);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: home.php?error=clase_no_encontrada");
        exit();
    }
    
    $clase = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error al obtener clase: " . $e->getMessage());
    header("Location: home.php?error=bd");
    exit();
}

// Verificar acceso al curso
$tiene_acceso = false;
if ($clase['es_gratis']) {
    $tiene_acceso = true;
} else {
    try {
        $stmt = $conn->prepare("SELECT idcompra FROM compras WHERE idusuario = :idu AND idcurso = :id_curso");
        $stmt->bindParam(':idu', $_SESSION['idusuario']);
        $stmt->bindParam(':id_curso', $clase['idcurso']);
        $stmt->execute();
        $tiene_acceso = $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        error_log("Error al verificar acceso: " . $e->getMessage());
        header("Location: home.php?error=bd");
        exit();
    }
}

if (!$tiene_acceso) {
    header("Location: ver_curso.php?id=" . $clase['idcurso']);
    exit();
}

// Obtener clases del curso para la navegación
try {
    $stmt = $conn->prepare("SELECT idclase, titulo FROM clases 
                          WHERE idcurso = :id_curso 
                          ORDER BY orden");
    $stmt->bindParam(':id_curso', $clase['idcurso']);
    $stmt->execute();
    $clases_curso = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error al obtener clases del curso: " . $e->getMessage());
    $clases_curso = [];
}

// Encontrar índice de la clase actual
$indice_actual = array_search($id_clase, array_column($clases_curso, 'idclase'));
$clase_anterior = $indice_actual > 0 ? $clases_curso[$indice_actual - 1] : null;
$clase_siguiente = $indice_actual < count($clases_curso) - 1 ? $clases_curso[$indice_actual + 1] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($clase['titulo']) ?> - CursosPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .clase-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .clase-sidebar {
            position: sticky;
            top: 20px;
        }
        .clase-item {
            border-left: 3px solid #dee2e6;
            transition: all 0.3s;
        }
        .clase-item:hover {
            background-color: #f8f9fa;
        }
        .clase-item.activa {
            border-left-color: #6c5ce7;
            background-color: #f0f2ff;
        }
        .btn-navegacion {
            background: linear-gradient(135deg, #6c5ce7, #00b894);
            border: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-8">
                <div class="clase-container p-4 mb-4">
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="home.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="ver_curso.php?id=<?= $clase['idcurso'] ?>"><?= htmlspecialchars($clase['titulo_curso']) ?></a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($clase['titulo']) ?></li>
                        </ol>
                    </nav>
                    
                    <h2 class="mb-4"><?= htmlspecialchars($clase['titulo']) ?></h2>
                    
                    <?php if ($clase['tipo'] == 'video'): ?>
                    <div class="ratio ratio-16x9 mb-4">
                        <iframe src="<?= htmlspecialchars($clase['contenido']) ?>" allowfullscreen></iframe>
                    </div>
                    <?php elseif ($clase['tipo'] == 'audio'): ?>
                    <div class="mb-4">
                        <audio controls class="w-100">
                            <source src="<?= htmlspecialchars($clase['contenido']) ?>" type="audio/mpeg">
                            Tu navegador no soporta el elemento de audio.
                        </audio>
                    </div>
                    <?php else: ?>
                    <div class="mb-4 p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($clase['contenido'])) ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <?php if ($clase_anterior): ?>
                        <a href="ver_clase.php?id=<?= $clase_anterior['idclase'] ?>" class="btn btn-navegacion btn-primary">
                            <i class="fas fa-arrow-left me-1"></i> Anterior
                        </a>
                        <?php else: ?>
                        <span></span>
                        <?php endif; ?>
                        
                        <?php if ($clase_siguiente): ?>
                        <a href="ver_clase.php?id=<?= $clase_siguiente['idclase'] ?>" class="btn btn-navegacion btn-primary">
                            Siguiente <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card clase-sidebar">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Contenido del curso</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($clases_curso as $index => $cl): ?>
                            <a href="ver_clase.php?id=<?= $cl['idclase'] ?>" 
                               class="list-group-item list-group-item-action clase-item <?= $cl['idclase'] == $id_clase ? 'activa' : '' ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><?= ($index + 1) . '. ' . htmlspecialchars($cl['titulo']) ?></span>
                                    <?php if ($cl['idclase'] == $id_clase): ?>
                                    <i class="fas fa-play text-primary"></i>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>