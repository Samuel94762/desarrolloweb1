<?php
session_start();
require_once '../Backend/database.php';

// Obtener lista de planes disponibles
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
    <title>Registro de Cuenta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0984e3, #6c5ce7);
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
        h1 {
            color: #6c5ce7;
        }
        .plan-option {
            transition: all 0.3s;
            cursor: pointer;
            margin-bottom: 15px;
        }
        .plan-option:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .plan-option.selected {
            border: 2px solid #6c5ce7;
            background-color: #f8f9fa;
        }
        .plan-features {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .seleccion-plan {
        display: none !important;
    }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow-lg p-4" style="max-width: 600px; width: 100%">
            <h1 class="text-center mb-4">Crear una cuenta</h1>
            
            <form action="../Backend/register.php" method="post">
                <!-- Sección de selección de plan -->
                <div class="seleccion-plan mb-4">
                    <label class="form-label mb-3"><strong>Selecciona tu plan:</strong></label>
                    
                    <div class="row">
                        <?php foreach ($planes as $plan): ?>
                        <div class="col-md-6">
                            <div class="card plan-option p-3" 
                                 onclick="selectPlan(this, <?= $plan['idp'] ?>)">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" 
                                           name="idp" id="plan<?= $plan['idp'] ?>" 
                                           value="<?= $plan['idp'] ?>" 
                                           <?= $plan['idp'] == 2 ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="plan<?= $plan['idp'] ?>">
                                        <?= htmlspecialchars($plan['nombre']) ?>
                                    </label>
                                </div>
                                <div class="plan-features mt-2">
                                    <div><?= $plan['sesiones_permitidas'] ?> sesión(es) activa(s)</div>
                                    <div><?= $plan['idp'] == 1 ? 'Acceso básico' : 'Acceso completo' ?></div>
                                    <?php if ($plan['idp'] == 2): ?>
                                    <div>Soporte prioritario</div>
                                    <div>Estadísticas avanzadas</div>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2 fw-bold">
                                    <?= $plan['idp'] == 1 ? 'GRATIS' : '$9.99/mes' ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Campos del formulario -->
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                </div>
                
                <div class="mb-3">
                    <label for="apellidos" class="form-label">Apellidos</label>
                    <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-text">Mínimo 8 caracteres</div>
                </div>
                
                <div class="mb-4">
                    <label for="idr" class="form-label">Rol</label>
                    <select class="form-select" id="idr" name="idr" required>
                        <option value="" disabled selected>Selecciona tu rol</option>
                        <option value="1">Administrador</option>
                        <option value="2">Invitado</option>
                    </select>
                </div>
                
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-lg">Registrarse</button>
                </div>
                
                <div class="text-center">
                    <span>¿Ya tienes una cuenta? <a href="iniciosession.html">Inicia sesión</a></span>
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectPlan(element, planId) {
            // Marcar el radio button correspondiente
            const radio = element.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Remover clase 'selected' de todos los planes
            document.querySelectorAll('.plan-option').forEach(plan => {
                plan.classList.remove('selected');
            });
            
            // Agregar clase 'selected' al plan clickeado
            element.classList.add('selected');
        }
        
        // Seleccionar el plan básico por defecto al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const basicPlan = document.querySelector('.plan-option');
            if (basicPlan) {
                basicPlan.classList.add('selected');
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>