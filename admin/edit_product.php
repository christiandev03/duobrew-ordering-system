<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header("Location: manage_products.php");
    exit();
}

$product_id = $_GET['id'];

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: manage_products.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $size = $_POST['size'];
    $price = $_POST['price'];
    $image = $product['image']; // Keep the existing image if not changed

    // Check if a new image is uploaded
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/"; // Removed "../" since we're inside admin folder
        $image_name = time() . "_" . basename($_FILES["image"]["name"]); // Add timestamp to prevent overwriting
        $target_file = $target_dir . $image_name;

        // Move the uploaded file
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Delete the old image if it exists
            if (!empty($product['image']) && file_exists($target_dir . $product['image'])) {
                unlink($target_dir . $product['image']);
            }
            $image = $image_name; // Update the image filename
        }
    }

    // Update product in the database
    $update_stmt = $conn->prepare("UPDATE products SET name=?, category=?, description=?, size=?, price=?, image=? WHERE id=?");
    $update_stmt->bind_param("ssssdsi", $name, $category, $description, $size, $price, $image, $product_id);

    if ($update_stmt->execute()) {
        header("Location: manage_products.php?success=Product updated successfully");
        exit();
    } else {
        $error = "Error updating product.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .modal {
            display: flex;
            justify-content: center;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            width: 500px;
            max-width: 90%;
        }
        .modal-content h2 {
            margin-top: 0;
        }
        .modal-content label {
            display: block;
            margin-top: 10px;
        }
        .modal-content input, .modal-content textarea, .modal-content button {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .modal-content button {
            background-color: #B57C4F;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .modal-content button:hover {
            background-color: #c69c6d;
        }
    </style>
</head>
<body>

<div class="modal">
    <div class="modal-content">
        <h2>Edit Product</h2>

        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label for="name">Product Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>

            <label for="category">Category:</label>
            <input type="text" name="category" value="<?php echo htmlspecialchars($product['category']); ?>" required>

            <label for="description">Description:</label>
            <textarea name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>

            <label for="size">Size:</label>
            <input type="text" name="size" value="<?php echo htmlspecialchars($product['size']); ?>" required>

            <label for="price">Price:</label>
            <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>

            <label for="image">Product Image:</label>
            <input type="file" name="image" accept="image/*">
            <br>
            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" width="100">

            <button type="submit">Update Product</button>
        </form>
    </div>
</div>

</body>
</html>