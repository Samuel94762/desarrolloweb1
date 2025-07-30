<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../Frontend/iniciosession.html");
    exit();
}

// Obtener planes disponibles
try {
    $stmt = $conn->prepare("SELECT * FROM planes");
    $stmt->execute();
    $planes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error al obtener planes: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elegir Plan - CursosPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .plan-card {
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .plan-card.recomendado {
            border-color: #6c5ce7;
            position: relative;
        }
        .plan-card.recomendado::after {
            content: 'Recomendado';
            position: absolute;
            top: -10px;
            right: 20px;
            background: #6c5ce7;
            color: white;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .plan-price {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .btn-plan {
            background: linear-gradient(135deg, #6c5ce7, #00b894);
            border: none;
            padding: 10px 25px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="display-4">Elige tu plan</h1>
            <p class="lead">Selecciona el plan que mejor se adapte a tus necesidades</p>
        </div>

        <div class="row g-4">
            <?php foreach ($planes as $plan): ?>
            <div class="col-md-6">
                <div class="card plan-card h-100 <?= $plan['idp'] == 2 ? 'recomendado' : '' ?>">
                    <div class="card-body p-4 text-center">
                        <h3 class="card-title mb-3"><?= htmlspecialchars($plan['nombre']) ?></h3>
                        <div class="plan-price mb-4">
                            $<?= number_format($plan['precio'], 2) ?>
                            <small class="text-muted d-block">/mes</small>
                        </div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?= $plan['sesiones_permitidas'] ?> sesión(es) simultánea(s)
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Acceso a cursos gratuitos
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?= $plan['descuento_cursos'] ?>% descuento en cursos de pago
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Creación ilimitada de cursos
                            </li>
                        </ul>
                        <form action="procesar_pago.php" method="post">
                            <input type="hidden" name="id_plan" value="<?= $plan['idp'] ?>">
                            <button type="submit" class="btn btn-plan btn-primary rounded-pill text-white">
                                Seleccionar Plan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <p class="text-muted">¿Ya tienes una suscripción? <a href="home.php">Volver al inicio</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>