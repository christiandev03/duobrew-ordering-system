<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include '../db.php';

// Fetch all products
$products = $conn->query("SELECT * FROM products ORDER BY id DESC");

// Fetch best-selling product (Iced Caramel Macchiato)
$best_seller = $conn->query("SELECT * FROM products WHERE name = 'Iced Caramel Macchiato' LIMIT 1");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
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
        .add-btn {
            background-color: #B57C4F;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-bottom: 20px;
            margin-left: 1250px;
        }
        .add-btn:hover {
            background-color: #c69c6d;
        }
        .products-table img {
            width: 70px; /* Increase the size of the image in the table */
        }
        .best-seller-card img {
            width: 150px; /* Decrease the size of the image in the best seller section */
        }
        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-right: 5px;
        }
        .edit-btn {
            background-color: #4CAF50;
            color: white;
        }
        .edit-btn:hover {
            background-color: #45a049;
        }
        .delete-btn {
            background-color: #f44336;
            color: white;
        }
        .delete-btn:hover {
            background-color: #da190b;
        }
        .price {
            color: red;
        }
        p {
            position: relative;
            top: 10px;
            margin-left: 50px;
        }
    </style>
</head>
<body>
    <!-- Add this before your sidebar -->
    <div class="page-header">
        <h1>Manage Products</h1>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="../images/duo_brew.png" alt="Duo Brew Logo" style="width: 95%;">
        </div>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li><a href="manage_orders.php"><i class="fa-solid fa-receipt"></i> <span>Manage Orders</span></a></li>
            <li><a href="manage_products.php" class="active"><i class="fa-solid fa-boxes-stacked"></i> <span>Manage Products</span></a></li>
            <li><a href="transactions.php"><i class="fa-solid fa-money-bill-transfer"></i> <span>Transactions</span></a></li>
            <li><a href="reports.php"><i class="fa-solid fa-file-lines"></i> <span>Reports</span></a></li>
            <li><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <button class="add-btn" onclick="window.location.href='add_product.php'">Add Product</button>

        <!-- Products Table -->
        <table class="products-table">
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Size</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $products->fetch_assoc()): ?>
                    <tr id="product-<?php echo $row['id']; ?>">
                        <td><?php echo $row['id']; ?></td>
                        <td><img src="uploads/<?php echo $row['image']; ?>" alt="Product Image"></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['category']; ?></td>
                        <td><?php echo $row['description']; ?></td>
                        <td><?php echo $row['size']; ?></td>
                        <td>₱<?php echo number_format($row['price'], 2); ?></td>
                        <td>
                            <button class="edit-btn" onclick="editProduct(<?php echo $row['id']; ?>)">
                                <i class="fa-solid fa-pen"></i> Edit
                            </button>
                            <button class="delete-btn" onclick="deleteProduct(<?php echo $row['id']; ?>)">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Best Seller Section -->
        <h3>Best Seller</h3>
        <div class="best-sellers">  
            <?php if ($row = $best_seller->fetch_assoc()): ?>
                <div class="best-seller-card">
                    <img src="uploads/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>">
                    <p class="price">₱<?php echo number_format($row['price'], 2); ?></p>
                    <h3><?php echo $row['name']; ?></h3>
                   
                </div>      
            <?php endif; ?>
        </div>
    </div>

    <script>
        function editProduct(productId) {
            window.location.href = "edit_product.php?id=" + productId;  
        }

        function deleteProduct(productId) {
            if (confirm("Are you sure you want to delete this product?")) {
                $.ajax({
                    url: "delete_product.php",
                    type: "POST",
                    data: { product_id: productId },
                    success: function(response) {
                        if (response.trim() === "success") {
                            $("#product-" + productId).remove();
                            alert("Product deleted successfully.");
                        } else {
                            alert("Error: " + response);
                        }
                    },
                    error: function() {
                        alert("An error occurred while deleting the product.");
                    }
                });
            }
        }
    </script>
</body>
</html>