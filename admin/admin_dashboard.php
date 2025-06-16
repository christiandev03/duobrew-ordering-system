<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

// Fetch counts for orders, products, and revenue
$totalOrders = $conn->query("SELECT COUNT(*) AS count FROM orders")->fetch_assoc()['count'];
$totalProducts = $conn->query("SELECT COUNT(*) AS count FROM products")->fetch_assoc()['count'];
$totalRevenue = $conn->query("SELECT SUM(total_price) AS revenue FROM orders")->fetch_assoc()['revenue'];
$totalRevenue = $totalRevenue ? $totalRevenue : 0;

// Fetch recent orders
$recentOrders = $conn->query("SELECT id, customer_name, total_price, status FROM orders ORDER BY id DESC LIMIT 5");

// Fetch recent transactions (Only Cash & GCash)
$recentTransactions = $conn->query("SELECT transaction_id, type, amount, created_at FROM transactions WHERE type IN ('Cash', 'Gcash') ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            width: 50%;
            height: 250px;
        }
        .card i {
            font-size: 50px;
            margin-right: 10px;
        }
        .orders-table, .transactions-table {
            width: 80%;
            border-collapse: collapse;
            margin-bottom: 20px;
            margin-left: 150px;
        }
        .orders-table th, .transactions-table th, .orders-table td, .transactions-table td {
            padding: 8px;
            
        }
        .orders-table th, .transactions-table th {
            background-color: #B57C4F;
            text-align: left;
        }
        /* Status labels */
.status {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-block;
}

.pending {
    background-color:rgb(209, 189, 11);
    color: white;
}

.processing {
    background-color: rgb(252, 101, 0);
    color: white;
}

.completed {
    background-color: rgb(11, 117, 11);
    color: white;
}

.cancelled {
    background-color: red;
    color: white;
}
    </style>
</head>
<body>
    
    <!-- Add this before your sidebar -->
    <div class="page-header" style="text-align: center;">
        <h1>Admin Dashboard</h1>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="../images/duo_brew.png" alt="Duo Brew Logo" style="width: 95%;">
        </div>
        <ul>
            <li class="active"><a href="admin_dashboard.php"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li><a href="manage_orders.php"><i class="fa-solid fa-receipt"></i> <span>Manage Orders</span></a></li> 
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

            <div class="card">
                <i class="fa-solid fa-cube"></i> <!-- Changed icon -->
                <div>
                    <h3><?php echo $totalProducts; ?></h3>
                    <p>Products</p>
                </div>
            </div>

            <div class="card">
                <i class="fa-solid fa-wallet"></i> <!-- Changed icon -->
                <div>
                    <h3>₱<?php echo number_format($totalRevenue, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>

        <h3>Recent Orders</h3>
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total Price</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $recentOrders->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['customer_name']; ?></td>
                        <td>₱<?php echo number_format($row['total_price'], 2); ?></td>
                        <td><span class="status <?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3>Recent Transactions</h3>
        <table class="transactions-table">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $recentTransactions->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['transaction_id']; ?></td>
                        <td><?php echo ucfirst($row['type']); ?></td>
                        <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                        <td><?php echo date("F j, Y g:i A", strtotime($row['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>