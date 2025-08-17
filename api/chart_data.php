<?php
require_once '../config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Seguridad: Solo permitir a administradores logueados
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

// --- Consultas a la Base de Datos ---

// Top 5 Productos Más Vendidos
$top_products_query = "SELECT p.name, SUM(oi.quantity) as total_sold FROM order_items oi JOIN products p ON oi.product_id = p.id GROUP BY oi.product_id ORDER BY total_sold DESC LIMIT 5";
$top_products_result = $conn->query($top_products_query);

// Productos con Bajo Stock
$low_stock_query = "SELECT name, stock FROM products WHERE stock > 0 AND stock < 5 ORDER BY stock ASC";
$low_stock_result = $conn->query($low_stock_query);

// Top 5 Productos Más Vistos
$top_viewed_query = "SELECT p.name, COUNT(pi.id) as total_views FROM product_interactions pi JOIN products p ON pi.product_id = p.id WHERE pi.interaction_type = 'view' GROUP BY pi.product_id ORDER BY total_views DESC LIMIT 5";
$top_viewed_result = $conn->query($top_viewed_query);

// Top 5 Productos con Más Interacción
$top_interacted_query = "SELECT p.name, COUNT(pi.id) as total_interactions FROM product_interactions pi JOIN products p ON pi.product_id = p.id WHERE pi.interaction_type IN ('detail_click', 'add_to_cart_click') GROUP BY pi.product_id ORDER BY total_interactions DESC LIMIT 5";
$top_interacted_result = $conn->query($top_interacted_query);


// --- Procesar y Estructurar Datos ---

function process_query_result($result) {
    $labels = [];
    $data = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $labels[] = $row['name'];
            $data[] = array_values($row)[1]; // Obtener el segundo valor (total_sold, stock, etc.)
        }
    }
    return ['labels' => $labels, 'data' => $data];
}

$chartData = [
    'topSold' => process_query_result($top_products_result),
    'lowStock' => process_query_result($low_stock_result),
    'topViewed' => process_query_result($top_viewed_result),
    'topInteracted' => process_query_result($top_interacted_result)
];

// --- Enviar Respuesta ---

header('Content-Type: application/json');
echo json_encode($chartData);

$conn->close();
?>