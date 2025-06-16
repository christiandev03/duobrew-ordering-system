<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $size = $_POST['size'];
    $price = $_POST['price'];

    // Handle Image Upload
    $target_dir = "uploads/"; // Save inside 'admin/uploads/'
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true); // Create folder if it doesn't exist
    }

    $image = basename($_FILES["image"]["name"]);
    $imageFileType = strtolower(pathinfo($image, PATHINFO_EXTENSION));

    // Validate image type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($imageFileType, $allowed_types)) {
        die("Error: Only JPG, JPEG, PNG, and GIF files are allowed.");
    }

    // Prevent overwriting files
    $new_filename = time() . "_" . $image; // Add timestamp to the filename
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO products (name, category, description, size, price, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssds", $name, $category, $description, $size, $price, $new_filename);
        
        if ($stmt->execute()) {
            header("Location: manage_products.php?success=Product added successfully");
            exit();
        } else {
            echo "Error adding product.";
        }
    } else {
        echo "Error uploading file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
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
        <h2>Add Product</h2>

        <form method="POST" enctype="multipart/form-data">
            <label for="name">Product Name:</label>
            <input type="text" name="name" required>

            <label for="category">Category:</label>
            <input type="text" name="category" required>

            <label for="description">Description:</label>
            <textarea name="description" required></textarea>

            <label for="size">Size:</label>
            <input type="text" name="size" required>

            <label for="price">Price:</label>
            <input type="number" step="0.01" name="price" required>

            <label for="image">Product Image:</label>
            <input type="file" name="image" accept="image/*" required>

            <button type="submit">Add Product</button>
        </form>
    </div>
</div>

</body>
</html>