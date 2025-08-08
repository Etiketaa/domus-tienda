<?php
require_once '../config.php';

header('Content-Type: application/json');

$sql = "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand";
$result = $conn->query($sql);

$brands = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $brands[] = $row;
    }
}

echo json_encode($brands);

$conn->close();
?>