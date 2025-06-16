<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include '../db.php';

// Get logged-in employee's username
$username = $_SESSION['username'];

// Initialize bill if not exists
if (!isset($_SESSION['bill'])) {
    $_SESSION['bill'] = array();
}

// Handle Add to Billing
if (isset($_POST['add_to_billing'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    $size = $_POST['size'];
    $sugar = $_POST['sugar'];
    $quantity = 1;

    // Adjust price based on size
    if ($size === 'S') {
        $product_price = 35;
    } elseif ($size === 'M') {
        $product_price = 45;
    }

    $item_key = $product_id . '-' . $size . '-' . $sugar;

    if (isset($_SESSION['bill'][$item_key])) {
        $_SESSION['bill'][$item_key]['quantity'] += $quantity;
    } else {
        $_SESSION['bill'][$item_key] = array(
            'product_id' => $product_id,
            'name' => $product_name,
            'price' => $product_price,
            'image' => $product_image,
            'size' => $size,
            'sugar' => $sugar,
            'quantity' => $quantity
        );
    }
}

// Handle remove from bill
if (isset($_POST['remove_item'])) {
    $item_key = $_POST['item_key'];
    if (isset($_SESSION['bill'][$item_key])) {
        unset($_SESSION['bill'][$item_key]);
    }
}

// Clear bill
if (isset($_POST['clear_bill'])) {
    $_SESSION['bill'] = array();
}

// Process payment
if (isset($_POST['print_bill'])) {
    $payment_method = $_POST['payment_method'];
    $total_price = 0;
    $order_details = array();

    // Calculate total price and prepare order details
    foreach ($_SESSION['bill'] as $item) {
        $total_price += $item['price'] * $item['quantity'];
        $order_details[] = "x{$item['quantity']} {$item['name']} (Size: {$item['size']}, Sugar: {$item['sugar']})";
    }

    // Convert order details to a string
    $order_details_string = implode(", ", $order_details);

    // Insert order into the database
    $stmt = $conn->prepare("INSERT INTO orders (customer_name, total_price, payment_method, status, created_at, order_details) VALUES (?, ?, ?, ?, NOW(), ?)");
    $status = 'Pending';
    $stmt->bind_param("sdsss", $username, $total_price, $payment_method, $status, $order_details_string);

    if ($stmt->execute()) {
        $order_id = $conn->insert_id; // Get the auto-incremented ID for the order

        // Insert transaction into the database
        $transaction_stmt = $conn->prepare("INSERT INTO transactions (type, amount, created_at) VALUES (?, ?, NOW())");
        $transaction_stmt->bind_param("sd", $payment_method, $total_price);
        $transaction_stmt->execute();
        $transaction_id = $conn->insert_id; // Get the auto-incremented ID for the transaction
        $transaction_stmt->close();

        // Generate transaction ID and update the database
        $transaction_date = date("Ymd");
        $transaction_id_formatted = "TXN-{$transaction_date}-" . str_pad($transaction_id, 5, "0", STR_PAD_LEFT);

        $update_transaction_stmt = $conn->prepare("UPDATE transactions SET transaction_id = ? WHERE id = ?");
        $update_transaction_stmt->bind_param("si", $transaction_id_formatted, $transaction_id);
        $update_transaction_stmt->execute();
        $update_transaction_stmt->close();

        // Insert sales data into the sales_report table
        $sales_stmt = $conn->prepare("INSERT INTO sales_report (order_id, total_sales, report_date) VALUES (?, ?, NOW())");
        $sales_stmt->bind_param("id", $order_id, $total_price);
        $sales_stmt->execute();
        $sales_stmt->close();

        // Clear the bill after saving the order
        $_SESSION['bill'] = array();

        if ($payment_method === 'gcash') {
            // Use modal for GCash payment
        } else {
            // Redirect or show success message for cash payment
            echo "<script>alert('Order placed successfully! Transaction ID: {$transaction_id_formatted}'); window.location.href = 'user_dashboard.php';</script>";
        }
    } else {
        echo "<script>alert('Failed to place the order. Please try again.');</script>";
    }

    $stmt->close();
}

// Fetch user's order history
$order_history = $conn->prepare("SELECT * FROM orders WHERE customer_name = ? ORDER BY created_at DESC");
$order_history->bind_param("s", $username);
$order_history->execute();
$order_history_result = $order_history->get_result();
?>
<?php
// Fetch product categories
$categories = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");

// Default category - can be changed based on selection
$current_category = isset($_GET['category']) ? $_GET['category'] : 'All';

// Fetch products for the current category or search query
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
if (!empty($search_query)) {
    $products = $conn->query("SELECT * FROM products WHERE name LIKE '%$search_query%' ORDER BY name");
} elseif ($current_category == 'All') {
    $products = $conn->query("SELECT * FROM products ORDER BY name");
} else {
    $products = $conn->query("SELECT * FROM products WHERE category = '$current_category' ORDER BY name");
}
?>

<script>
    // Handle payment method selection
    function selectPaymentMethod(method) {
        const cashMethod = document.getElementById('cash-method');
        const gcashMethod = document.getElementById('gcash-method');
        const paymentMethodInput = document.getElementById('payment-method-input');

        if (method === 'cash') {
            cashMethod.classList.add('selected');
            gcashMethod.classList.remove('selected');
            paymentMethodInput.value = 'cash';
        } else if (method === 'gcash') {
            gcashMethod.classList.add('selected');
            cashMethod.classList.remove('selected');
            paymentMethodInput.value = 'gcash';
        }
    }
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DUO BREW USER</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="user.css">
    <link rel="stylesheet" href="modal.css">
</head>
<body>
    <div class="header">
        <div class="logo">
            <img src="../images/duo_brew.png" alt="Duo Brew Logo">
        </div>
        <div class="header-icons">
            <a href="user_dashboard.php" class="header-icon">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="order_history.php" class="header-icon">
                <i class="fas fa-history"></i>
                <span>History</span>
            </a>
            <a href="../logout.php" class="header-icon">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <div class="main-container">
        <div class="left-panel">
            <div class="search-container">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Search" value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="category-selection">
                <div class="category-title">Choose Category</div>
                <div class="category-buttons">
                    <a href="?category=All" class="category-button <?php echo ($current_category == 'All') ? 'active' : ''; ?>">
                        <img src="../images/all.png" alt="All">
                        <span>All</span>
                    </a>
                    <a href="?category=Coffee Based" class="category-button <?php echo ($current_category == 'Coffee Based') ? 'active' : ''; ?>">
                        <img src="../images/coffee.png" alt="Coffee Based">
                        <span>Coffee Based</span>
                    </a>
                    <a href="?category=Non Coffee Based" class="category-button <?php echo ($current_category == 'Non Coffee Based') ? 'active' : ''; ?>">
                        <img src="../images/non_coffee.png" alt="Non Coffee Based">
                        <span>Non Coffee Based</span>
                    </a>
                    <a href="?category=Fruit Tea" class="category-button <?php echo ($current_category == 'Fruit Tea') ? 'active' : ''; ?>">
                        <img src="../images/fruit_tea.png" alt="Fruit Tea">
                        <span>Fruit Tea</span>
                    </a>
                </div>
            </div>
            
            <div class="menu-title"><?php echo $current_category; ?> Menu</div>
            
            <div class="product-grid">
                <?php while ($product = $products->fetch_assoc()): ?>
                <div class="product-card">
                    <div class="product-content">
                        <img src="../admin/uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        <div class="product-details">
                            <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-description"><?php echo htmlspecialchars($product['description'] ?? 'No description available'); ?></div>
                            <div class="product-price" data-base-price="<?php echo $product['price']; ?>">Php <?php echo number_format($product['price'], 2); ?></div>
                        </div>
                    </div>
                    
                    <div class="product-options">
                        <form method="POST" action="" id="product-form-<?php echo $product['id']; ?>">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                            <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                            <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($product['image']); ?>">
                            <input type="hidden" name="size" value="M" id="size-input-<?php echo $product['id']; ?>">
                            <input type="hidden" name="sugar" value="50%" id="sugar-input-<?php echo $product['id']; ?>">
                        
                            <div class="option-label">Size</div>
                            <div class="option-buttons size-options" data-product-id="<?php echo $product['id']; ?>">
                                <div class="option-button" data-size="S">S</div>
                                <div class="option-button" data-size="M">M</div>
                                <div class="option-button selected" data-size="L">L</div>
                            </div>
                            
                            <div class="option-label">Sugar</div>
                            <div class="option-buttons sugar-options" data-product-id="<?php echo $product['id']; ?>">
                                <div class="option-button" data-sugar="Sugar Free">Sugar Free</div>
                                <div class="option-button" data-sugar="20%">20%</div>
                                <div class="option-button selected" data-sugar="50%">50%</div>
                                <div class="option-button" data-sugar="70%">70%</div>
                                <div class="option-button" data-sugar="100%">100%</div>
                            </div>
                            
                            <button type="submit" name="add_to_billing" class="add-button">Add to Billing</button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <div class="right-panel">
            <div class="bill-title">Bills</div>
            
            <div class="bill-items">
                <?php 
                $subtotal = 0;
                foreach ($_SESSION['bill'] as $key => $item): 
                    $subtotal += $item['price'] * $item['quantity'];
                ?>
                <div class="bill-item">
                    <img src="../admin/uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="bill-item-image">
                    <div class="bill-item-details">
                        <div class="bill-item-title"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div>Size: <?php echo $item['size']; ?>, Sugar: <?php echo $item['sugar']; ?></div>
                        <div class="bill-item-price">Php <?php echo number_format($item['price'], 2); ?></div>
                    </div>
                    <div class="bill-item-quantity"><?php echo $item['quantity']; ?></div>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="item_key" value="<?php echo $key; ?>">
                        <button type="submit" name="remove_item" class="bill-item-remove">×</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="bill-summary">
                <div class="bill-row">
                    <div>Subtotal</div>
                    <div>Php <?php echo number_format($subtotal, 2); ?></div>
                </div>
                <div class="bill-row bill-total">
                    <div>Total</div>
                    <div>Php <?php echo number_format($subtotal, 2); ?></div>
                </div>
            </div>
            
            <div class="payment-methods">
                <div class="payment-method selected" id="cash-method" onclick="selectPaymentMethod('cash')">
                    <i class="fas fa-money-bill"></i>
                    <div>Cash</div>
                </div>
                <div class="payment-method" id="gcash-method" onclick="selectPaymentMethod('gcash')">
                    <i class="fas fa-mobile-alt"></i>
                    <div>GCash</div>
                </div>
            </div>

            <form method="POST" id="payment-form">
                <input type="hidden" name="payment_method" id="payment-method-input" value="cash">
                <button type="submit" name="print_bill" class="print-bill-button">PRINT BILL</button>
            </form>
        </div>
    </div>

    <?php if (isset($_POST['print_bill']) && $_POST['payment_method'] === 'gcash'): ?>
        <div id="gcash-modal" class="modal">
            <div class="modal-content">
                <span class="close-button" onclick="closeModal()">&times;</span>
                <h2>GCash Payment</h2>
                <img src="../images/gcash_qr.jpg" alt="GCash QR Code" class="gcash-image">
                <p>Scan the QR code to complete your payment.</p>
                <button onclick="confirmPayment()">Confirm Payment</button>
            </div>
        </div>
        <script>
            // Show the modal
            document.getElementById('gcash-modal').style.display = 'flex';

            // Close the modal
            function closeModal() {
                document.getElementById('gcash-modal').style.display = 'none';
            }

            // Confirm payment and clear the bill
            function confirmPayment() {
                alert('Payment confirmed. Thank you!');
                window.location.href = 'user_dashboard.php'; // Redirect to refresh the page
            }
        </script>
    <?php endif; ?>

    <script>
        // Handle size selection
        document.querySelectorAll('.size-options').forEach(container => {
            const productId = container.dataset.productId;
            const sizeInput = document.getElementById(`size-input-${productId}`);
            const priceElement = container.closest('.product-card').querySelector('.product-price');
            const basePrice = parseFloat(priceElement.dataset.basePrice);

            container.querySelectorAll('.option-button').forEach(button => {
                button.addEventListener('click', function() {
                    // Remove selected class from all buttons in this container
                    container.querySelectorAll('.option-button').forEach(btn => {
                        btn.classList.remove('selected');
                    });

                    // Add selected class to the clicked button
                    button.classList.add('selected');

                    // Update the hidden input value
                    sizeInput.value = button.dataset.size;

                    // Update the price based on size
                    let newPrice = basePrice;
                    if (button.dataset.size === 'S') {
                        newPrice = 35;
                    } else if (button.dataset.size === 'M') {
                        newPrice = 45;
                    }
                    priceElement.textContent = `Php ${newPrice.toFixed(2)}`;
                });
            });
        });

        // Handle sugar selection
        document.querySelectorAll('.sugar-options').forEach(container => {
            const productId = container.dataset.productId;
            const sugarInput = document.getElementById(`sugar-input-${productId}`);
            
            container.querySelectorAll('.option-button').forEach(button => {
                button.addEventListener('click', function() {
                    // Remove selected class from all buttons in this container
                    container.querySelectorAll('.option-button').forEach(btn => {
                        btn.classList.remove('selected');
                    });
                    
                    // Add selected class to the clicked button
                    button.classList.add('selected');
                    
                    // Update the hidden input value
                    sugarInput.value = button.dataset.sugar;
                });
            });
        });

        // Submit product form
        function submitProductForm(productId) {
            document.getElementById(`product-form-${productId}`).submit();
        }
    </script>
</body>
</html>