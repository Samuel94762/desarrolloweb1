<?php
session_start();
require_once 'database.php';

// Verificar que hay un intento de login con sesiones excedidas
if (!isset($_SESSION['login_attempt'])) {
    header("Location: ../Frontend/iniciosession.html");
    exit();
}

$login_attempt = $_SESSION['login_attempt'];
$idu = $login_attempt['idu'];

try {
    // Obtener sesiones activas
    $stmt = $conn->prepare("SELECT ids, session_id, fecha_inicio, ip_address, user_agent 
                           FROM sesiones_activas 
                           WHERE idu = :idu 
                           ORDER BY fecha_inicio DESC");
    $stmt->bindParam(':idu', $idu);
    $stmt->execute();
    $sesiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Sesiones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6c5ce7, #00b894);
        }
        .card {
            border-radius: 1rem;
            background-color: #ffffffdd;
            backdrop-filter: blur(10px);
        }
        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }
        .btn-primary:hover {
            background-color: #5a4ccf;
            border-color: #5a4ccf;
        }
        .session-card {
            transition: all 0.3s;
        }
        .session-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow-lg p-4" style="max-width: 800px; width: 100%">
            <h1 class="text-center mb-4">Límite de sesiones alcanzado</h1>
            
            <div class="alert alert-warning">
                <p>Hola <?php echo htmlspecialchars($login_attempt['nombre']); ?>,</p>
                <p>Tu plan <?php echo $login_attempt['sesiones_permitidas'] == 1 ? 'Básico' : 'Premium'; ?> 
                permite un máximo de <?php echo $login_attempt['sesiones_permitidas']; ?> sesión(es) activa(s).</p>
                <p>Actualmente tienes <?php echo $login_attempt['sesiones_activas']; ?> sesión(es) activa(s).</p>
                <p>Por favor cierra al menos una sesión para continuar.</p>
            </div>
            
            <h3 class="mb-3">Tus sesiones activas:</h3>
            
            <div class="row">
                <?php foreach ($sesiones as $sesion): ?>
                <div class="col-md-6 mb-3">
                    <div class="card session-card p-3">
                        <div class="d-flex justify-content-between">
                            <h5>Sesión #<?php echo $sesion['ids']; ?></h5>
                            <form action="../Backend/cerrar_sesion_remota.php" method="post">
                                <input type="hidden" name="ids" value="<?php echo $sesion['ids']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Cerrar</button>
                            </form>
                        </div>
                        <p class="mb-1"><small>Iniciada: <?php echo $sesion['fecha_inicio']; ?></small></p>
                        <p class="mb-1"><small>IP: <?php echo $sesion['ip_address']; ?></small></p>
                        <p class="mb-1 text-truncate"><small>Navegador: <?php echo htmlspecialchars($sesion['user_agent']); ?></small></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="d-grid mt-4">
                <a href="../Frontend/iniciosession.html" class="btn btn-secondary">Volver al inicio</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>