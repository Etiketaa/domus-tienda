<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("SELECT id, image_url FROM carousel_images ORDER BY order_index ASC, created_at ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    echo json_encode(['success' => true, 'images' => $images]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener las imágenes del carrusel.']);
}
?>