<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include '../db.php';

// Get logged-in user's username
$username = $_SESSION['username'];

// Let's first get the column names from the orders table to ensure we use the correct field names
$columns_query = "SHOW COLUMNS FROM orders";
$columns_result = $conn->query($columns_query);
$columns = [];
while($col = $columns_result->fetch_assoc()) {
    $columns[] = $col['Field'];
}

// Determine the date field name - common variations
$date_field = 'created_at'; // Default assumption
if(in_array('order_date', $columns)) {
    $date_field = 'order_date';
} elseif(in_array('date', $columns)) {
    $date_field = 'date';
} elseif(in_array('timestamp', $columns)) {
    $date_field = 'timestamp';
}

// Determine the total amount field name
$amount_field = 'total_price'; // Default assumption
if(!in_array('total_price', $columns)) {
    if(in_array('amount', $columns)) {
        $amount_field = 'amount';
    } elseif(in_array('total', $columns)) {
        $amount_field = 'total';
    } elseif(in_array('price', $columns)) {
        $amount_field = 'price';
    }
}

// Query to get orders for the current user, ordered by most recent first
$query = "SELECT id, $date_field AS order_date, $amount_field AS total_price, 
          payment_method, status 
          FROM orders 
          WHERE customer_name = ? 
          ORDER BY $date_field DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - DUO BREW</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f4f2ea;
        }
        .header {
            background-color: #B57C4F;
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
        }
        .logo {
    height: 80px;
    display: flex;
    align-items: center;
        }
.logo img {
    height: 200%;
}
        .header-icons {
    display: flex;
    gap: 5px; /* Set the gap to 5px */
    margin-left: auto;
}
.header-icon {
    font-size: 24px;
    color: white;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    font-size: 14px;
    margin-right: 20px; /* Add margin to the right */
}
.header-icon i {
    font-size: 24px;
    margin-bottom: 5px;
}
        .main-container {
            flex: 1;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        .page-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }
        .orders-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .no-orders {
            text-align: center;
            padding: 40px;
            color: #777;
        }
        .order-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .order-id {
            font-weight: bold;
        }
        .order-date {
            color: #777;
        }
        .order-details {
            display: flex;
            justify-content: space-between;
        }
        .order-total {
            font-weight: bold;
            color: #B57C4F;
        }
        .order-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-processing {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .view-details-btn {
            background-color: #B57C4F;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
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
    
    <div class="main-container">
        <div class="page-title">Your Order History</div>
        
        <div class="orders-container">
            <?php if ($result->num_rows == 0): ?>
                <div class="no-orders">You haven't placed any orders yet.</div>
            <?php else: ?>
                <?php while ($order = $result->fetch_assoc()): ?>
                    <div class="order-item">
                        <div class="order-header">
                            <div class="order-id">Order #<?php echo $order['id']; ?></div>
                            <div class="order-date"><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></div>
                        </div>
                        <div class="order-details">
                            <div>
                                <div>Payment Method: <?php echo $order['payment_method'] ?? 'N/A'; ?></div>
                                <div class="order-total">Total: Php <?php echo number_format($order['total_price'] ?? 0, 2); ?></div>
                            </div>
                            <div>
                                <?php
                                $statusClass = '';
                                $status = $order['status'] ?? 'N/A';
                                switch($status) {
                                    case 'Completed':
                                        $statusClass = 'status-completed';
                                        break;
                                    case 'Processing':
                                        $statusClass = 'status-processing';
                                        break;
                                    case 'Cancelled':
                                        $statusClass = 'status-cancelled';
                                        break;
                                    default:
                                        $statusClass = '';
                                }
                                ?>
                                <span class="order-status <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                            </div>
                        </div>
                        <button class="view-details-btn" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">View Details</button>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function viewOrderDetails(orderId) {
            // Redirect to the correct order details page
            window.location.href = 'order_details.php?order_id=' + orderId;
        }
    </script>
</body>
</html>