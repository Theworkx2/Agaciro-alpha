<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$transaction_id = $_POST['transaction_id'] ?? null;
$new_amount = $_POST['amount'] ?? null;
$new_reason = trim($_POST['reason'] ?? '');

if (!$transaction_id || !$new_amount || $new_reason === '') {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if ($new_amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
    exit;
}

// Get the current transaction details
$current_sql = "SELECT * FROM transactions WHERE id = ?";
$current_stmt = mysqli_prepare($conn, $current_sql);
mysqli_stmt_bind_param($current_stmt, "i", $transaction_id);
mysqli_stmt_execute($current_stmt);
$current_result = mysqli_stmt_get_result($current_stmt);
$current_transaction = mysqli_fetch_assoc($current_result);

if (!$current_transaction) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    exit;
}

// For withdrawals, check if the new amount would exceed the current balance
if ($current_transaction['type'] === 'withdrawal') {
    $balance_sql = "SELECT 
        COALESCE(SUM(CASE 
            WHEN type = 'deposit' THEN amount 
            WHEN type = 'withdrawal' AND id != ? THEN -amount 
            ELSE 0 
        END), 0) as balance 
        FROM transactions 
        WHERE member_id = ?";
    
    $balance_stmt = mysqli_prepare($conn, $balance_sql);
    mysqli_stmt_bind_param($balance_stmt, "ii", $transaction_id, $current_transaction['member_id']);
    mysqli_stmt_execute($balance_stmt);
    $balance_result = mysqli_stmt_get_result($balance_stmt);
    $balance_row = mysqli_fetch_assoc($balance_result);
    $available_balance = $balance_row['balance'];

    if ($new_amount > $available_balance) {
        echo json_encode([
            'success' => false, 
            'message' => 'Insufficient balance. Available: ' . number_format($available_balance, 0, '.', ',') . ' RWF'
        ]);
        exit;
    }
    mysqli_stmt_close($balance_stmt);
}

// Update the transaction
$update_sql = "UPDATE transactions SET amount = ?, reason = ? WHERE id = ?";
$update_stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($update_stmt, "dsi", $new_amount, $new_reason, $transaction_id);

if (mysqli_stmt_execute($update_stmt)) {
    echo json_encode(['success' => true, 'message' => 'Transaction updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating transaction: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($current_stmt);
mysqli_stmt_close($update_stmt);
mysqli_close($conn);
?> 