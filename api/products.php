<?php
require_once '../config.php';

header('Content-Type: text/html; charset=utf-8');

// Si se solicita un ID de producto, devolver JSON
if (isset($_GET['id'])) {
    $productId = intval($_GET['id']);
    $sql = "SELECT id, name, description, price, stock, category, brand, image_url FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($product);
    } else {
        header("HTTP/1.0 404 Not Found");
        echo json_encode(['error' => 'Producto no encontrado']);
    }
    $stmt->close();
    $conn->close();
    exit; // Terminar el script aquí
}

// Lógica para mostrar la cuadrícula de productos
$category = isset($_GET['category']) ? $_GET['category'] : null;
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT * FROM products";
$whereClauses = [];
$params = [];
$types = '';

if ($category && $category !== 'all') {
    $whereClauses[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($searchTerm)) {
    $whereClauses[] = "name LIKE ? OR description LIKE ?";
    $params[] = "%" . $searchTerm . "%";
    $params[] = "%" . $searchTerm . "%";
    $types .= "ss";
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(' AND ', $whereClauses);
}

$sql .= " ORDER BY name";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $is_ap_brand = (!empty($row['brand']) && strtolower($row['brand']) == 'ap');

        echo "<div class='product-card' data-brand='" . htmlspecialchars(strtolower($row['brand'] ?? '')) . "'>";
        if (!empty($row['image_url'])) {
            echo "<img src='" . htmlspecialchars($row['image_url']) . "' alt='" . htmlspecialchars($row['name']) . "' loading='lazy'>";
        } else {
            echo "<img src='assets/images/placeholder.png' alt='Imagen no disponible para " . htmlspecialchars($row['name']) . "' loading='lazy'>";
        }
        echo "<h3>" . htmlspecialchars($row['name']) . "</h3>";
        if (!empty($row['brand'])) {
            echo "<p class='brand'>" . htmlspecialchars($row['brand']) . "</p>";
        }

        // Lógica de precio y botón
        if ($is_ap_brand) {
            echo "<p class='price-ap'>Precio a consultar</p>";
        } else {
            echo "<p class='price'>$" . number_format($row['price'], 2) . "</p>";
        }

        echo "<button class='details-btn' data-id='" . $row['id'] . "'>Detalles</button>";

        if ($row['stock'] > 0) {
            echo "<button class='add-to-cart-btn' data-id='" . $row['id'] . "'>Añadir al Carrito</button>";
        } else {
            echo "<p class='out-of-stock'>Sin stock</p>";
        }
        echo "</div>";
    }
} else {
    echo "<p>No se encontraron productos que coincidan con la búsqueda.</p>";
}

$stmt->close();
$conn->close();
?>