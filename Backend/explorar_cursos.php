<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: home.php");
    exit();
}

// Obtener categorías
try {
    $stmt = $conn->prepare("SELECT * FROM categorias");
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error al obtener categorías: " . $e->getMessage());
    $categorias = [];
}

// Filtrar por categoría si se especifica
$id_categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;
$filtro_titulo = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

// Construir consulta base
$sql = "SELECT c.*, u.nombre as profesor FROM cursos c JOIN usuarios u ON c.id_profesor = u.idu WHERE 1=1";
$params = [];

if ($id_categoria) {
    $sql .= " AND c.id_categoria = :id_categoria";
    $params[':id_categoria'] = $id_categoria;
}

if ($filtro_titulo) {
    $sql .= " AND c.titulo LIKE :titulo";
    $params[':titulo'] = "%$filtro_titulo%";
}

// Ordenar por (predeterminado: más recientes primero)
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'recientes';
switch ($orden) {
    case 'precio_asc':
        $sql .= " ORDER BY c.precio ASC";
        break;
    case 'precio_desc':
        $sql .= " ORDER BY c.precio DESC";
        break;
    case 'puntuacion':
        $sql .= " ORDER BY c.puntuacion DESC";
        break;
    default:
        $sql .= " ORDER BY c.fecha_creacion DESC";
}

// Obtener cursos
try {
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error al obtener cursos: " . $e->getMessage());
    $cursos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorar Cursos - CursosPro</title>
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
        .sidebar {
            position: sticky;
            top: 20px;
        }
        .categoria-link {
            transition: all 0.3s;
        }
        .categoria-link:hover, .categoria-link.active {
            background-color: #6c5ce7;
            color: white !important;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-3">
                <div class="card sidebar mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Filtrar por</h5>
                        
                        <form method="get" class="mb-4">
                            <div class="input-group">
                                <input type="text" class="form-control" name="busqueda" placeholder="Buscar..." value="<?= htmlspecialchars($filtro_titulo) ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                        
                        <h6 class="mb-3">Categorías</h6>
                        <div class="list-group list-group-flush">
                            <a href="explorar_cursos.php" 
                               class="list-group-item list-group-item-action categoria-link <?= !$id_categoria ? 'active' : '' ?>">
                                Todas las categorías
                            </a>
                            <?php foreach ($categorias as $categoria): ?>
                            <a href="explorar_cursos.php?categoria=<?= $categoria['idc'] ?>" 
                               class="list-group-item list-group-item-action categoria-link <?= $id_categoria == $categoria['idc'] ? 'active' : '' ?>">
                                <?= htmlspecialchars($categoria['nombre']) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <h6 class="mt-4 mb-3">Ordenar por</h6>
                        <div class="list-group list-group-flush">
                            <a href="explorar_cursos.php?<?= http_build_query(array_merge($_GET, ['orden' => 'recientes'])) ?>" 
                               class="list-group-item list-group-item-action <?= $orden == 'recientes' ? 'active' : '' ?>">
                                Más recientes
                            </a>
                            <a href="explorar_cursos.php?<?= http_build_query(array_merge($_GET, ['orden' => 'puntuacion'])) ?>" 
                               class="list-group-item list-group-item-action <?= $orden == 'puntuacion' ? 'active' : '' ?>">
                                Mejor puntuados
                            </a>
                            <a href="explorar_cursos.php?<?= http_build_query(array_merge($_GET, ['orden' => 'precio_asc'])) ?>" 
                               class="list-group-item list-group-item-action <?= $orden == 'precio_asc' ? 'active' : '' ?>">
                                Precio: menor a mayor
                            </a>
                            <a href="explorar_cursos.php?<?= http_build_query(array_merge($_GET, ['orden' => 'precio_desc'])) ?>" 
                               class="list-group-item list-group-item-action <?= $orden == 'precio_desc' ? 'active' : '' ?>">
                                Precio: mayor a menor
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <?php if ($id_categoria): ?>
                        <?= htmlspecialchars($categorias[array_search($id_categoria, array_column($categorias, 'idc'))]['nombre']) ?>
                        <?php else: ?>
                        Todos los cursos
                        <?php endif; ?>
                    </h2>
                    <small class="text-muted"><?= count($cursos) ?> cursos encontrados</small>
                </div>
                
                <?php if (empty($cursos)): ?>
                <div class="alert alert-info">
                    No se encontraron cursos con los filtros seleccionados.
                </div>
                <?php else: ?>
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>