<?php
session_start();
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = trim($_POST['correo']);
    $passwd = trim($_POST['passwd']);

    try {
        $stmt = $conn->prepare("SELECT u.*, r.nombre as rol, p.sesiones_permitidas 
                               FROM usuarios u 
                               JOIN roles r ON u.idr = r.idr 
                               JOIN planes p ON u.idp = p.idp
                               WHERE u.correo = :correo");
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($passwd, $usuario['passwd'])) {
                // Verificar si el email está verificado
                if (!$usuario['verificado']) {
                    header("Location: ../Frontend/iniciosession.html?error=no_verificado&correo=".urlencode($correo));
                    exit();
                }
                
                // Verificar sesiones activas
                $stmt = $conn->prepare("SELECT COUNT(*) as sesiones_activas FROM sesiones_activas WHERE idu = :idu");
                $stmt->bindParam(':idu', $usuario['idu']);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $sesiones_activas = $result['sesiones_activas'];
                
                if ($sesiones_activas >= $usuario['sesiones_permitidas']) {
                    // Guardar datos en sesión para mostrar en la página de control
                    $_SESSION['login_attempt'] = [
                        'idu' => $usuario['idu'],
                        'correo' => $usuario['correo'],
                        'nombre' => $usuario['nombre'],
                        'sesiones_permitidas' => $usuario['sesiones_permitidas'],
                        'sesiones_activas' => $sesiones_activas
                    ];
                    
                    header("Location: control_sesiones.php");
                    exit();
                }
                
                // Registrar nueva sesión
                $session_id = session_id();
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                
                $stmt = $conn->prepare("INSERT INTO sesiones_activas (idu, session_id, ip_address, user_agent) 
                                      VALUES (:idu, :session_id, :ip_address, :user_agent)");
                $stmt->bindParam(':idu', $usuario['idu']);
                $stmt->bindParam(':session_id', $session_id);
                $stmt->bindParam(':ip_address', $ip_address);
                $stmt->bindParam(':user_agent', $user_agent);
                $stmt->execute();
                
                $_SESSION['idusuario'] = $usuario['idu'];
                $_SESSION['usuario'] = $usuario['nombre'];
                $_SESSION['correo'] = $usuario['correo'];
                $_SESSION['idr'] = $usuario['idr'];
                $_SESSION['rol'] = $usuario['rol'];
                $_SESSION['idp'] = $usuario['idp'];
                $_SESSION['sesion_id'] = $session_id;
                
                // Redirección según rol
                if ($usuario['idr'] == 1) {
                    header("Location: home.php");
                } else {
                    header("Location: home.php");
                }
                exit();
            }
        }
        
        // Si llega aquí es porque falló la autenticación
        header("Location: ../Frontend/iniciosession.html?error=credenciales_invalidas&correo=".urlencode($correo));
        exit();
        
    } catch(PDOException $e) {
        error_log("Error en autenticación: " . $e->getMessage());
        header("Location: ../Frontend/iniciosession.html?error=error_bd");
        exit();
    }
} else {
    header("Location: ../Frontend/iniciosession.html");
    exit();
}
?>