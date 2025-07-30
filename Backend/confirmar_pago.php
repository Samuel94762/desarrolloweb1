<?php
session_start();
require_once 'database.php';
require __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['pago_pendiente']) || !isset($_GET['success']) || !isset($_GET['paymentId']) || !isset($_GET['PayerID'])) {
    header("Location: elegir_plan.php");
    exit();
}

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

$paymentId = $_GET['paymentId'];
$payerId = $_GET['PayerID'];
$success = $_GET['success'] === 'true';

try {
    if ($success) {
        $payment = \PayPal\Api\Payment::get($paymentId, $apiContext);
        
        $execution = new \PayPal\Api\PaymentExecution();
        $execution->setPayerId($payerId);
        
        $result = $payment->execute($execution, $apiContext);
        
        // Crear suscripción
        $fecha_inicio = date('Y-m-d H:i:s');
        $fecha_fin = date('Y-m-d H:i:s', strtotime('+1 month'));
        
        $stmt = $conn->prepare("INSERT INTO suscripciones (idusuario, idplan, fecha_inicio, fecha_fin, estado) 
                              VALUES (:idusuario, :idplan, :fecha_inicio, :fecha_fin, 'activa')");
        $stmt->bindParam(':idusuario', $_SESSION['idusuario']);
        $stmt->bindParam(':idplan', $_SESSION['pago_pendiente']['id_plan']);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);
        $stmt->execute();
        $id_suscripcion = $conn->lastInsertId();
        
        // Registrar transacción
        $stmt = $conn->prepare("INSERT INTO transacciones_paypal 
                              (idusuario, tipo, idsuscripcion, id_paypal, estado, monto, datos_completos) 
                              VALUES (:idusuario, 'suscripcion', :idsuscripcion, :id_paypal, 'completado', :monto, :datos)");
        $stmt->bindParam(':idusuario', $_SESSION['idusuario']);
        $stmt->bindParam(':idsuscripcion', $id_suscripcion);
        $stmt->bindParam(':id_paypal', $paymentId);
        $stmt->bindParam(':monto', $_SESSION['pago_pendiente']['monto']);
        $stmt->bindValue(':datos', json_encode($result));
        $stmt->execute();
        
        // Limpiar sesión
        unset($_SESSION['pago_pendiente']);
        
        header("Location: home.php?success=suscripcion");
    } else {
        // Pago cancelado
        $stmt = $conn->prepare("INSERT INTO transacciones_paypal 
                              (idusuario, tipo, id_paypal, estado, monto) 
                              VALUES (:idusuario, 'suscripcion', :id_paypal, 'cancelado', :monto)");
        $stmt->bindParam(':idusuario', $_SESSION['idusuario']);
        $stmt->bindParam(':id_paypal', $paymentId);
        $stmt->bindParam(':monto', $_SESSION['pago_pendiente']['monto']);
        $stmt->execute();
        
        unset($_SESSION['pago_pendiente']);
        header("Location: elegir_plan.php?error=pago_cancelado");
    }
    exit();
    
} catch (Exception $e) {
    error_log("Error al confirmar pago: " . $e->getMessage());
    
    // Registrar transacción fallida
    $stmt = $conn->prepare("INSERT INTO transacciones_paypal 
                          (idusuario, tipo, id_paypal, estado, monto, datos_completos) 
                          VALUES (:idusuario, 'suscripcion', :id_paypal, 'fallido', :monto, :datos)");
    $stmt->bindParam(':idusuario', $_SESSION['idusuario']);
    $stmt->bindParam(':id_paypal', $paymentId);
    $stmt->bindParam(':monto', $_SESSION['pago_pendiente']['monto']);
    $stmt->bindValue(':datos', $e->getMessage());
    $stmt->execute();
    
    unset($_SESSION['pago_pendiente']);
    header("Location: elegir_plan.php?error=pago_fallido");
    exit();
}