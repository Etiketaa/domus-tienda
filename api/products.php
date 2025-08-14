<?php
require_once '../config.php';

// Function to output JSON and exit
function output_json($data, $statusCode = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Function to generate a single product card HTML
function render_product_card($product) {
    $is_ap_brand = (!empty($product['brand']) && strtolower($product['brand']) == 'ap');
    $image_url = !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'assets/images/placeholder.png';
    $product_name = htmlspecialchars($product['name']);
    $product_brand = htmlspecialchars($product['brand'] ?? '');

    $html = "<div class='product-card' data-brand='" . strtolower($product_brand) . "'>";
    $html .= "<img src='{$image_url}' alt='{$product_name}' class='img-fluid' loading='lazy'>";
    $html .= "<h3>{$product_name}</h3>";
    
    if ($product_brand) {
        $html .= "<p class='brand'>{$product_brand}</p>";
    }

    if ($is_ap_brand) {
        $html .= "<p class='price-ap'>Precio a consultar</p>";
    } else {
        $html .= "<p class='price'>$" . number_format($product['price'], 2) . "</p>";
    }

    $html .= "<button class='details-btn' data-id='{$product['id']}'>Detalles</button>";

    if ($product['stock'] > 0) {
        $html .= "<button class='add-to-cart-btn' data-id='{$product['id']}'>Añadir al Carrito</button>";
    } else {
        $html .= "<p class='out-of-stock'>Sin stock</p>";
    }
    $html .= "</div>";

    return $html;
}

// --- Main Logic ---
try {
    // Handle single product request
    if (isset($_GET['id'])) {
        $productId = intval($_GET['id']);
        if ($productId <= 0) {
            output_json(['success' => false, 'message' => 'ID de producto inválido.'], 400);
        }

        $stmt = $conn->prepare("SELECT p.id, p.name, p.description, p.price, p.stock, c.name as category, b.name as brand, p.image_url FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id WHERE p.id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($product = $result->fetch_assoc()) {
            // Fetch additional images
            $img_stmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
            $img_stmt->bind_param("i", $productId);
            $img_stmt->execute();
            $img_result = $img_stmt->get_result();
            $product['additional_images'] = $img_result->fetch_all(MYSQLI_ASSOC);
            $img_stmt->close();

            output_json($product);
        } else {
            output_json(['success' => false, 'message' => 'Producto no encontrado.'], 404);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    // Handle product grid request
    header('Content-Type: text/html; charset=utf-8');

    $sql = "SELECT p.id, p.name, p.description, p.price, p.stock, c.name as category, b.name as brand, p.image_url FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id";
    $whereClauses = [];
    $params = [];
    $types = '';

    if (!empty($_GET['category']) && $_GET['category'] !== 'all') {
        $whereClauses[] = "p.category_id = ?";
        $params[] = intval($_GET['category']);
        $types .= "i";
    }

    if (!empty($_GET['search'])) {
        $searchTerm = "%" . $_GET['search'] . "%";
        $whereClauses[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }

    $sql .= " ORDER BY p.name";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo render_product_card($row);
        }
    } else {
        echo "<p>No se encontraron productos que coincidan con la búsqueda.</p>";
    }

    $stmt->close();

} catch (Exception $e) {
    // Log error and show a generic message
    error_log($e->getMessage());
    http_response_code(500);
    // Don't output HTML error if JSON was requested
    if (isset($_GET['id'])) {
         output_json(['success' => false, 'message' => 'Error interno del servidor.'], 500);
    }
    echo "<p>Ocurrió un error al procesar la solicitud. Por favor, intente de nuevo más tarde.</p>";
}

$conn->close();
?>