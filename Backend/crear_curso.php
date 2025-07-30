<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: home.php");
    exit();
}

// Verificar que el usuario tiene una suscripción activa
try {
    $stmt = $conn->prepare("SELECT * FROM suscripciones 
                          WHERE idusuario = :idu AND estado = 'activa' AND fecha_fin > NOW()");
    $stmt->bindParam(':idu', $_SESSION['idusuario']);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: home.php?error=necesita_suscripcion");
        exit();
    }
} catch(PDOException $e) {
    error_log("Error al verificar suscripción: " . $e->getMessage());
    header("Location: home.php?error=bd");
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

// Procesar formulario de creación de curso
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $precio = (float)$_POST['precio'];
    $es_gratis = isset($_POST['es_gratis']) ? 1 : 0;
    $id_categoria = !empty($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : null;
    
    try {
        // Crear curso
        $stmt = $conn->prepare("INSERT INTO cursos 
                              (titulo, descripcion, precio, id_profesor, id_categoria, es_gratis) 
                              VALUES (:titulo, :descripcion, :precio, :id_profesor, :id_categoria, :es_gratis)");
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':precio', $precio);
        $stmt->bindParam(':id_profesor', $_SESSION['idusuario']);
        $stmt->bindParam(':id_categoria', $id_categoria);
        $stmt->bindParam(':es_gratis', $es_gratis, PDO::PARAM_INT);
        $stmt->execute();
        
        $id_curso = $conn->lastInsertId();
        
        // Redirigir a la página de edición del curso
        header("Location: editar_curso.php?id=$id_curso");
        exit();
        
    } catch(PDOException $e) {
        error_log("Error al crear curso: " . $e->getMessage());
        $error = "Error al crear el curso. Por favor, inténtalo de nuevo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Curso - CursosPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-title {
            border-bottom: 2px solid #6c5ce7;
            padding-bottom: 10px;
        }
        .btn-submit {
            background: linear-gradient(135deg, #6c5ce7, #00b894);
            border: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-container p-4 p-md-5">
                    <h2 class="form-title mb-4">Crear nuevo curso</h2>
                    
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-4">
                            <label for="titulo" class="form-label">Título del curso</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required></textarea>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="precio" class="form-label">Precio (USD)</label>
                                <input type="number" class="form-control" id="precio" name="precio" min="0" step="0.01" value="0">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="es_gratis" name="es_gratis">
                                    <label class="form-check-label" for="es_gratis">Curso gratuito</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="id_categoria" class="form-label">Categoría</label>
                            <select class="form-select" id="id_categoria" name="id_categoria">
                                <option value="">Selecciona una categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['idc'] ?>"><?= htmlspecialchars($categoria['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-submit btn-primary btn-lg">Crear Curso</button>
                        </div>
                    </form>
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