<?php

// Set up error and exception handling to always return JSON
set_exception_handler(function ($exception) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unhandled Exception: ' . $exception->getMessage(),
        'code' => $exception->getCode(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine()
    ]);
    exit();
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error: ' . $message,
        'severity' => $severity,
        'file' => $file,
        'line' => $line
    ]);
    exit();
});

require_once '../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: Datos JSON inválidos recibidos.']);
    exit();
}

$product_id = filter_var($data['product_id'] ?? null, FILTER_VALIDATE_INT);
$interaction_type = htmlspecialchars(trim($data['interaction_type'] ?? ''), ENT_QUOTES, 'UTF-8');

if (empty($product_id) || $product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de producto inválido.']);
    exit();
}

if (empty($interaction_type)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de interacción no especificado.']);
    exit();
}

// Opcional: Validar tipos de interacción permitidos
$allowed_interaction_types = ['view', 'detail_click', 'add_to_cart_click'];
if (!in_array($interaction_type, $allowed_interaction_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de interacción no permitido.']);
    exit();
}

try {
    $stmt = $conn->prepare("INSERT INTO product_interactions (product_id, interaction_type) VALUES (?, ?)");
    $stmt->bind_param("is", $product_id, $interaction_type);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Interacción registrada con éxito.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al registrar la interacción: ' . $stmt->error]);
    }
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} finally {
    $conn->close();
}

?>