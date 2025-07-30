<?php
session_start();
require_once 'database.php';
require __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['usuario']) || !isset($_POST['id_plan'])) {
    header("Location: elegir_plan.php");
    exit();
}

$id_plan = (int)$_POST['id_plan'];

// Verificar que el plan existe
try {
    $stmt = $conn->prepare("SELECT * FROM planes WHERE idp = :id_plan");
    $stmt->bindParam(':id_plan', $id_plan);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: elegir_plan.php?error=plan_invalido");
        exit();
    }
    
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error al verificar plan: " . $e->getMessage());
    header("Location: elegir_plan.php?error=bd");
    exit();
}

// Configurar PayPal
$apiContext = new \PayPal\Rest\ApiContext(
    new \PayPal\Auth\OAuthTokenCredential(
        $_ENV['PAYPAL_CLIENT_ID'],     // ClientID
        $_ENV['PAYPAL_SECRET']      // ClientSecret
    )
);

$apiContext->setConfig([
    'mode' => $_ENV['PAYPAL_MODE'], // 'sandbox' o 'live'
    'log.LogEnabled' => true,
    'log.FileName' => '../PayPal.log',
    'log.LogLevel' => 'DEBUG',
    'cache.enabled' => true,
]);

// Crear pago
$payer = new \PayPal\Api\Payer();
$payer->setPaymentMethod('paypal');

$amount = new \PayPal\Api\Amount();
$amount->setTotal($plan['precio']);
$amount->setCurrency('USD');

$transaction = new \PayPal\Api\Transaction();
$transaction->setAmount($amount);
$transaction->setDescription("Suscripción {$plan['nombre']} - CursosPro");

$redirectUrls = new \PayPal\Api\RedirectUrls();
$redirectUrls->setReturnUrl($_ENV['APP_URL']."/Backend/confirmar_pago.php?success=true")
    ->setCancelUrl($_ENV['APP_URL']."/Backend/confirmar_pago.php?success=false");

$payment = new \PayPal\Api\Payment();
$payment->setIntent('sale')
    ->setPayer($payer)
    ->setTransactions([$transaction])
    ->setRedirectUrls($redirectUrls);

try {
    $payment->create($apiContext);
    
    // Guardar transacción temporal en sesión
    $_SESSION['pago_pendiente'] = [
        'id_payment' => $payment->getId(),
        'id_plan' => $id_plan,
        'monto' => $plan['precio'],
        'tipo' => 'suscripcion'
    ];
    
    // Redirigir a PayPal para el pago
    header("Location: " . $payment->getApprovalLink());
    exit();
    
} catch (\PayPal\Exception\PayPalConnectionException $ex) {
    error_log("Error PayPal: " . $ex->getData());
    header("Location: elegir_plan.php?error=paypal");
    exit();
}