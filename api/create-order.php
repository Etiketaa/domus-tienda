<?php

// Set up error and exception handling to always return JSON
set_exception_handler(function ($exception) {
    error_log('Unhandled Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ocurrió un error inesperado. Por favor, intente nuevamente más tarde.'
    ]);
    exit();
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    error_log('PHP Error: ' . $message . ' in ' . $file . ' on line ' . $line);
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ocurrió un error inesperado. Por favor, intente nuevamente más tarde.'
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

// 1. Sanitizar y validar datos del cliente
$customer_name = htmlspecialchars(trim($data['customer']['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$customer_phone = htmlspecialchars(trim($data['customer']['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
$customer_address = htmlspecialchars(trim($data['customer']['address'] ?? ''), ENT_QUOTES, 'UTF-8');

if (empty($customer_name) || empty($customer_phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El nombre y el teléfono del cliente son requeridos.']);
    exit();
}

$items_from_frontend = $data['items'] ?? [];
if (empty($items_from_frontend)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El carrito está vacío.']);
    exit();
}

// 2. Procesar ítems y determinar el estado del pedido
$product_ids = array_column($items_from_frontend, 'id');
if (empty($product_ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se encontraron IDs de productos válidos.']);
    exit();
}

$placeholders = implode(',', array_fill(0, count($product_ids), '?'));
$types = str_repeat('i', count($product_ids));

$stmt_products = $conn->prepare("SELECT id, name, price, stock, brand FROM products WHERE id IN ($placeholders)");
$stmt_products->bind_param($types, ...$product_ids);
$stmt_products->execute();
$result_products = $stmt_products->get_result();

$db_products = [];
while ($row = $result_products->fetch_assoc()) {
    $db_products[$row['id']] = $row;
}
$stmt_products->close();

$calculated_total = 0;
$processed_items = [];
$contains_ap_brand = false;

foreach ($items_from_frontend as $item) {
    $product_id = filter_var($item['id'] ?? 0, FILTER_VALIDATE_INT);
    $quantity = filter_var($item['quantity'] ?? 0, FILTER_VALIDATE_INT);

    if ($product_id <= 0 || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "ID de producto o cantidad inválida: id=${product_id}, q=${quantity}."]);
        exit();
    }

    if (!isset($db_products[$product_id])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Producto con ID ' . $product_id . ' no encontrado.']);
        exit();
    }

    $db_product = $db_products[$product_id];

    if ($db_product['stock'] < $quantity) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Stock insuficiente para ' . htmlspecialchars($db_product['name']) . '. Disponible: ' . $db_product['stock'] . '.']);
        exit();
    }

    $is_ap = strtolower($db_product['brand'] ?? '') === 'ap';
    if ($is_ap) {
        $contains_ap_brand = true;
    }

    $item_price = $is_ap ? 0 : (float)$db_product['price'];
    $item_name = htmlspecialchars($db_product['name'], ENT_QUOTES, 'UTF-8');

    $processed_items[] = [
        'product_id' => $product_id,
        'product_name' => $item_name,
        'price' => $item_price,
        'quantity' => $quantity
    ];
    
    if (!$is_ap) {
        $calculated_total += ($item_price * $quantity);
    }
}

$order_status = $contains_ap_brand ? 'cotizacion_pendiente' : 'pendiente';

// 3. Iniciar transacción y guardar en la base de datos
$conn->begin_transaction();

try {
    // Insertar el pedido principal
    $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_phone, customer_address, total, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssids", $customer_name, $customer_phone, $customer_address, $calculated_total, $order_status);
    $stmt->execute();
    $order_id = $conn->insert_id;
    $stmt->close();

    // Insertar los ítems del pedido y actualizar stock
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
    $stmt_update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($processed_items as $item) {
        // Actualizar stock
        $stmt_update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
        $stmt_update_stock->execute();

        // Insertar ítem
        $stmt_item->bind_param("iisdi", $order_id, $item['product_id'], $item['product_name'], $item['price'], $item['quantity']);
        $stmt_item->execute();
    }

    $stmt_item->close();
    $stmt_update_stock->close();

    $conn->commit();

    $success_message = $contains_ap_brand 
        ? "Solicitud de cotización recibida. Nos pondremos en contacto contigo para finalizar la compra."
        : "¡Pedido realizado con éxito!";

    echo json_encode(['success' => true, 'message' => $success_message, 'order_id' => $order_id]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al crear el pedido: ' . $e->getMessage()]);
} finally {
    $conn->close();
}