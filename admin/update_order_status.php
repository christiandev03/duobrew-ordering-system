<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = trim($_POST['status']); // Trim any spaces

    // Ensure case consistency
    $allowedStatuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];
    if (in_array($newStatus, $allowedStatuses)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $orderId);
        if ($stmt->execute()) {
            echo "Success";
        } else {
            echo "Error updating status";
        }
        $stmt->close();
    } else {
        echo "Invalid status";
    }
}
?>
