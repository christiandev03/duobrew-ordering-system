<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

// Fetch total orders count
$totalOrders = $conn->query("SELECT COUNT(*) AS count FROM orders")->fetch_assoc()['count'];

// Fetch all orders
$orders = [];
$ordersQuery = $conn->query("SELECT * FROM orders ORDER BY id DESC");
if ($ordersQuery) {
    while ($row = $ordersQuery->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            display: flex;
            flex-direction: column;
            height: 100vh;
            margin: 0;
        }
        .page-header {
            background-color: #B57C4F;
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.329);
            border-bottom: 1px solid #000000;
            z-index: 1000;
        }
        .sidebar {
            width: 250px;
            background-color: #B57C4F;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 20px;
            z-index: 1000;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar a {
            display: block;
            padding: 15px 20px;
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .sidebar a:hover {
            background-color: #c69c6d;
        }
        .sidebar i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .active {
            background-color: #866948;
        }
        .content {
            margin-left: 250px;
            padding: 25px;
            padding-top: 90px; /* Adjust for header */
            overflow-y: auto;
            flex: 1;
        }
        .dashboard-overview {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }
        .card {
            margin: 10px;
            padding: 40px; /* Increase padding to make the card bigger */
            border: 1px solid #ccc;
            border-radius: 10px; /* Increase border-radius for a more rounded look */
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 300px; /* Increase width to make the card bigger */
        }
        .delete-btn {
            background-color: red;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .delete-btn:hover {
            background-color: darkred;
        }
        .orders-table {
            width: 80%;
            border-collapse: collapse;
            margin-bottom: 20px;
            margin-left: 150px;
        }
        .orders-table th, .orders-table td {
            
            padding: 8px;
        }
        .orders-table th {
            background-color: #B57C4F;
            text-align: left;
        }
    </style>
</head>
<body>
    
    <!-- Add this before your sidebar -->
    <div class="page-header" style="text-align: center;">
        <h1>Manage Orders</h1>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="../images/duo_brew.png" alt="Duo Brew Logo" style="width: 95%;">
        </div>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li><a href="manage_orders.php" class="active"><i class="fa-solid fa-receipt"></i> <span>Manage Orders</span></a></li> 
            <li><a href="manage_products.php"><i class="fa-solid fa-boxes-stacked"></i> <span>Manage Products</span></a></li>
            <li><a href="transactions.php"><i class="fa-solid fa-money-bill-transfer"></i> <span>Transactions</span></a></li>
            <li><a href="reports.php"><i class="fa-solid fa-file-lines"></i> <span>Reports</span></a></li>
            <li><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li> 
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="content">
        <div class="dashboard-overview">
            <div class="card">
                <i class="fa-solid fa-receipt"></i> <!-- Changed icon -->
                <div>
                    <h3><?php echo $totalOrders; ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <h3>Orders</h3>
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Order Details</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                        <tr id="order-<?php echo htmlspecialchars($order['id']); ?>">
                            <td><?php echo htmlspecialchars($order['id']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['order_details']); ?></td> 
                            <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                            <td>
                                <select class="order-status" data-order-id="<?php echo htmlspecialchars($order['id']); ?>">
                                    <option value="Pending" <?php echo ($order['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Processing" <?php echo ($order['status'] === 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                    <option value="Completed" <?php echo ($order['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo ($order['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </td>
                            <td>
                                <button class="delete-btn" onclick="deleteOrder(<?php echo htmlspecialchars($order['id']); ?>)">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- JavaScript -->
    <script>
        $(document).ready(function () {
            $(".order-status").change(function () {
                var orderId = $(this).data("order-id");
                var newStatus = $(this).val().trim();

                $.ajax({
                    url: "update_order_status.php",
                    type: "POST",
                    data: { order_id: orderId, status: newStatus },
                    success: function (response) {
                        console.log("Status updated: " + response);
                    }
                });
            });
        });

        function deleteOrder(orderId) {
            if (confirm("Are you sure you want to delete this order?")) {
                $.ajax({
                    url: "delete_order.php",
                    type: "POST",
                    data: { order_id: orderId },
                    success: function (response) {
                        $("#order-" + orderId).remove();
                        console.log("Order deleted: " + response);
                    }
                });
            }
        }
    </script>

</body>
</html>