<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include '../db.php';

// Fetch all transactions (only Cash and GCash)
$transactions = $conn->query("SELECT transaction_id, type, amount, created_at FROM transactions WHERE type IN ('Cash', 'GCash') ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
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
        .transactions-table {
            width: 80%;
            border-collapse: collapse;
            margin-bottom: 20px;
            margin-left: 150px;
        }
        .transactions-table th, .transactions-table td {
            padding: 8px;
        }
        .transactions-table th {
            background-color: #B57C4F;
            text-align: left;
        }
    </style>
</head>
<body>
    <!-- Add this before your sidebar -->
    <div class="page-header">
        <h1>Transactions</h1>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="../images/duo_brew.png" alt="Duo Brew Logo" style="width: 95%;">
        </div>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li><a href="manage_orders.php"><i class="fa-solid fa-receipt"></i> <span>Manage Orders</span></a></li>
            <li><a href="manage_products.php"><i class="fa-solid fa-boxes-stacked"></i> <span>Manage Products</span></a></li>
            <li><a href="transactions.php" class="active"><i class="fa-solid fa-money-bill-transfer"></i> <span>Transactions</span></a></li>
            <li><a href="reports.php"><i class="fa-solid fa-file-lines"></i> <span>Reports</span></a></li>
            <li><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
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
                <?php while ($row = $transactions->fetch_assoc()): ?>
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