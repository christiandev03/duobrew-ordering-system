<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include '../db.php';

// Initialize variables
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : "";
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : "";

// Prepare SQL query with optional filtering
$query = "SELECT id, customer_name, total_price, status, created_at FROM orders";
$params = [];

if (!empty($startDate) && !empty($endDate)) {
    $query .= " WHERE DATE(created_at) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}

$query .= " ORDER BY created_at DESC";

// Execute the query using prepared statements
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param("ss", ...$params);
}
$stmt->execute();
$orderResults = $stmt->get_result();

// Insert sales data into the sales_report table
if (!empty($startDate) && !empty($endDate)) {
    $salesInsertStmt = $conn->prepare("
        INSERT INTO sales_report (order_id, total_sales, report_date)
        SELECT id, total_price, DATE(created_at)
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        ON DUPLICATE KEY UPDATE total_sales = VALUES(total_sales), report_date = VALUES(report_date)
    ");
    if ($salesInsertStmt) {
        $salesInsertStmt->bind_param("ss", $startDate, $endDate);
        $salesInsertStmt->execute();
        $salesInsertStmt->close();
    } else {
        echo "<script>alert('Failed to prepare sales report insertion.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Reports | Ka-Brew Admin</title>
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
        .filter-form {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .filter-form label {
            margin-right: 10px;
        }
        .filter-form input[type="date"] {
            padding: 5px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .filter-form button {
            background-color: #B57C4F;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .filter-form button:hover {
            background-color: #c69c6d;
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
        .action-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }
        .action-buttons button {
            background-color: #B57C4F;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-left: 10px;
        }
        .action-buttons button:hover {
            background-color: #c69c6d;
        }
    </style>
</head>
<body>
    <!-- Add this before your sidebar -->
    <div class="page-header">
        <h1>Order Reports</h1>
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
            <li><a href="transactions.php"><i class="fa-solid fa-money-bill-transfer"></i> <span>Transactions</span></a></li>
            <li><a href="reports.php" class="active"><i class="fa-solid fa-file-lines"></i> <span>Reports</span></a></li>
            <li><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <h2>Order Reports</h2>

        <!-- Filter Form -->
        <form method="GET" class="filter-form">
            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" value="<?php echo $startDate; ?>" required>
            
            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" value="<?php echo $endDate; ?>" required>
            
            <button type="submit">Filter</button>
        </form>

        <!-- Orders Table -->
        <table id="ordersTable" class="orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th>Order Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $orderResults->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td>₱<?php echo number_format($row['total_price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><?php echo date("F d, Y", strtotime($row['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

       <!-- Print & Export Buttons -->
       <div class="action-buttons">
            <button onclick="printReport()">🖨️ Print Report</button>
            <button onclick="exportToCSV()">📊 Export to CSV</button>
        </div>
    </div>

    <script>
        function printReport() {
            let table = document.getElementById("ordersTable").outerHTML;
            let newWindow = window.open("", "_blank");
            newWindow.document.write(`
                <html>
                <head>
                    <title>Ka-Brew Order Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid black; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                    </style>
                </head>
                <body>
                    <h2>Ka-Brew Order Report</h2>
                    ${table}
                    <script>
                        window.onload = function() {
                            window.print();
                            window.close();
                        };
                    <\/script>
                </body>
                </html>
            `);
            newWindow.document.close();
        }

        function exportToCSV() {
            let table = document.getElementById("ordersTable");
            let rows = table.querySelectorAll("tr");
            let csvContent = "\uFEFFOrder ID,Customer,Total Price,Status,Order Date\n";

            rows.forEach((row, index) => {
                if (index === 0) return; // Skip the header row
                let cols = row.querySelectorAll("td");
                let rowData = [];
                cols.forEach(col => {
                    let text = col.innerText;
                    rowData.push(`"${text}"`);
                });
                csvContent += rowData.join(",") + "\n";
            });

            let blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
            let url = URL.createObjectURL(blob);
            let a = document.createElement("a");
            a.href = url;
            a.download = "Duo-Brew_Order_Report.csv";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>
</html>