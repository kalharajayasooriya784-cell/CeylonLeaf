<?php
// Prevent any PHP notices/warnings from breaking the JSON response
error_reporting(0);
ini_set('display_errors', 0);

session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in to add items to your cart.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product or quantity.']);
    exit();
}

// Check if item already exists in user's cart
$stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $new_qty = $row['quantity'] + $quantity;
    
    $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $new_qty, $row['id']);
    if ($update_stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Cart updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update cart in database.']);
    }
    $update_stmt->close();
} else {
    $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
    if ($insert_stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Item added to cart!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to insert item into cart.']);
    }
    $insert_stmt->close();
}

$stmt->close();
$conn->close();
?>