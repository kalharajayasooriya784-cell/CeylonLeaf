<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_items = $_POST['selected_items'] ?? [];

if (empty($selected_items)) {
    header("Location: cart.php?payment=error&msg=No+items+selected");
    exit();
}

$selected_ids = array_map('intval', $selected_items);
$placeholders = implode(',', array_fill(0, count($selected_ids), '?'));

// 1. Cart එකෙන් Select කරපු බඩු Details ගන්නවා
$sql = "SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name AS product_name, p.price 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? AND c.id IN ($placeholders)";

$stmt = $conn->prepare($sql);
$types = 'i' . str_repeat('i', count($selected_ids));
$params = array_merge([$user_id], $selected_ids);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$subtotal = 0;
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $subtotal += $row['price'] * $row['quantity'];
}
$stmt->close();

if (empty($items)) {
    header("Location: cart.php?payment=error&msg=Items+not+found");
    exit();
}

$vat = $subtotal * 0.10;
$grand_total = $subtotal + $vat;

// 2. Customerගේ විස්තර ගන්නවා
$user_stmt = $conn->prepare("SELECT full_name, email, telephone, address FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// 3. Unique Bill Number එකක් හදාගන්නවා (Duplicate error එක මඟහරවා ගැනීමට)
$bill_number = "INV-" . time() . "-" . mt_rand(100, 999);

// 4. Orders Table එකට Insert කරනවා (bill_number එකත් සමඟ)
$order_stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, bill_number) VALUES (?, ?, ?)");
$order_stmt->bind_param("ids", $user_id, $grand_total, $bill_number);

if ($order_stmt->execute()) {
    $order_id = $order_stmt->insert_id;
    $order_stmt->close();

    $email_items_list = "";

    // 5. order_items වලට Save කරලා, Cart එකෙන් ඒවා මකලා දානවා
    foreach ($items as $item) {
        $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_name, quantity, price) VALUES (?, ?, ?, ?)");
        $item_stmt->bind_param("isid", $order_id, $item['product_name'], $item['quantity'], $item['price']);
        $item_stmt->execute();
        $item_stmt->close();

        // Cart එකෙන් Item එක ඩිලීට් කරනවා
        $del_stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $del_stmt->bind_param("ii", $item['cart_id'], $user_id);
        $del_stmt->execute();
        $del_stmt->close();

        $item_subtotal = number_format($item['price'] * $item['quantity'], 2);
        $email_items_list .= "<tr>
            <td style='padding:8px; border:1px solid #ddd;'>{$item['product_name']}</td>
            <td style='padding:8px; border:1px solid #ddd;'>{$item['quantity']}</td>
            <td style='padding:8px; border:1px solid #ddd;'>\${$item['price']}</td>
            <td style='padding:8px; border:1px solid #ddd;'>\${$item_subtotal}</td>
        </tr>";
    }

    // 6. OWNER (ඔයාට) EMAIL එක යවනවා
    $owner_email = "your_email@gmail.com"; // මෙතනට ඔයාගේ ඇත්තම Email එක දාන්න
    $subject = "New Order Received - #" . $bill_number;
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
      <h2>New Order Placed! ({$bill_number})</h2>
      <h3>Customer Details:</h3>
      <p>
        <strong>Name:</strong> {$user_data['full_name']}<br>
        <strong>Email:</strong> {$user_data['email']}<br>
        <strong>Phone:</strong> {$user_data['telephone']}<br>
        <strong>Address:</strong> {$user_data['address']}
      </p>
      <h3>Purchased Items:</h3>
      <table style='width:100%; border-collapse:collapse;' border='1'>
        <tr style='background:#f4f4f4;'>
            <th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th>
        </tr>
        {$email_items_list}
      </table>
      <p><strong>Total Amount Paid (incl. 10% VAT): \$" . number_format($grand_total, 2) . "</strong></p>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: orders@ceylonleaf.com\r\n";

    @mail($owner_email, $subject, $message, $headers);

    // 7. Cart එකට සාර්ථකව Redirect කරනවා
    header("Location: cart.php?payment=success&order_id=" . $order_id);
    exit();
} else {
    header("Location: cart.php?payment=error&msg=Database+Error");
    exit();
}
?>