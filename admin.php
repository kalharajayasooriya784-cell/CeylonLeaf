<?php
session_start();
include 'db_connect.php';

// Optional: You can check if the user is an admin or logged in here
// if (!isset($_SESSION['logged_in'])) { header("Location: login.php"); exit(); }

$message = "";
$error = "";

// ==========================================
// 1. HANDLE ADD PRODUCT (ADD TEA)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);
    
    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            // Generate unique filename to avoid overwriting existing files
            $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadFileDir = 'uploads/';
            $dest_path = $uploadFileDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO products (name, image_url, price, description) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssds", $name, $dest_path, $price, $description);
                
                if ($stmt->execute()) {
                    $message = "Tea product added successfully!";
                } else {
                    $error = "Database insertion failed.";
                }
                $stmt->close();
            } else {
                $error = "Error moving uploaded file to destination directory.";
            }
        } else {
            $error = "Upload failed. Allowed image formats: jpg, jpeg, png, webp.";
        }
    } else {
        $error = "Please upload a product image.";
    }
}

// ==========================================
// 2. HANDLE DELETE PRODUCT
// ==========================================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get current image path to remove it from server
    $stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $imagePath = $row['image_url'];
        // Remove file if it exists in uploads folder
        if (file_exists($imagePath) && strpos($imagePath, 'uploads/') === 0) {
            unlink($imagePath);
        }
    }
    $stmt->close();

    // Delete row from database
    $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $delete_stmt->bind_param("i", $id);
    if ($delete_stmt->execute()) {
        $message = "Product deleted successfully!";
    } else {
        $error = "Failed to delete product.";
    }
    $delete_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CeylonLeaf</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="admin-container">
        <h1 class="admin-title">Admin Dashboard</h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="admin-grid">
            <!-- SECTION 1: ADD TEA FORM -->
            <div class="admin-card">
                <h2>Add Tea Product</h2>
                <form action="admin.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Tea Name</label>
                        <input type="text" id="name" name="name" required placeholder="e.g., Ceylon Silver Tips">
                    </div>

                    <div class="form-group">
                        <label for="image">Upload Image</label>
                        <input type="file" id="image" name="image" accept="image/*" required>
                    </div>

                    <div class="form-group">
                        <label for="price">Price ($)</label>
                        <input type="number" step="0.01" id="price" name="price" required placeholder="15.00">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" required placeholder="Write details about taste, origin, etc."></textarea>
                    </div>

                    <button type="submit" name="add_product" class="auth-btn">Add Product</button>
                </form>
            </div>

            <!-- SECTION 2: EDIT / DELETE PRODUCT LIST -->
            <div class="admin-card">
                <h2>Manage Existing Products</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM products ORDER BY id DESC";
                            $products = $conn->query($sql);

                            if ($products->num_rows > 0) {
                                while($row = $products->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td><img src='".htmlspecialchars($row['image_url'])."' class='admin-thumb' alt='product'></td>";
                                    echo "<td><strong>".htmlspecialchars($row['name'])."</strong></td>";
                                    echo "<td>$".number_format($row['price'], 2)."</td>";
                                    echo "<td class='action-cell'>
                                            <a href='edit_product.php?id=".$row['id']."' class='btn-edit'>Edit</a>
                                            <a href='admin.php?delete=".$row['id']."' class='btn-delete' onclick='return confirm(\"Are you sure you want to delete this product?\");'>Delete</a>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>No products found in database.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>