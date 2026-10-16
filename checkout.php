<?php
session_start();
include 'db_connect.php';

// Check login status
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Redirect if no items selected
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['selected_items'])) {
    header("Location: cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Valued Customer';
$selected_cart_ids = $_POST['selected_items'];

// Fetch selected items to calculate total on server side
$placeholders = implode(',', array_fill(0, count($selected_cart_ids), '?'));
$sql = "SELECT c.id AS cart_id, c.quantity, p.name, p.price, p.id AS product_id 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? AND c.id IN ($placeholders)";

$stmt = $conn->prepare($sql);
$types = 'i' . str_repeat('i', count($selected_cart_ids));
$params = array_merge([$user_id], $selected_cart_ids);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$subtotal = 0;
$item_count = 0;
while ($row = $result->fetch_assoc()) {
    $subtotal += $row['price'] * $row['quantity'];
    $item_count += $row['quantity'];
}
$stmt->close();

$vat = $subtotal * 0.10;
$grand_total = $subtotal + $vat;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout - CeylonLeaf</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style1.css">
</head>

<body class="checkout-body">

    <div class="checkout-modal-container">
        <!-- Header Bar -->
        <div class="checkout-header">
            <div class="checkout-brand">
                <span class="bank-badge">COMMERCIAL BANK SECURE GATEWAY</span>
                <span class="gateway-lock" style="margin-left: 10px;">🔒 256-bit SSL Encrypted</span>
            </div>
            <div class="checkout-timer" id="timer">10 : 00</div>
            <a href="cart.php" class="close-modal">&times;</a>
        </div>

        <div class="checkout-grid">
            <!-- Left Column: Payment Form -->
            <div class="payment-form-section">
                <div class="section-heading">
                    <h3>Card Details</h3>
                    <a href="cart.php" class="edit-link">Edit Order</a>
                </div>
                <p class="form-hint">Enter your payment information to authorize the order</p>

                <form action="process_payment.php" method="POST">
                    <!-- Selected Cart Item Hidden Inputs -->
                    <?php foreach ($selected_cart_ids as $cart_id): ?>
                        <input type="hidden" name="selected_items[]" value="<?php echo htmlspecialchars($cart_id); ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="grand_total" value="<?php echo $grand_total; ?>">

                    <div class="form-group-custom">
                        <label for="cardholder">Cardholder Name</label>
                        <input type="text" id="cardholder" name="cardholder" required placeholder="Name on card" value="<?php echo htmlspecialchars($user_name); ?>">
                    </div>

                    <div class="card-input-box">
                        <span class="card-brand-logo">💳</span>
                        <input type="text" id="cardnumber" name="cardnumber" required maxlength="19" placeholder="4356 - 7890 - 1234 - 5678">
                        <span class="checked-icon">✔</span>
                    </div>

                    <div class="form-row-dual">
                        <div class="form-group-custom">
                            <label for="expiry">Expiry Date (MM/YY)</label>
                            <input type="text" id="expiry" name="expiry" required placeholder="10/26" maxlength="5">
                        </div>
                        <div class="form-group-custom">
                            <label for="cvv">CVV / CVC</label>
                            <input type="password" id="cvv" name="cvv" required placeholder="446" maxlength="4">
                        </div>
                    </div>

                    <button type="submit" class="pay-btn">Authorize & Pay $<?php echo number_format($grand_total, 2); ?></button>
                </form>

                <div style="text-align: center; margin-top: 15px;">
                    <a href="cart.php" style="color: #666; text-decoration: none; font-size: 14px;">← Cancel and return to cart</a>
                </div>
            </div>

            <!-- Right Column: Virtual Card & Summary -->
            <div class="receipt-section">
                <!-- Virtual Card Graphic -->
                <div class="virtual-card">
                    <div class="vc-top">
                        <span class="vc-chip">■■■</span>
                        <span class="vc-wifi">🛜</span>
                    </div>
                    <div class="vc-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="vc-number">•••• •••• •••• 3456</div>
                    <div class="vc-footer">
                        <span>10/26</span>
                        <span class="vc-mastercard">🔴🟡</span>
                    </div>
                </div>

                <!-- Order Details Breakdown -->
                <div class="receipt-details">
                    <div class="receipt-line">
                        <span>Company</span>
                        <strong>CeylonLeaf Tea Co.</strong>
                    </div>
                    <div class="receipt-line">
                        <span>Total Items</span>
                        <strong><?php echo $item_count; ?> Items</strong>
                    </div>
                    <div class="receipt-line">
                        <span>Subtotal</span>
                        <strong>$<?php echo number_format($subtotal, 2); ?></strong>
                    </div>
                    <div class="receipt-line">
                        <span>VAT (10%)</span>
                        <strong>$<?php echo number_format($vat, 2); ?></strong>
                    </div>
                </div>

                <div class="receipt-total-box">
                    <div class="receipt-line total-pay">
                        <span>You have to Pay</span>
                        <span class="total-price">$<?php echo number_format($grand_total, 2); ?> USD</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 10-minute countdown timer
        let timeLeft = 600;
        const timerEl = document.getElementById('timer');
        setInterval(() => {
            if (timeLeft > 0) {
                timeLeft--;
                let mins = Math.floor(timeLeft / 60).toString().padStart(2, '0');
                let secs = (timeLeft % 60).toString().padStart(2, '0');
                if (timerEl) timerEl.innerText = `${mins} : ${secs}`;
            }
        }, 1000);
    </script>
</body>

</html>