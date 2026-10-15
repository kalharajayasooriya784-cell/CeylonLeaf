<?php
session_start();
include 'db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: admin.php");
    exit();
}

$id = intval($_GET['id']);
$message = "";
$error = "";

// Fetch current product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: admin.php");
    exit();
}

// Handle Form Update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_product'])) {
    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);
    $image_url = $product['image_url']; // keep existing image by default

    // If new image uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
            $dest_path = 'uploads/' . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Delete old image if it was in uploads folder
                if (file_exists($product['image_url']) && strpos($product['image_url'], 'uploads/') === 0) {
                    unlink($product['image_url']);
                }
                $image_url = $dest_path;
            }
        } else {
            $error = "Invalid image file format!";
        }
    }

    if (empty($error)) {
        $update_stmt = $conn->prepare("UPDATE products SET name = ?, image_url = ?, price = ?, description = ? WHERE id = ?");
        $update_stmt->bind_param("ssdsi", $name, $image_url, $price, $description, $id);

        if ($update_stmt->execute()) {
            $message = "Product updated successfully!";
            // Refresh product data
            $product['name'] = $name;
            $product['image_url'] = $image_url;
            $product['price'] = $price;
            $product['description'] = $description;
        } else {
            $error = "Failed to update product.";
        }
        $update_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - CeylonLeaf Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="auth-container">
        <div class="auth-card" style="max-width: 550px;">
            <h2>Edit Product</h2>
            <p class="auth-subtitle">Modify details for: <?php echo htmlspecialchars($product['name']); ?></p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="edit_product.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Tea Name</label>
                    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($product['name']); ?>">
                </div>

                <div class="form-group">
                    <label>Current Image</label><br>
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="admin-thumb" style="width: 100px; height: 100px;" alt="Current Photo">
                </div>

                <div class="form-group">
                    <label for="image">Change Image (Optional)</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="price">Price ($)</label>
                    <input type="number" step="0.01" id="price" name="price" required value="<?php echo htmlspecialchars($product['price']); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <button type="submit" name="update_product" class="auth-btn">Save Changes</button>
                <a href="admin.php" style="display: block; margin-top: 15px; text-decoration: none; color: var(--text-color);">← Back to Admin Panel</a>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>