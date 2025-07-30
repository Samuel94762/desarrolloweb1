<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['usuario']) || !isset($_GET['id'])) {
    header("Location: home.php");
    exit();
}

$id_curso = (int)$_GET['id'];

// Obtener información del curso
try {
    $stmt = $conn->prepare("SELECT c.*, u.nombre as profesor 
                          FROM cursos c 
                          JOIN usuarios u ON c.id_profesor = u.idu
                          WHERE c.idcurso = :id_curso");
    $stmt->bindParam(':id_curso', $id_curso);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: home.php?error=curso_no_encontrado");
        exit();
    }
    
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error al obtener curso: " . $e->getMessage());
    header("Location: home.php?error=bd");
    exit();
}

// Verificar si el usuario tiene acceso al curso
$tiene_acceso = false;
if ($curso['es_gratis']) {
    $tiene_acceso = true;
} else {
    // Verificar si el usuario ha comprado el curso
    try {
        $stmt = $conn->prepare("SELECT idcompra FROM compras WHERE idusuario = :idu AND idcurso = :id_curso");
        $stmt->bindParam(':idu', $_SESSION['idusuario']);
        $stmt->bindParam(':id_curso', $id_curso);
        $stmt->execute();
        $tiene_acceso = $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        error_log("Error al verificar acceso: " . $e->getMessage());
    }
}

// Obtener reseñas del curso
try {
    $stmt = $conn->prepare("SELECT r.*, u.nombre as usuario 
                          FROM reseñas r 
                          JOIN usuarios u ON r.idusuario = u.idu
                          WHERE r.idcurso = :id_curso
                          ORDER BY r.fecha DESC");
    $stmt->bindParam(':id_curso', $id_curso);
    $stmt->execute();
    $reseñas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error al obtener reseñas: " . $e->getMessage());
    $reseñas = [];
}

// Obtener clases del curso
if ($tiene_acceso) {
    try {
        $stmt = $conn->prepare("SELECT * FROM clases 
                              WHERE idcurso = :id_curso
                              ORDER BY orden");
        $stmt->bindParam(':id_curso', $id_curso);
        $stmt->execute();
        $clases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error al obtener clases: " . $e->getMessage());
        $clases = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($curso['titulo']) ?> - CursosPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .curso-header {
            background-color: #343a40;
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .curso-img {
            max-height: 300px;
            object-fit: cover;
        }
        .clase-item {
            border-left: 3px solid #6c5ce7;
            transition: all 0.3s;
        }
        .clase-item:hover {
            background-color: #f8f9fa;
        }
        .estrella {
            color: #ffc107;
            font-size: 1.5rem;
        }
        .btn-comprar {
            background: linear-gradient(135deg, #6c5ce7, #00b894);
            border: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="curso-header">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <h1><?= htmlspecialchars($curso['titulo']) ?></h1>
                    <p class="lead">Por <?= htmlspecialchars($curso['profesor']) ?></p>
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <?php 
                            $puntuacion = $curso['puntuacion'];
                            for ($i = 1; $i <= 5; $i++): 
                                $clase = $i <= $puntuacion ? 'fas fa-star estrella' : 'far fa-star estrella';
                            ?>
                            <i class="<?= $clase ?>"></i>
                            <?php endfor; ?>
                            <span class="ms-2">(<?= count($reseñas) ?> reseñas)</span>
                        </div>
                        <span class="badge <?= $curso['es_gratis'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= $curso['es_gratis'] ? 'Gratis' : '$' . $curso['precio'] ?>
                        </span>
                    </div>
                </div>
                <?php if (!$curso['es_gratis'] && !$tiene_acceso): ?>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Comprar Curso</h4>
                            <p class="card-text">$<?= number_format($curso['precio'], 2) ?></p>
                            
                            <?php 
                            // Verificar si tiene suscripción activa para aplicar descuento
                            $descuento = 0;
                            try {
                                $stmt = $conn->prepare("SELECT p.descuento_cursos 
                                                      FROM suscripciones s 
                                                      JOIN planes p ON s.idplan = p.idp
                                                      WHERE s.idusuario = :idu AND s.estado = 'activa' AND s.fecha_fin > NOW()");
                                $stmt->bindParam(':idu', $_SESSION['idusuario']);
                                $stmt->execute();
                                if ($stmt->rowCount() > 0) {
                                    $suscripcion = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $descuento = $suscripcion['descuento_cursos'];
                                }
                            } catch(PDOException $e) {
                                error_log("Error al verificar suscripción: " . $e->getMessage());
                            }
                            
                            if ($descuento > 0):
                                $precio_con_descuento = $curso['precio'] * (1 - $descuento / 100);
                            ?>
                            <p class="text-success">
                                <del>$<?= number_format($curso['precio'], 2) ?></del>
                                $<?= number_format($precio_con_descuento, 2) ?> (<?= $descuento ?>% descuento)
                            </p>
                            <?php endif; ?>
                            
                            <form action="procesar_pago_curso.php" method="post">
                                <input type="hidden" name="id_curso" value="<?= $curso['idcurso'] ?>">
                                <button type="submit" class="btn btn-comprar btn-primary w-100">
                                    Comprar Ahora
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">Descripción</h3>
                        <p class="card-text"><?= nl2br(htmlspecialchars($curso['descripcion'])) ?></p>
                    </div>
                </div>
                
                <?php if ($tiene_acceso): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">Contenido del Curso</h3>
                        <div class="list-group">
                            <?php foreach ($clases as $clase): ?>
                            <div class="list-group-item clase-item mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars($clase['titulo']) ?></h5>
                                        <small class="text-muted">
                                            <i class="fas fa-<?= 
                                                $clase['tipo'] == 'video' ? 'video' : 
                                                ($clase['tipo'] == 'audio' ? 'volume-up' : 'file-alt') 
                                            ?> me-1"></i>
                                            <?= ucfirst($clase['tipo']) ?> • 
                                            <?= $clase['duracion'] ?? '0' ?> min
                                        </small>
                                    </div>
                                    <a href="ver_clase.php?id=<?= $clase['idclase'] ?>" class="btn btn-sm btn-outline-primary">
                                        Ver
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">Reseñas</h3>
                        
                        <?php if (count($reseñas) > 0): ?>
                            <?php foreach ($reseñas as $reseña): ?>
                            <div class="mb-4 pb-3 border-bottom">
                                <div class="d-flex justify-content-between mb-2">
                                    <h5><?= htmlspecialchars($reseña['usuario']) ?></h5>
                                    <div>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?= $i <= $reseña['puntuacion'] ? 'fas fa-star estrella' : 'far fa-star estrella' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="text-muted"><small><?= date('d/m/Y H:i', strtotime($reseña['fecha'])) ?></small></p>
                                <p><?= htmlspecialchars($reseña['comentario']) ?></p>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Este curso aún no tiene reseñas.</p>
                        <?php endif; ?>
                        
                        <?php 
                        // Verificar si el usuario puede dejar reseña (debe tener acceso al curso)
                        if ($tiene_acceso) {
                            try {
                                $stmt = $conn->prepare("SELECT idreseña FROM reseñas WHERE idusuario = :idu AND idcurso = :id_curso");
                                $stmt->bindParam(':idu', $_SESSION['idusuario']);
                                $stmt->bindParam(':id_curso', $id_curso);
                                $stmt->execute();
                                $ya_reseñado = $stmt->rowCount() > 0;
                            } catch(PDOException $e) {
                                error_log("Error al verificar reseña: " . $e->getMessage());
                                $ya_reseñado = false;
                            }
                            
                            if (!$ya_reseñado):
                        ?>
                        <div class="mt-4">
                            <h4>Deja tu reseña</h4>
                            <form action="procesar_reseña.php" method="post">
                                <input type="hidden" name="id_curso" value="<?= $curso['idcurso'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Puntuación</label>
                                    <div class="rating">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?= $i ?>" name="puntuacion" value="<?= $i ?>" <?= $i == 5 ? 'checked' : '' ?>>
                                        <label for="star<?= $i ?>"><i class="fas fa-star estrella"></i></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="comentario" class="form-label">Comentario</label>
                                    <textarea class="form-control" id="comentario" name="comentario" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Enviar Reseña</button>
                            </form>
                        </div>
                        <?php 
                            endif;
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Acerca del instructor</h4>
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($curso['profesor']) ?>&background=random" 
                                 class="rounded-circle me-3" width="60" height="60">
                            <h5><?= htmlspecialchars($curso['profesor']) ?></h5>
                        </div>
                        <p class="card-text">Experto en su campo con años de experiencia enseñando.</p>
                        <a href="#" class="btn btn-outline-primary">Ver perfil</a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Detalles del curso</h4>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Duración total</span>
                                <span>
                                    <?php 
                                    $duracion_total = 0;
                                    if ($tiene_acceso) {
                                        foreach ($clases as $clase) {
                                            $duracion_total += $clase['duracion'] ?? 0;
                                        }
                                    }
                                    echo $duracion_total > 0 ? $duracion_total . ' min' : 'No disponible';
                                    ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Clases</span>
                                <span><?= $tiene_acceso ? count($clases) : '?' ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Nivel</span>
                                <span>Intermedio</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Idioma</span>
                                <span>Español</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Certificado</span>
                                <span><?= $curso['es_gratis'] ? 'No' : 'Sí' ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para el sistema de puntuación con estrellas
        document.querySelectorAll('.rating input').forEach(input => {
            input.addEventListener('change', function() {
                const stars = this.parentElement.querySelectorAll('label');
                const value = parseInt(this.value);
                
                stars.forEach((star, index) => {
                    const icon = star.querySelector('i');
                    if (index >= 5 - value) {
                        icon.classList.add('fas');
                        icon.classList.remove('far');
                    } else {
                        icon.classList.add('far');
                        icon.classList.remove('fas');
                    }
                });
            });
        });
    </script>
</body>
</html>