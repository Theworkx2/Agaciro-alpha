<?php
require_once 'config/database.php';

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $member_id = $_POST['member_id'];
    $amount = $_POST['amount'];
    $reason = trim($_POST['reason']);
    
    // Validate amount
    if ($amount <= 0) {
        $message = "Amount must be greater than 0";
        $messageType = "danger";
    } else {
        // Insert transaction
        $sql = "INSERT INTO transactions (member_id, type, amount, reason) VALUES (?, 'deposit', ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt === false) {
            $message = "Error preparing statement: " . mysqli_error($conn);
            $messageType = "danger";
        } else {
            mysqli_stmt_bind_param($stmt, "ids", $member_id, $amount, $reason);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Deposit of " . number_format($amount, 0, '.', ',') . " RWF successful!";
                $messageType = "success";
            } else {
                $message = "Error executing statement: " . mysqli_stmt_error($stmt);
                $messageType = "danger";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch all members for the dropdown
$members_query = "SELECT id, name, phone FROM members ORDER BY name";
$members_result = mysqli_query($conn, $members_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitsa - Agaciro Saving Group</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/agaciro.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/style.css" rel="stylesheet">
    <style>
        .form-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            margin-top: 2rem;
        }
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.3);
            border-color: var(--accent-yellow);
            color: white;
            box-shadow: 0 0 0 0.25rem rgba(255, 215, 0, 0.25);
        }
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
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
        .form-select option {
            background: var(--primary-blue);
            color: white;
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
    </style>
</head>
<body>
    <a href="index.php" class="back-button">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
        </svg>
        Back
    </a>

    <div class="main-container">
        <div class="title">
            <h1>Bitsa <span>Amafaranga</span></h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-3">
                    <label for="member_id" class="form-label">Select Member</label>
                    <select class="form-select" id="member_id" name="member_id" required>
                        <option value="">Choose a member...</option>
                        <?php while ($member = mysqli_fetch_assoc($members_result)): ?>
                            <option value="<?php echo $member['id']; ?>">
                                <?php echo htmlspecialchars($member['name']) . ' (' . htmlspecialchars($member['phone']) . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="amount" class="form-label">Amount (RWF)</label>
                    <input type="number" class="form-control" id="amount" name="amount" placeholder="Enter amount" required min="0">
                </div>

                <div class="mb-4">
                    <label for="reason" class="form-label">Reason for Saving</label>
                    <input type="text" class="form-control" id="reason" name="reason" placeholder="Enter reason for saving" required>
                </div>

                <button type="submit" class="nav-button">Submit Deposit</button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 