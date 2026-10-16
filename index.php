<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CeylonLeaf - Premium Tea</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- Include Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero" id="hero">
        <div class="slider">
            <div class="slide active">
                <img src="images.jpg" alt="Tea Estate">
            </div>
            <div class="slide">
                <video src="tea.mp4" autoplay muted loop></video>
            </div>
            <div class="slide">
                <!-- Google Maps Embed -->
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d5696.961365679896!2d79.90817352263245!3d6.853444096127429!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae25bcb6233bbe3%3A0x83671e18f923713c!2sJ'pura%20Campus!5e0!3m2!1sen!2slk!4v1791353221081!5m2!1sen!2slk" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </section>

    <!-- Category Section -->
    <section class="category" id="category">
        <h2 class="section-title">Our Products</h2>
        <div class="product-grid">
            <?php
            // Fetch products from database
            $sql = "SELECT * FROM products ORDER BY id DESC";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
            ?>
                    <div class="product-card slide-up" data-description="<?php echo htmlspecialchars($row['description']); ?>">
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                        <p class="price">$<?php echo number_format($row['price'], 2); ?></p>

                        <!-- Action Area: Quantity and Add to Cart -->
                        <div class="action-area" onclick="event.stopPropagation();">
                            <div class="qty-control">
                                <button type="button" class="qty-btn minus">-</button>
                                <input type="number" class="qty-input" value="1" min="1">
                                <button type="button" class="qty-btn plus">+</button>
                            </div>
                            <!-- Direct onclick calling the global addToCart function -->
                            <button type="button" class="add-cart-btn" onclick="addToCart(<?php echo $row['id']; ?>, this)">Add to Cart</button>
                        </div>
                    </div>
                <?php
                endwhile;
            else:
                ?>
                <p>No products available at the moment.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- About Us Section -->
    <section class="about-us" id="aboutus">
        <h2 class="section-title">About Us</h2>
        <p>Welcome to CeylonLeaf. This journey began from a simple thought—a <strong>semis' idea forced to start it</strong>. We are dedicated to bringing you the finest, hand-picked tea leaves directly from the lush estates of our homeland to your cup. Quality and authenticity are the core of our business.</p>
    </section>

    <!-- Include Footer -->
    <?php include 'footer.php'; ?>

    <!-- Popup Modal for Description -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3 id="modalTitle"></h3>
            <p id="modalDescription"></p>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>