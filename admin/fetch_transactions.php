<?php
include '../db.php';

$recentTransactions = $conn->query("SELECT id, type, amount, created_at FROM transactions WHERE type IN ('Cash', 'Gcash') ORDER BY created_at DESC LIMIT 5");

while ($row = $recentTransactions->fetch_assoc()): ?>
    <tr>
        <td><?php echo "TXN-" . date("Ymd", strtotime($row['created_at'])) . "-00001"; ?></td>
        <td><?php echo ucfirst($row['type']); ?></td>
        <td>₱<?php echo number_format($row['amount'], 2); ?></td>
        <td><?php echo date("F j, Y g:i A", strtotime($row['created_at'])); ?></td>
    </tr>
<?php endwhile; ?>
