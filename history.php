<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch User Info
$u_stmt = $conn->prepare("SELECT full_name, email, telephone, address FROM users WHERE id = ?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$user_info = $u_stmt->get_result()->fetch_assoc();
$u_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buying History - CeylonLeaf</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .invoice-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .invoice-card {
            background: #fff;
            color: #222;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            position: relative;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        }

        .close-inv {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #888;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="cart-container">
        <h1 class="cart-title">Buying History</h1>

        <?php
        $orders_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($orders_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $orders_result = $stmt->get_result();

        if ($orders_result->num_rows > 0):
        ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Bill ID</th>
                        <th>Date & Time</th>
                        <th>Total Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#INV-<?php echo $order['id']; ?></strong></td>
                            <td><?php echo $order['created_at']; ?></td>
                            <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            <td>
                                <button type="button" class="btn-edit" onclick="openInvoice(<?php echo $order['id']; ?>)">View Invoice</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-cart">
                <p>No purchase history found.</p>
                <a href="index.php#category" class="auth-btn" style="display:inline-block; width:auto; padding:10px 20px; text-decoration:none;">Shop Now</a>
            </div>
        <?php endif;
        $stmt->close();
        ?>
    </div>

    <!-- Invoice Modal -->
    <div id="invoiceModal" class="invoice-modal">
        <div class="invoice-card">
            <span class="close-inv" onclick="closeInvoice()">&times;</span>
            <h2 style="color:#2c5e1a; margin-bottom:10px;">CeylonLeaf Official Invoice</h2>
            <hr>
            <div id="invoiceContent" style="margin-top:15px;">
                <p>Loading details...</p>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
    <script>
        function openInvoice(orderId) {
            document.getElementById('invoiceModal').style.display = 'flex';
            fetch('get_invoice.php?order_id=' + orderId)
                .then(res => res.text())
                .then(data => {
                    document.getElementById('invoiceContent').innerHTML = data;
                });
        }

        function closeInvoice() {
            document.getElementById('invoiceModal').style.display = 'none';
        }
    </script>
</body>

</html>