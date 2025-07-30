<?php
// Verificar si hay una sesión activa
$tiene_suscripcion = false;
if (isset($_SESSION['usuario'])) {
    try {
        $stmt = $conn->prepare("SELECT s.*, p.nombre as plan, p.descuento_cursos 
                              FROM suscripciones s 
                              JOIN planes p ON s.idplan = p.idp
                              WHERE s.idusuario = :idu AND s.estado = 'activa' AND s.fecha_fin > NOW()");
        $stmt->bindParam(':idu', $_SESSION['idusuario']);
        $stmt->execute();
        $tiene_suscripcion = $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        error_log("Error al verificar suscripción: " . $e->getMessage());
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="home.php">CursosPro</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : '' ?>" href="home.php">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'explorar_cursos.php' ? 'active' : '' ?>" href="explorar_cursos.php">Explorar Cursos</a>
                </li>
                <?php if (isset($_SESSION['usuario']) && $tiene_suscripcion): ?>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'mis_cursos.php' ? 'active' : '' ?>" href="mis_cursos.php">Mis Cursos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'crear_curso.php' ? 'active' : '' ?>" href="crear_curso.php">Crear Curso</a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['usuario'])): ?>
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
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../Frontend/iniciosession.html">Iniciar Sesión</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../Frontend/registro.php">Registrarse</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>