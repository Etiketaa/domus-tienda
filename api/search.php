<?php
header('Content-Type: application/json');
include '../config.php';

$searchTerm = htmlspecialchars(trim($_GET['term'] ?? ''));

if (empty($searchTerm)) {
    echo json_encode([]);
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

$searchTermForQuery = '%' . $conn->real_escape_string($searchTerm) . '%';

$sql = "SELECT id, name, description, price, stock, image_url FROM products WHERE name LIKE ? OR description LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $searchTermForQuery, $searchTermForQuery);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);

$stmt->close();
$conn->close();
?>