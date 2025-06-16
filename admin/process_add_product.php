<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $size = $_POST['size'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    
    // Handle image upload
    $targetDir = "uploads/";  // Folder to store uploaded images
    $imageName = basename($_FILES["image"]["name"]);
    $targetFilePath = $targetDir . $imageName;
    $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // Validate image file
    $allowedTypes = array("jpg", "jpeg", "png", "gif");
    if (!in_array($imageFileType, $allowedTypes)) {
        echo "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
        exit();
    }

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
        // Insert product into database
        $stmt = $conn->prepare("INSERT INTO products (name, category, size, price, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdis", $name, $category, $size, $price, $stock, $imageName);

        if ($stmt->execute()) {
            header("Location: manage_products.php?success=1");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "Failed to upload image.";
    }
}
?>
