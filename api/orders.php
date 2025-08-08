<?php
require_once '../config.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and has the 'admin' role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requiere autenticación de administrador.']);
    exit();
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetOrders($conn);
        break;
    case 'PUT':
        handleUpdateOrder($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}

function handleGetOrders($conn) {
    $sql = "SELECT id, customer_name, customer_phone, customer_address, total, order_date, status FROM orders ORDER BY order_date DESC";
    $result = $conn->query($sql);

    $orders = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Fetch order items for each order
            $items_sql = "SELECT product_name, quantity, price FROM order_items WHERE order_id = ?";
            $items_stmt = $conn->prepare($items_sql);
            $items_stmt->bind_param("i", $row['id']);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            $items = [];
            while($item_row = $items_result->fetch_assoc()) {
                $items[] = $item_row;
            }
            $row['items'] = $items;
            $items_stmt->close();

            $orders[] = $row;
        }
    }
    echo json_encode($orders);
}

function handleUpdateOrder($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = $data['id'] ?? null;
    $newStatus = $data['status'] ?? null;

    if (!$orderId || !$newStatus) {
        http_response_code(400);
        echo json_encode(['message' => 'Order ID and new status are required.']);
        return;
    }

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $orderId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Order status updated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update order status.']);
    }
    $stmt->close();
}

$conn->close();
?>