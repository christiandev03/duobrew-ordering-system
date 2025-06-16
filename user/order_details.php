<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include '../db.php';

// Get the order ID from the query string
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo "<script>alert('Invalid order ID.'); window.location.href = 'order_history.php';</script>";
    exit();
}

$order_id = intval($_GET['order_id']);
$username = $_SESSION['username'];

// Fetch the order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND customer_name = ?");
$stmt->bind_param("is", $order_id, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Order not found.'); window.location.href = 'order_history.php';</script>";
    exit();
}

$order = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - DUO BREW</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="user.css">
    <style>
        body {
            background-color: #f4f2ea;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        .order-details-container {
            max-width: 800px;
            margin: 40px auto;
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
            gap: 20px; /* Added space between order number and date */
        }
        .order-header h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .order-header div {
            font-size: 14px;
            color: #777;
        }
        .order-info {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .order-info div {
            margin-bottom: 10px;
            font-size: 16px;
        }
        .order-info strong {
            color: #333;
        }
        .order-items {
            margin-top: 20px;
        }
        .order-items h3 {
            margin-bottom: 15px;
            font-size: 20px;
            color: #333;
        }
        .order-items p {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
            white-space: pre-wrap;
        }
    </style>
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

    <div class="order-details-container">
        <div class="order-header">
            <h2>Order #<?php echo htmlspecialchars($order['id']); ?></h2>
            <div><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></div>
        </div>
        <div class="order-info">
            <div><strong>Customer Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></div>
            <div><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></div>
            <div><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></div>
            <div><strong>Total Price:</strong> Php <?php echo number_format($order['total_price'], 2); ?></div>
        </div>
        <div class="order-items">
            <h3>Order Details</h3>
            <p><?php echo nl2br(htmlspecialchars($order['order_details'])); ?></p>
        </div>
    </div>
</body>
</html>
