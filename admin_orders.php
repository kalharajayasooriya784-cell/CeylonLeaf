<?php
session_start();
include 'db_connect.php';

// Check if owner/admin is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id_to_update = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    $allowed_statuses = ['Not Packed', 'Packed', 'Delivered'];
    if (in_array($new_status, $allowed_statuses)) {
        $up_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $up_stmt->bind_param("si", $new_status, $order_id_to_update);
        $up_stmt->execute();
        $up_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders View - CeylonLeaf</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .admin-title {
            color: #2c5e1a;
            margin-bottom: 25px;
            font-size: 26px;
        }
        .order-card {
            border: 1px solid #e1e4e8;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .order-header {
            background: #f4f6f8;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e1e4e8;
            flex-wrap: wrap;
            gap: 10px;
        }
        .order-body {
            padding: 20px;
        }
        .customer-info-box {
            background: #fafbfc;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            border-left: 4px solid #2c5e1a;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .items-table th, .items-table td {
            padding: 10px;
            border: 1px solid #eee;
            text-align: left;
            font-size: 14px;
        }
        .items-table th {
            background: #2c5e1a;
            color: #fff;
        }
        .status-select {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: bold;
            border: 1px solid #ccc;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="admin-container">
        <h1 class="admin-title">📦 Customer Orders Management (Owner Panel)</h1>

        <?php
        // Fetch all orders with customer details and status
        $sql = "SELECT o.id AS order_id, o.total_amount, o.created_at, o.status, 
                       u.full_name, u.email, u.telephone, u.address 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC";
        
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0):
            while ($order = $result->fetch_assoc()):
        ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <strong>Bill No: #INV-<?php echo $order['order_id']; ?></strong>
                        <span style="color: #666; margin-left: 15px; font-size: 13px;">📅 <?php echo $order['created_at']; ?></span>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <strong style="color: #2c5e1a; font-size: 16px;">Total: $<?php echo number_format($order['total_amount'], 2); ?></strong>
                        
                        <!-- Status Update Form -->
                        <form action="admin_orders.php" method="POST">
                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                            <input type="hidden" name="update_status" value="1">
                            <select name="status" class="status-select" onchange="this.form.submit()">
                                <option value="Not Packed" <?php if ($order['status'] === 'Not Packed') echo 'selected'; ?>>📦 Not Packed</option>
                                <option value="Packed" <?php if ($order['status'] === 'Packed') echo 'selected'; ?>>📋 Packed</option>
                                <option value="Delivered" <?php if ($order['status'] === 'Delivered') echo 'selected'; ?>>🚚 Delivered</option>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="order-body">
                    <!-- Customer Details -->
                    <div class="customer-info-box">
                        <strong>Customer Details:</strong><br>
                        👤 Name: <?php echo htmlspecialchars($order['full_name']); ?> | 
                        📧 Email: <?php echo htmlspecialchars($order['email']); ?> | 
                        📞 Phone: <?php echo htmlspecialchars($order['telephone']); ?><br>
                        📍 Address: <?php echo htmlspecialchars($order['address']); ?>
                    </div>

                    <!-- Ordered Items Table -->
                    <strong>Purchased Items:</strong>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $order_id = $order['order_id'];
                            $items_sql = "SELECT product_name, quantity, price FROM order_items WHERE order_id = ?";
                            $stmt = $conn->prepare($items_sql);
                            $stmt->bind_param("i", $order_id);
                            $stmt->execute();
                            $items_res = $stmt->get_result();

                            while ($item = $items_res->fetch_assoc()):
                                $subtotal = $item['price'] * $item['quantity'];
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>$<?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                            <?php 
                            endwhile; 
                            $stmt->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php 
            endwhile;
        else:
        ?>
            <div class="empty-cart">
                <p>No orders placed yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>