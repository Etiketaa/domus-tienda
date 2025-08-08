<?php
include 'config.php';

$product = null;

if (isset($_GET['id'])) {
    $productId = $_GET['id'];

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    }

    $stmt->close();
    $conn->close();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? htmlspecialchars($product['name']) . ' - Doctor Cell' : 'Producto no encontrado - Doctor Cell'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <a href="index.php"><img src="./img/logo.png" alt="Doctor Cell Logo" class="header-logo"></a>
        <nav>
            <a href="dashboard.php">Dashboard</a>
        </nav>
    </header>

    <main class="product-detail-main">
        <?php if ($product): ?>
            <div class="product-detail-card">
                <div class="product-detail-image">
                    <?php if ($product['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <div class="no-image">No hay imagen disponible</div>
                    <?php endif; ?>
                </div>
                <div class="product-detail-info">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    <p class="product-detail-description"><?php echo htmlspecialchars($product['description']); ?></p>
                    <p class="product-detail-price">Precio: $<?php echo number_format($product['price'], 2); ?></p>
                    <p class="product-detail-stock">Stock: <?php echo htmlspecialchars($product['stock']); ?></p>
                    <?php if ($product['stock'] > 0): ?>
                        <button class="add-to-cart-btn" data-id="<?php echo $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo htmlspecialchars($product['price']); ?>">Agregar al Carrito</button>
                    <?php else: ?>
                        <p class="out-of-stock">Sin stock</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <p class="error-message">El producto solicitado no fue encontrado.</p>
        <?php endif; ?>
    </main>

    <script src="assets/js/main.js"></script>
    <footer>
        <p>&copy; 2024 Doctor Cell. Todos los derechos reservados.</p>
        <p>Desarrollado por </p><a href="https://www.linkedin.com/in/francoparedes1992/">Etiketaa Development</a>
    </footer>
</body>
</html>