<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = $_POST['order_id'];

    $conn->query("DELETE FROM orders WHERE id = $orderId");
    echo "Deleted";
}
?>
