<?php
require_once 'config.php';

// URL base del sitio web (¡reemplazar con la URL final!)
$baseUrl = 'URL_DE_TU_SITIO_WEB';

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// 1. Página de inicio
echo '<url>';
echo '<loc>' . $baseUrl . '/index.php</loc>';
echo '<priority>1.0</priority>';
echo '<changefreq>daily</changefreq>';
echo '</url>';

// 2. Páginas de productos
$sql = "SELECT id, updated_at FROM products ORDER BY id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo '<url>';
        echo '<loc>' . $baseUrl . '/product.php?id=' . $row['id'] . '</loc>';
        // Si tienes una columna 'updated_at' en tu tabla de productos, úsala.
        if (!empty($row['updated_at'])) {
            echo '<lastmod>' . date('Y-m-d', strtotime($row['updated_at'])) . '</lastmod>';
        }
        echo '<changefreq>weekly</changefreq>';
        echo '<priority>0.8</priority>';
        echo '</url>';
    }
}

echo '</urlset>';

$conn->close();
?>
