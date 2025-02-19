<?php
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header('Location: raporo.php');
    exit;
}

$member_id = $_GET['id'];

// Get member details
$member_query = "SELECT * FROM members WHERE id = ?";
$stmt = mysqli_prepare($conn, $member_query);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$member_result = mysqli_stmt_get_result($stmt);
$member = mysqli_fetch_assoc($member_result);

if (!$member) {
    header('Location: raporo.php');
    exit;
}

// Get member statistics
$stats_query = "SELECT 
    COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) as balance,
    COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) as total_deposits,
    COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END), 0) as total_withdrawals,
    COUNT(*) as transaction_count
FROM transactions 
WHERE member_id = ?";
$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($stats_result);

// Get all transactions for this member
$transactions_query = "SELECT * FROM transactions 
WHERE member_id = ? 
ORDER BY transaction_date DESC";
$stmt = mysqli_prepare($conn, $transactions_query);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$transactions_result = mysqli_stmt_get_result($stmt);

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
    <title><?php echo htmlspecialchars($member['name']); ?> - Agaciro Saving Group</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/agaciro.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .member-info {
            background: rgba(255, 255, 255, 0.15);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }
        .member-info h2 {
            color: var(--accent-yellow);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            font-weight: 500;
        }
        .member-info p {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .member-info p strong {
            min-width: 120px;
            color: var(--accent-yellow);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .stat-card {
            background: var(--table-bg);
            border: 1px solid var(--table-border);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-width: 0;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            background: var(--table-hover);
        }
        .stat-card h3 {
            color: var(--accent-yellow);
            font-size: 1rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            width: 100%;
        }
        .stat-card p {
            font-size: 1.5rem;
            margin: 0;
            font-weight: 500;
            width: 100%;
            text-align: center;
        }
        .stat-card .badge {
            font-size: 1.25rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: rgba(13, 110, 253, 0.2);
            color: #fff;
            font-weight: 500;
        }
        .section-title {
            color: var(--accent-yellow);
            margin: 2rem 0 1rem;
            font-size: 1.5rem;
            font-weight: 500;
        }
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .member-info {
                padding: 1.5rem;
            }
            .stat-card {
                padding: 1.25rem;
            }
        }
        .table {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            margin-top: 1rem;
            border-radius: 10px;
            overflow: hidden;
        }
        .table th, .table td {
            border-color: rgba(255, 255, 255, 0.2);
            padding: 1rem;
            vertical-align: middle;
        }
        .table thead th {
            background: rgba(0, 0, 0, 0.2);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            color: var(--accent-yellow);
        }
        .deposit-amount {
            color: #00ff9d;
            font-weight: 500;
        }
        .withdrawal-amount {
            color: #ff4444;
            font-weight: 500;
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
        .transaction-filters {
            margin-bottom: 1rem;
        }
        .transaction-filters select {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
        @media print {
            .back-button, .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
            .content-container, .member-info, .stat-card {
                background: white !important;
                color: black !important;
            }
            .table {
                color: black !important;
                background: white !important;
            }
        }
    </style>
</head>
<body>
    <a href="raporo.php" class="back-button">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
        </svg>
        Back to Reports
    </a>

    <div class="main-container">
        <div class="content-container">
            <!-- Member Information -->
            <div class="member-info">
                <h2><?php echo htmlspecialchars($member['name']); ?></h2>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($member['phone'] ?: 'Not provided'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($member['email'] ?: 'Not provided'); ?></p>
                <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($member['created_at'])); ?></p>
            </div>

            <!-- Member Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Current Balance</h3>
                    <p class="<?php echo $stats['balance'] >= 0 ? 'deposit-amount' : 'withdrawal-amount'; ?>">
                        <?php echo format_amount($stats['balance']); ?>
                    </p>
                </div>
                <div class="stat-card">
                    <h3>Total Deposits</h3>
                    <p class="deposit-amount"><?php echo format_amount($stats['total_deposits']); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Withdrawals</h3>
                    <p class="withdrawal-amount"><?php echo format_amount($stats['total_withdrawals']); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Transactions</h3>
                    <p class="transactions-count">
                        <span class="badge"><?php echo $stats['transaction_count']; ?></span>
                    </p>
                </div>
            </div>

            <!-- Transaction History -->
            <h2 class="section-title mt-4">Transaction History</h2>
            <div class="modern-table">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($transaction = mysqli_fetch_assoc($transactions_result)): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $transaction['type'] === 'deposit' ? 'badge-deposit' : 'badge-withdrawal'; ?>">
                                            <?php echo ucfirst($transaction['type']); ?>
                                        </span>
                                    </td>
                                    <td class="<?php echo $transaction['type'] === 'deposit' ? 'deposit-amount' : 'withdrawal-amount'; ?>">
                                        <?php echo format_amount($transaction['amount']); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-edit" 
                                                    onclick="editTransaction(<?php echo htmlspecialchars(json_encode([
                                                        'id' => $transaction['id'],
                                                        'amount' => $transaction['amount'],
                                                        'type' => $transaction['type'],
                                                        'member' => $member['name']
                                                    ])); ?>)">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                                </svg>
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                                <button type="submit" name="delete_transaction" class="btn-action btn-delete" 
                                                        onclick="return confirm('Are you sure you want to delete this transaction?')">
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

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 