<?php
require_once 'config/database.php';

$message = '';
$messageType = '';

// Handle transaction deletion
if (isset($_POST['delete_transaction'])) {
    $transaction_id = $_POST['transaction_id'];
    
    $delete_query = "DELETE FROM transactions WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $transaction_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Transaction deleted successfully";
        $messageType = "success";
    } else {
        $message = "Error deleting transaction: " . mysqli_error($conn);
        $messageType = "danger";
    }
}

// Handle transaction edit
if (isset($_POST['edit_transaction'])) {
    $transaction_id = $_POST['transaction_id'];
    $new_amount = $_POST['amount'];
    $new_reason = trim($_POST['reason']);
    
    if ($new_amount <= 0) {
        $message = "Amount must be greater than 0";
        $messageType = "danger";
    } else {
        // Get transaction type first
        $get_type_query = "SELECT type FROM transactions WHERE id = ?";
        $stmt = mysqli_prepare($conn, $get_type_query);
        mysqli_stmt_bind_param($stmt, "i", $transaction_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $transaction = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($transaction['type'] === 'withdrawal') {
            // Check if new amount exceeds available balance
            $balance_query = "SELECT 
                COALESCE(SUM(CASE 
                    WHEN type = 'deposit' THEN amount 
                    WHEN type = 'withdrawal' AND id != ? THEN -amount 
                    ELSE 0 
                END), 0) as balance 
                FROM transactions 
                WHERE member_id = (SELECT member_id FROM transactions WHERE id = ?)";
            
            $stmt = mysqli_prepare($conn, $balance_query);
            mysqli_stmt_bind_param($stmt, "ii", $transaction_id, $transaction_id);
            mysqli_stmt_execute($stmt);
            $balance_result = mysqli_stmt_get_result($stmt);
            $balance_row = mysqli_fetch_assoc($balance_result);
            mysqli_stmt_close($stmt);

            if ($new_amount > $balance_row['balance']) {
                $message = "Insufficient balance. Available: " . number_format($balance_row['balance'], 0, '.', ',') . " RWF";
                $messageType = "danger";
                exit;
            }
        }

        // Update transaction
        $update_query = "UPDATE transactions SET amount = ?, reason = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "dsi", $new_amount, $new_reason, $transaction_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Transaction updated successfully";
            $messageType = "success";
        } else {
            $message = "Error updating transaction: " . mysqli_error($conn);
            $messageType = "danger";
        }
        mysqli_stmt_close($stmt);
    }
}

// Get total savings
$total_query = "SELECT 
    COALESCE(SUM(CASE 
        WHEN type = 'deposit' THEN amount 
        WHEN type = 'withdrawal' THEN -(amount + COALESCE(fees, 0))
        ELSE 0
    END), 0) as total_balance,
    COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) as total_deposits,
    COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END), 0) as total_withdrawals,
    COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN COALESCE(fees, 0) ELSE 0 END), 0) as total_fees
FROM transactions";
$total_result = mysqli_query($conn, $total_query);

if (!$total_result) {
    die("Error in total query: " . mysqli_error($conn));
}

$totals = mysqli_fetch_assoc($total_result);
if (!$totals) {
    $totals = array(
        'total_balance' => 0,
        'total_deposits' => 0,
        'total_withdrawals' => 0,
        'total_fees' => 0
    );
}

// Get member balances
$balances_query = "SELECT 
    m.id,
    m.name,
    m.phone,
    COALESCE(SUM(CASE 
        WHEN t.type = 'deposit' THEN t.amount 
        WHEN t.type = 'withdrawal' THEN -(t.amount + COALESCE(t.fees, 0))
        ELSE 0
    END), 0) as balance,
    COALESCE(SUM(CASE WHEN t.type = 'deposit' THEN t.amount ELSE 0 END), 0) as total_deposits,
    COALESCE(SUM(CASE WHEN t.type = 'withdrawal' THEN t.amount ELSE 0 END), 0) as total_withdrawals,
    COUNT(t.id) as transaction_count
FROM members m
LEFT JOIN transactions t ON m.id = t.member_id
GROUP BY m.id, m.name, m.phone
ORDER BY balance DESC";
$balances_result = mysqli_query($conn, $balances_query);

if (!$balances_result) {
    die("Error in balances query: " . mysqli_error($conn));
}

// Get recent transactions (removing LIMIT to show all)
$transactions_query = "SELECT 
    t.id,
    t.type,
    t.amount,
    t.fees,
    t.reason,
    t.transaction_date,
    m.name as member_name,
    m.id as member_id
FROM transactions t
JOIN members m ON t.member_id = m.id
ORDER BY t.transaction_date DESC";
$transactions_result = mysqli_query($conn, $transactions_query);

if (!$transactions_result) {
    die("Error in transactions query: " . mysqli_error($conn));
}

// Format number with RWF
function format_amount($amount) {
    return number_format($amount, 0, '.', ',') . ' RWF';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raporo - Agaciro Saving Group</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/agaciro.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/style.css" rel="stylesheet">
    <style>
        .content-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            margin-top: 2rem;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        .stat-card h3 {
            color: var(--accent-yellow);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        .stat-card p {
            margin-bottom: 0;
            opacity: 0.8;
        }
        .table {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            margin-top: 1rem;
        }
        .table th, .table td {
            border-color: rgba(255, 255, 255, 0.2);
        }
        .section-title {
            color: var(--accent-yellow);
            margin: 2rem 0 1rem;
        }
        .deposit-amount {
            color: #00ff9d;
        }
        .withdrawal-amount {
            color: #ff4444;
        }
        .back-button {
            position: absolute;
            top: 1rem;
            left: 1rem;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .back-button:hover {
            color: var(--accent-yellow);
        }
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .btn-action {
            background: transparent;
            border: none;
            padding: 0.5rem;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }
        .btn-action:hover {
            opacity: 0.8;
        }
        .btn-edit {
            color: var(--accent-yellow);
        }
        .btn-delete {
            color: #ff4444;
        }
        .modal-content {
            background: var(--primary-blue);
            color: white;
        }
        .modal-header {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        .modal-footer {
            border-top-color: rgba(255, 255, 255, 0.1);
        }
        .form-select {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            padding-right: 2.5rem;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .custom-toast {
            background: var(--primary-blue);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .custom-toast.show {
            opacity: 1;
        }
        
        .custom-toast.success {
            border-left: 4px solid #00ff9d;
        }
        
        .custom-toast.error {
            border-left: 4px solid #ff4444;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.3);
            border-color: var(--accent-yellow);
            color: white;
            box-shadow: 0 0 0 0.25rem rgba(255, 215, 0, 0.25);
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .print-button {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .print-button:hover {
            background: var(--accent-yellow);
            color: var(--primary-blue);
        }

        @media print {
            body {
                background: white !important;
                color: black !important;
                padding: 0 !important;
                font-size: 12pt;
            }
            
            .back-button, .print-button, .action-buttons, .btn-close,
            .modal, .toast-container {
                display: none !important;
            }

            /* Print Header */
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 2rem;
                padding: 1rem;
                border-bottom: 2px solid #000;
            }

            .print-header img {
                height: 60px;
                margin-bottom: 1rem;
            }

            .print-header h2 {
                margin: 0;
                font-size: 24pt;
                font-weight: bold;
            }

            .print-header p {
                margin: 0.5rem 0 0;
                font-size: 12pt;
            }

            /* Report Date */
            .report-date {
                display: block !important;
                text-align: right;
                margin-bottom: 1rem;
                font-size: 10pt;
            }

            .content-container {
                background: none !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .title {
                display: none !important;
            }

            /* Statistics Cards */
            .stats-container {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                margin: 2rem 0;
                page-break-inside: avoid;
            }

            .stat-card {
                border: 1px solid #000 !important;
                padding: 1rem !important;
                background: none !important;
            }

            .stat-card h3 {
                color: black !important;
                font-size: 14pt !important;
                font-weight: bold;
            }

            /* Tables */
            .modern-table {
                margin: 2rem 0;
                page-break-inside: avoid;
            }

            .table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 10pt !important;
            }

            .table th {
                background-color: #f0f0f0 !important;
                color: black !important;
                font-weight: bold !important;
                text-transform: uppercase;
                padding: 0.5rem !important;
            }

            .table td {
                padding: 0.5rem !important;
            }

            .table th, .table td {
                border: 1px solid #000 !important;
            }

            /* Section Titles */
            .section-title {
                color: black !important;
                font-size: 16pt !important;
                font-weight: bold;
                margin: 2rem 0 1rem !important;
                border-bottom: 1px solid #000;
                padding-bottom: 0.5rem;
            }

            /* Print Footer */
            .print-footer {
                display: block !important;
                position: fixed;
                bottom: 0;
                width: 100%;
                padding: 1rem;
                border-top: 1px solid #000;
                font-size: 8pt;
                text-align: center;
            }

            .print-footer .page-number:after {
                content: counter(page);
            }

            /* Page Settings */
            @page {
                size: A4;
                margin: 2cm;
            }

            /* Ensure proper coloring for amounts */
            .deposit-amount {
                color: #006400 !important;
                font-weight: bold;
            }

            .withdrawal-amount {
                color: #8b0000 !important;
                font-weight: bold;
            }

            .badge {
                background: none !important;
                border: 1px solid #000 !important;
                color: black !important;
                padding: 0.25rem 0.5rem !important;
            }

            /* Links */
            a {
                text-decoration: none !important;
                color: black !important;
            }

            /* Hide unnecessary elements */
            .bi-box-arrow-up-right {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Print Header (hidden in normal view) -->
    <div class="print-header" style="display: none;">
        <img src="assets/agaciro.png" alt="Agaciro Saving Group Logo">
        <h2>Agaciro Saving Group</h2>
        <p>Financial Report</p>
    </div>

    <!-- Report Date (hidden in normal view) -->
    <div class="report-date" style="display: none;">
        <p>Report Generated: <?php echo date('F j, Y, g:i a'); ?></p>
    </div>

    <a href="index.php" class="back-button">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
        </svg>
        Back
    </a>

    <button onclick="window.print()" class="print-button">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer" viewBox="0 0 16 16">
            <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
            <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
        </svg>
        Print Report
    </button>

    <div class="main-container">
        <div class="title">
            <h1>Ra<span>poro</span></h1>
        </div>

        <div class="content-container">
            <!-- Summary Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <h3><?php echo format_amount($totals['total_balance']); ?></h3>
                    <p>Total Balance</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo format_amount($totals['total_deposits']); ?></h3>
                    <p>Total Deposits</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo format_amount($totals['total_withdrawals']); ?></h3>
                    <p>Total Withdrawals</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo format_amount($totals['total_fees']); ?></h3>
                    <p>Total Fees</p>
                </div>
            </div>

            <!-- Member Balances -->
            <h2 class="section-title">Member Balances</h2>
            <div class="modern-table">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Phone</th>
                                <th>Current Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($member = mysqli_fetch_assoc($balances_result)): ?>
                                <tr>
                                    <td>
                                        <a href="member_details.php?id=<?php echo $member['id']; ?>" class="text-decoration-none" style="color: var(--accent-yellow);">
                                            <?php echo htmlspecialchars($member['name']); ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-up-right ms-1" viewBox="0 0 16 16">
                                                <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z"/>
                                                <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z"/>
                                            </svg>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                    <td class="<?php echo $member['balance'] >= 0 ? 'deposit-amount' : 'withdrawal-amount'; ?>">
                                        <?php echo format_amount($member['balance']); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Transactions -->
            <h2 class="section-title">Recent Transactions</h2>
            <div class="modern-table">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($transaction = mysqli_fetch_assoc($transactions_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['member_name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $transaction['type'] === 'deposit' ? 'badge-deposit' : 'badge-withdrawal'; ?>">
                                            <?php echo ucfirst($transaction['type']); ?>
                                        </span>
                                    </td>
                                    <td class="<?php echo $transaction['type'] === 'deposit' ? 'deposit-amount' : 'withdrawal-amount'; ?>">
                                        <?php echo format_amount($transaction['amount']); ?>
                                        <?php if ($transaction['type'] === 'withdrawal' && $transaction['fees'] > 0): ?>
                                            <br>
                                            <small class="text-muted">Fee: <?php echo format_amount($transaction['fees']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['reason']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($transaction['transaction_date'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-action btn-edit" 
                                                    onclick="editTransaction(<?php echo htmlspecialchars(json_encode([
                                                        'id' => $transaction['id'],
                                                        'amount' => $transaction['amount'],
                                                        'reason' => $transaction['reason']
                                                    ])); ?>)">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                                </svg>
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                                <button type="submit" name="delete_transaction" class="btn-action btn-delete" 
                                                        onclick="return confirm('Are you sure you want to delete this transaction? This action cannot be undone.')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6Z"/>
                                                        <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1ZM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118ZM2.5 3h11V2h-11v1Z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Transaction Modal -->
    <div class="modal fade" id="editTransactionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editTransactionForm" method="POST">
                        <input type="hidden" id="editTransactionId" name="transaction_id">
                        <input type="hidden" name="edit_transaction" value="true">
                        <div class="mb-3">
                            <label for="editAmount" class="form-label">Amount (RWF)</label>
                            <input type="number" class="form-control" id="editAmount" name="amount" required min="0">
                        </div>
                        <div class="mb-3">
                            <label for="editReason" class="form-label">Reason</label>
                            <input type="text" class="form-control" id="editReason" name="reason" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateTransaction()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container for Notifications -->
    <div class="toast-container"></div>

    <!-- Print Footer (hidden in normal view) -->
    <div class="print-footer" style="display: none;">
        <p>Agaciro Saving Group - Financial Report</p>
        <p>Generated on <?php echo date('F j, Y, g:i a'); ?></p>
        <p class="page-number">Page </p>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showNotification(message, type = 'success') {
            const container = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            toast.className = `custom-toast ${type}`;
            toast.textContent = message;
            container.appendChild(toast);

            // Trigger reflow to enable transition
            toast.offsetHeight;
            toast.classList.add('show');

            // Remove toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function editTransaction(transaction) {
            document.getElementById('editTransactionId').value = transaction.id;
            document.getElementById('editAmount').value = transaction.amount;
            document.getElementById('editReason').value = transaction.reason || '';
            new bootstrap.Modal(document.getElementById('editTransactionModal')).show();
        }

        function updateTransaction() {
            const form = document.getElementById('editTransactionForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            form.submit();
        }

        // Show PHP messages as notifications if they exist
        <?php if ($message): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification(<?php echo json_encode($message); ?>, <?php echo json_encode($messageType === 'success' ? 'success' : 'error'); ?>);
            });
        <?php endif; ?>
    </script>
</body>
</html> 