<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_GET['order_id'])) {
    exit('Unauthorized access');
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

// Order eka fetch karagneema
$o_stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$o_stmt->bind_param("ii", $order_id, $user_id);
$o_stmt->execute();
$order = $o_stmt->get_result()->fetch_assoc();
$o_stmt->close();

if (!$order) {
    exit('Invoice not found.');
}

// User ge details fetch karagneema
$u_stmt = $conn->prepare("SELECT full_name, email, telephone, address FROM users WHERE id = ?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$user = $u_stmt->get_result()->fetch_assoc();
$u_stmt->close();

// Order items (gaththa badu) fetch karagneema
$i_stmt = $conn->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = ?");
$i_stmt->bind_param("i", $order_id);
$i_stmt->execute();
$items_result = $i_stmt->get_result();
?>

<div style="font-size:14px; line-height:1.6; color:#333;">
    <p><strong>Invoice No:</strong> #INV-<?php echo $order['id']; ?><br>
    <strong>Date & Time:</strong> <?php echo $order['created_at']; ?></p>
    
    <div style="background:#f4f4f4; padding:10px; border-radius:6px; margin:10px 0;">
        <strong>Customer Details:</strong><br>
        Name: <?php echo htmlspecialchars($user['full_name']); ?><br>
        Phone: <?php echo htmlspecialchars($user['telephone']); ?><br>
        Address: <?php echo htmlspecialchars($user['address']); ?>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-top:10px;" border="1">
        <thead>
            <tr style="background:#2c5e1a; color:#fff; text-align:left;">
                <th style="padding:8px;">Item Name</th>
                <th style="padding:8px;">Qty</th>
                <th style="padding:8px;">Price</th>
                <th style="padding:8px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            while($item = $items_result->fetch_assoc()): 
                $sub = $item['price'] * $item['quantity'];
            ?>
                <tr>
                    <td style="padding:8px;"><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td style="padding:8px;"><?php echo $item['quantity']; ?></td>
                    <td style="padding:8px;">$<?php echo number_format($item['price'], 2); ?></td>
                    <td style="padding:8px;">$<?php echo number_format($sub, 2); ?></td>
                </tr>
            <?php 
            endwhile; 
            $i_stmt->close(); 
            ?>
        </tbody>
    </table>

    <div style="text-align:right; margin-top:15px; font-size:16px;">
        <strong>Total Amount (incl. VAT): <span style="color:#2c5e1a;">$<?php echo number_format($order['total_amount'], 2); ?></span></strong>
    </div>
</div>