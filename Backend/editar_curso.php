<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['usuario']) || !isset($_GET['id'])) {
    header("Location: home.php");
    exit();
}

$id_curso = (int)$_GET['id'];

// Verificar que el curso existe y pertenece al usuario
try {
    $stmt = $conn->prepare("SELECT * FROM cursos WHERE idcurso = :id_curso AND id_profesor = :id_profesor");
    $stmt->bindParam(':id_curso', $id_curso);
    $stmt->bindParam(':id_profesor', $_SESSION['idusuario']);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: mis_cursos.php?error=curso_no_encontrado");
        exit();
    }
    
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error al obtener curso: " . $e->getMessage());
    header("Location: mis_cursos.php?error=bd");
    exit();
}

// Obtener categorías disponibles
try {
    $stmt = $conn->prepare("SELECT * FROM categorias");
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error al obtener categorías: " . $e->getMessage());
    $categorias = [];
}

// Obtener clases del curso
try {
    $stmt = $conn->prepare("SELECT * FROM clases WHERE idcurso = :id_curso ORDER BY orden");
    $stmt->bindParam(':id_curso', $id_curso);
    $stmt->execute();
    $clases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error al obtener clases: " . $e->getMessage());
    $clases = [];
}

// Procesar actualización del curso
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_curso'])) {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $precio = (float)$_POST['precio'];
    $es_gratis = isset($_POST['es_gratis']) ? 1 : 0;
    $id_categoria = !empty($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : null;
    
    try {
        $stmt = $conn->prepare("UPDATE cursos 
                              SET titulo = :titulo, descripcion = :descripcion, precio = :precio, 
                                  id_categoria = :id_categoria, es_gratis = :es_gratis 
                              WHERE idcurso = :id_curso");
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':precio', $precio);
        $stmt->bindParam(':id_categoria', $id_categoria);
        $stmt->bindParam(':es_gratis', $es_gratis, PDO::PARAM_INT);
        $stmt->bindParam(':id_curso', $id_curso);
        $stmt->execute();
        
        $success = "Curso actualizado correctamente.";
    } catch(PDOException $e) {
        error_log("Error al actualizar curso: " . $e->getMessage());
        $error = "Error al actualizar el curso. Por favor, inténtalo de nuevo.";
    }
}

// Procesar nueva clase
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_clase'])) {
    $titulo = trim($_POST['titulo_clase']);
    $tipo = $_POST['tipo_clase'];
    $contenido = trim($_POST['contenido_clase']);
    $duracion = !empty($_POST['duracion_clase']) ? (int)$_POST['duracion_clase'] : null;
    
    // Calcular orden (siguiente al último)
    $orden = 1;
    if (!empty($clases)) {
        $ultima_clase = end($clases);
        $orden = $ultima_clase['orden'] + 1;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO clases 
                              (idcurso, titulo, tipo, contenido, duracion, orden) 
                              VALUES (:idcurso, :titulo, :tipo, :contenido, :duracion, :orden)");
        $stmt->bindParam(':idcurso', $id_curso);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':contenido', $contenido);
        $stmt->bindParam(':duracion', $duracion);
        $stmt->bindParam(':orden', $orden);
        $stmt->execute();
        
        header("Location: editar_curso.php?id=$id_curso&success=clase_agregada");
        exit();
    } catch(PDOException $e) {
        error_log("Error al agregar clase: " . $e->getMessage());
        $error = "Error al agregar la clase. Por favor, inténtalo de nuevo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Curso - CursosPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .curso-header {
            background-color: #343a40;
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #6c5ce7;
        }
        .clase-item {
            border-left: 3px solid #6c5ce7;
            transition: all 0.3s;
        }
        .clase-item:hover {
            background-color: #f8f9fa;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6c5ce7, #00b894);
            border: none;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="curso-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><?= htmlspecialchars($curso['titulo']) ?></h1>
                    <p class="lead">Editando curso</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="ver_curso.php?id=<?= $curso['idcurso'] ?>" class="btn btn-outline-light me-2">Vista previa</a>
                    <a href="mis_cursos.php" class="btn btn-light">Mis Cursos</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#informacion">Información</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#contenido">Contenido</a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Pestaña de información -->
            <div class="tab-pane fade show active" id="informacion">
                <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-4">
                                <label for="titulo" class="form-label">Título del curso</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" 
                                       value="<?= htmlspecialchars($curso['titulo']) ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required><?= htmlspecialchars($curso['descripcion']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Configuración</h5>
                                    
                                    <div class="mb-3">
                                        <label for="id_categoria" class="form-label">Categoría</label>
                                        <select class="form-select" id="id_categoria" name="id_categoria">
                                            <option value="">Selecciona una categoría</option>
                                            <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?= $categoria['idc'] ?>" <?= $curso['id_categoria'] == $categoria['idc'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($categoria['nombre']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="precio" class="form-label">Precio (USD)</label>
                                        <input type="number" class="form-control" id="precio" name="precio" 
                                               min="0" step="0.01" value="<?= $curso['precio'] ?>" <?= $curso['es_gratis'] ? 'disabled' : '' ?>>
                                    </div>
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="es_gratis" name="es_gratis" <?= $curso['es_gratis'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="es_gratis">Curso gratuito</label>
                                    </div>
                                    
                                    <button type="submit" name="guardar_curso" class="btn btn-primary w-100">Guardar Cambios</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Pestaña de contenido -->
            <div class="tab-pane fade" id="contenido">
                <?php if (isset($_GET['success']) && $_GET['success'] == 'clase_agregada'): ?>
                <div class="alert alert-success">Clase agregada correctamente.</div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="mb-4">Clases del curso</h4>
                        
                        <?php if (empty($clases)): ?>
                        <div class="alert alert-info">
                            Este curso aún no tiene clases. Agrega tu primera clase.
                        </div>
                        <?php else: ?>
                        <div class="list-group mb-4">
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
                                    <div>
                                        <a href="editar_clase.php?id=<?= $clase['idclase'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="eliminar_clase.php?id=<?= $clase['idclase'] ?>" 
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('¿Estás seguro de eliminar esta clase?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Agregar nueva clase</h5>
                                
                                <form method="post">
                                    <div class="mb-3">
                                        <label for="titulo_clase" class="form-label">Título</label>
                                        <input type="text" class="form-control" id="titulo_clase" name="titulo_clase" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="tipo_clase" class="form-label">Tipo de contenido</label>
                                        <select class="form-select" id="tipo_clase" name="tipo_clase" required>
                                            <option value="">Selecciona un tipo</option>
                                            <option value="video">Video</option>
                                            <option value="audio">Audio</option>
                                            <option value="texto">Texto</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="contenido_clase" class="form-label">Contenido (URL o texto)</label>
                                        <textarea class="form-control" id="contenido_clase" name="contenido_clase" rows="3" required></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="duracion_clase" class="form-label">Duración (minutos)</label>
                                        <input type="number" class="form-control" id="duracion_clase" name="duracion_clase" min="1">
                                    </div>
                                    
                                    <button type="submit" name="agregar_clase" class="btn btn-primary w-100">Agregar Clase</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para manejar el switch de curso gratuito
        document.getElementById('es_gratis').addEventListener('change', function() {
            const precioInput = document.getElementById('precio');
            if (this.checked) {
                precioInput.value = '0';
                precioInput.disabled = true;
            } else {
                precioInput.disabled = false;
            }
        });
    </script>
</body>
</html>