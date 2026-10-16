<?php
session_start();
include 'db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $address = trim($_POST['address']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (!empty($full_name) && !empty($email) && !empty($telephone) && !empty($address) && !empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $error = "Passwords do not match!";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Email address is already registered!";
            } else {
                // Securely hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert user with telephone and address
                $insert_stmt = $conn->prepare("INSERT INTO users (full_name, email, telephone, address, password) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("sssss", $full_name, $email, $telephone, $address, $hashed_password);

                if ($insert_stmt->execute()) {
                    $success = "Account created successfully! <a href='login.php'>Click here to login</a>";
                } else {
                    $error = "Something went wrong. Please try again!";
                }
                $insert_stmt->close();
            }
            $stmt->close();
        }
    } else {
        $error = "Please fill in all required fields!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CeylonLeaf</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="auth-container">
        <div class="auth-card">
            <h2>Create Account</h2>
            <p class="auth-subtitle">Join CeylonLeaf today</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required placeholder="John Doe" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="example@mail.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="telephone">Telephone Number</label>
                    <input type="tel" id="telephone" name="telephone" required placeholder="+94 7X XXX XXXX" value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="address">Home Address</label>
                    <textarea id="address" name="address" rows="3" required placeholder="Street address, City, District"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Create a password">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                </div>

                <button type="submit" class="auth-btn">Register</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>

</html>