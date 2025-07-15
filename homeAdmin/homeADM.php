<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['idr'] != 1) {
    header("Location: Frontend/iniciosession.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Panel Admin</title>
<style>
    body { font-family: Arial, sans-serif; margin:0; }
    nav { background-color: #333; overflow: hidden; }
    nav a {
        float: left;
        display: block;
        color: #fff;
        text-align: center;
        padding: 14px 20px;
        text-decoration: none;
    }
    nav a:hover {
        background-color: #575757;
    }
    .container {
        padding: 20px;
    }
</style>
</head>
<body>

<nav>
    <a href="homeADM.php">INICIO</a>
    <a href="listarusuarios.php">LISTAR USUARIOS</a>
    <a href="cerrarsesion.php">SALIR</a>
</nav>

<div class="container">
    <h2>Bienvenido Admin, <?php echo htmlspecialchars($_SESSION['usuario']); ?></h2>
    <p>Tu correo: <?php echo htmlspecialchars($_SESSION['correo']); ?></p>
</div>

</body>
</html>
