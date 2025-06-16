<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    echo "Unauthorized access.";
    exit();
}

include '../db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];

    // Fetch the product image file name
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product) {
        // Delete the image file from the server
        $image_path = "../uploads/" . $product['image'];
        if (file_exists($image_path) && !empty($product['image'])) {
            unlink($image_path); // Delete the image file
        }

        // Delete the product from the database
        $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $delete_stmt->bind_param("i", $product_id);
        if ($delete_stmt->execute()) {
            echo "success"; // Send success response to JavaScript
            exit();
        } else {
            echo "Error deleting product.";
            exit();
        }
    } else {
        echo "Product not found.";
        exit();
    }
}
?>
