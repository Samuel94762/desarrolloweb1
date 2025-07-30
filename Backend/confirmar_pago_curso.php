<?php
session_start();
require_once 'database.php';
require __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['pago_pendiente_curso']) || !isset($_GET['success']) || !isset($_GET['paymentId']) || !isset($_GET['PayerID'])) {
    header("Location: home.php");
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
        
        // Calcular comisión y pago al profesor
        $comision = $_SESSION['pago_pendiente_curso']['descuento'] > 0 ? 10 : 30;
        $pago_profesor = $_SESSION['pago_pendiente_curso']['monto'] * (1 - $comision / 100);
        
        // Registrar compra
        $stmt = $conn->prepare("INSERT INTO compras 
                              (idusuario, idcurso, monto_pagado, descuento_aplicado, comision_plataforma, pago_profesor) 
                              VALUES (:idusuario, :idcurso, :monto, :descuento, :comision, :pago_profesor)");
        $stmt->bindParam(':idusuario', $_SESSION['idusuario']);
        $stmt->bindParam(':idcurso', $_SESSION['pago_pendiente_curso']['id_curso']);
        $stmt->bindParam(':monto', $_SESSION['pago_pendiente_curso']['monto']);
        $stmt->bindParam(':descuento', $_SESSION['pago_pendiente_curso']['descuento']);
        $stmt->bindParam(':comision', $comision);
        $stmt->bindParam(':pago_profesor', $pago_profesor);
        $stmt->execute();
        $id_compra = $conn->lastInsertId();
        
        // Registrar transacción
        $stmt = $conn->prepare("INSERT INTO transacciones_paypal 
                              (idusuario, tipo, idcompra, id_paypal, estado, monto, datos_completos) 
                              VALUES (:idusuario, 'compra_curso', :idcompra, :id_paypal, 'completado', :monto, :datos)");
        $stmt->bindParam(':idusuario', $_SESSION['idusuario']);
        $stmt->bindParam(':idcompra', $id_compra);
        $stmt->bindParam(':id_paypal', $paymentId);
        $stmt->bindParam(':monto', $_SESSION['pago_pendiente_curso']['monto']);
        $stmt->bindValue(':datos', json_encode($result));
        $stmt->execute();
        
        // Limpiar sesión
        unset($_SESSION['pago_pendiente_curso']);
        
        header("Location: ver_curso.php?id=" . $_SESSION['pago_pendiente_curso']['id_curso'] . "&success=compra");
    } else {
        // Pago cancelado
        $stmt = $conn->prepare("INSERT INTO transacciones_paypal 
                              (idusuario, tipo, id_paypal, estado, monto) 
                              VALUES (:idusuario, 'compra_curso', :id_paypal, 'cancelado', :monto)");
        $stmt->bindParam(':idusuario', $_SESSION['idusuario']);
        $stmt->bindParam(':id_paypal', $paymentId);
        $stmt->bindParam(':monto', $_SESSION['pago_pendiente_curso']['monto']);
        $stmt->execute();
        
        unset($_SESSION['pago_pendiente_curso']);
        header("Location: ver_curso.php?id=" . $_SESSION['pago_pendiente_curso']['id_curso'] . "&error=pago_cancelado");
    }
    exit();
    
} catch (Exception $e) {
    error_log("Error al confirmar pago: " . $e->getMessage());
    
    // Registrar transacción fallida
    $stmt = $conn->prepare("INSERT INTO transacciones_paypal 
                          (idusuario, tipo, id_paypal, estado, monto, datos_completos) 
                          VALUES (:idusuario, 'compra_curso', :id_paypal, 'fallido', :monto, :datos)");
    $stmt->bindParam(':idusuario', $_SESSION['idusuario']);
    $stmt->bindParam(':id_paypal', $paymentId);
    $stmt->bindParam(':monto', $_SESSION['pago_pendiente_curso']['monto']);
    $stmt->bindValue(':datos', $e->getMessage());
    $stmt->execute();
    
    unset($_SESSION['pago_pendiente_curso']);
    header("Location: ver_curso.php?id=" . $_SESSION['pago_pendiente_curso']['id_curso'] . "&error=pago_fallido");
    exit();
}