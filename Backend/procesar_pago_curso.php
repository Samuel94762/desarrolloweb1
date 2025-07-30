<?php
session_start();
require_once 'database.php';
require __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['usuario'])) { 
    header("Location: home.php");
    exit();
}

if (!isset($_POST['id_curso'])) {
    header("Location: home.php");
    exit();
}

$id_curso = (int)$_POST['id_curso'];

// Obtener información del curso
try {
    $stmt = $conn->prepare("SELECT * FROM cursos WHERE idcurso = :id_curso");
    $stmt->bindParam(':id_curso', $id_curso);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: home.php?error=curso_no_encontrado");
        exit();
    }
    
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error al obtener curso: " . $e->getMessage());
    header("Location: home.php?error=bd");
    exit();
}

// Verificar si el usuario ya tiene acceso al curso
try {
    $stmt = $conn->prepare("SELECT idcompra FROM compras WHERE idusuario = :idu AND idcurso = :id_curso");
    $stmt->bindParam(':idu', $_SESSION['idusuario']);
    $stmt->bindParam(':id_curso', $id_curso);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        header("Location: ver_curso.php?id=$id_curso");
        exit();
    }
} catch(PDOException $e) {
    error_log("Error al verificar acceso: " . $e->getMessage());
    header("Location: home.php?error=bd");
    exit();
}

// Calcular precio con descuento si tiene suscripción premium
$precio_final = $curso['precio'];
$descuento = 0;

try {
    $stmt = $conn->prepare("SELECT p.descuento_cursos 
                          FROM suscripciones s 
                          JOIN planes p ON s.idplan = p.idp
                          WHERE s.idusuario = :idu AND s.estado = 'activa' AND s.fecha_fin > NOW()");
    $stmt->bindParam(':idu', $_SESSION['idusuario']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $suscripcion = $stmt->fetch(PDO::FETCH_ASSOC);
        $descuento = $suscripcion['descuento_cursos'];
        $precio_final = $curso['precio'] * (1 - $descuento / 100);
    }
} catch(PDOException $e) {
    error_log("Error al verificar suscripción: " . $e->getMessage());
}

// Configurar PayPal
$apiContext = new \PayPal\Rest\ApiContext(
    new \PayPal\Auth\OAuthTokenCredential(
        $_ENV['PAYPAL_CLIENT_ID'],
        $_ENV['PAYPAL_SECRET']
    )
);

$apiContext->setConfig([
    'mode' => $_ENV['PAYPAL_MODE'],
    'log.LogEnabled' => true,
    'log.FileName' => '../PayPal.log',
    'log.LogLevel' => 'DEBUG',
    'cache.enabled' => true,
]);

// Crear pago
$payer = new \PayPal\Api\Payer();
$payer->setPaymentMethod('paypal');

$amount = new \PayPal\Api\Amount();
$amount->setTotal($precio_final);
$amount->setCurrency('USD');

$transaction = new \PayPal\Api\Transaction();
$transaction->setAmount($amount);
$transaction->setDescription("Compra curso: {$curso['titulo']} - CursosPro");

$redirectUrls = new \PayPal\Api\RedirectUrls();
$redirectUrls->setReturnUrl($_ENV['APP_URL']."/Backend/confirmar_pago_curso.php?success=true")
    ->setCancelUrl($_ENV['APP_URL']."/Backend/confirmar_pago_curso.php?success=false");

$payment = new \PayPal\Api\Payment();
$payment->setIntent('sale')
    ->setPayer($payer)
    ->setTransactions([$transaction])
    ->setRedirectUrls($redirectUrls);

try {
    $payment->create($apiContext);
    
    // Guardar transacción temporal en sesión
    $_SESSION['pago_pendiente_curso'] = [
        'id_payment' => $payment->getId(),
        'id_curso' => $id_curso,
        'monto' => $precio_final,
        'descuento' => $descuento,
        'precio_original' => $curso['precio'],
        'tipo' => 'compra_curso'
    ];
    
    // Redirigir a PayPal para el pago
    header("Location: " . $payment->getApprovalLink());
    exit();
    
} catch (\PayPal\Exception\PayPalConnectionException $ex) {
    error_log("Error PayPal: " . $ex->getData());
    header("Location: ver_curso.php?id=$id_curso&error=paypal");
    exit();
}