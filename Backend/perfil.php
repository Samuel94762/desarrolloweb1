<?php
session_start();
require_once 'database.php';

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: ../Frontend/iniciosession.html");
    exit();
}

// Obtener datos del usuario
try {
    $stmt = $conn->prepare("SELECT u.*, p.nombre as plan_nombre 
                          FROM usuarios u 
                          LEFT JOIN planes p ON u.idp = p.idp
                          WHERE u.idu = ?");
    $stmt->execute([$_SESSION['idusuario']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        throw new Exception("Usuario no encontrado");
    }
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - CursosPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .profile-header {
            background: linear-gradient(135deg, #6c5ce7, #00b894);
            color: white;
            border-radius: 15px 15px 0 0;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card profile-card mb-4">
                    <div class="card-header profile-header">
                        <h3 class="mb-0">Mi Perfil</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($usuario['nombre']) ?>&background=random" 
                                     class="rounded-circle mb-3" width="150" height="150">
                                <h4><?= htmlspecialchars($usuario['nombre']) ?></h4>
                                <span class="badge bg-primary"><?= htmlspecialchars($usuario['plan_nombre'] ?? 'Sin plan') ?></span>
                            </div>
                            <div class="col-md-8">
                                <form action="actualizar_perfil.php" method="post">
                                    <div class="mb-3">
                                        <label class="form-label">Nombre</label>
                                        <input type="text" class="form-control" name="nombre" 
                                               value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Apellidos</label>
                                        <input type="text" class="form-control" name="apellidos" 
                                               value="<?= htmlspecialchars($usuario['apellidos']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Correo electrónico</label>
                                        <input type="email" class="form-control" value="<?= htmlspecialchars($usuario['correo']) ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Plan actual</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($usuario['plan_nombre'] ?? 'Básico') ?>" readonly>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Actualizar perfil</button>
                                    <a href="cambiar_password.php" class="btn btn-outline-secondary">Cambiar contraseña</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>