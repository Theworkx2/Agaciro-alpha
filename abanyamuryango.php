<?php
require_once 'config/database.php';

$message = '';
$messageType = '';

// Handle member deletion
if (isset($_POST['delete_member'])) {
    $member_id = $_POST['member_id'];
    
    // Check if member has any transactions
    $check_transactions = "SELECT COUNT(*) as count FROM transactions WHERE member_id = ?";
    $stmt = mysqli_prepare($conn, $check_transactions);
    mysqli_stmt_bind_param($stmt, "i", $member_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        $message = "Cannot delete member with existing transactions";
        $messageType = "danger";
    } else {
        $delete_query = "DELETE FROM members WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $member_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Member deleted successfully";
            $messageType = "success";
        } else {
            $message = "Error deleting member: " . mysqli_error($conn);
            $messageType = "danger";
        }
    }
}

// Handle member addition/update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    
    if (empty($name)) {
        $message = "Name is required";
        $messageType = "danger";
    } else {
        if (isset($_POST['member_id']) && !empty($_POST['member_id'])) {
            // Update existing member
            $member_id = $_POST['member_id'];
            $sql = "UPDATE members SET name=?, phone=?, email=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssi", $name, $phone, $email, $member_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Member updated successfully";
                $messageType = "success";
            } else {
                $message = "Error updating member: " . mysqli_error($conn);
                $messageType = "danger";
            }
        } else {
            // Add new member
            $sql = "INSERT INTO members (name, phone, email) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $name, $phone, $email);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Member added successfully";
                $messageType = "success";
            } else {
                $message = "Error adding member: " . mysqli_error($conn);
                $messageType = "danger";
            }
        }
    }
}

// Fetch all members
$members_query = "SELECT * FROM members ORDER BY name";
$members_result = mysqli_query($conn, $members_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abanyamuryango - Agaciro Saving Group</title>
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
        .table {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .table th, .table td {
            border-color: rgba(255, 255, 255, 0.2);
        }
        .btn-edit {
            color: var(--accent-yellow);
            cursor: pointer;
        }
        .btn-delete {
            color: #ff4444;
            cursor: pointer;
        }
        .btn-edit:hover, .btn-delete:hover {
            opacity: 0.8;
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
            <h1>Abanya<span>muryango</span></h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="content-container">
            <!-- Add/Edit Member Form -->
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="mb-4">
                <input type="hidden" name="member_id" id="member_id" value="">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                </div>
                <button type="submit" name="submit" class="nav-button">Add Member</button>
            </form>

            <!-- Members Table -->
            <div class="modern-table">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Joined Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($member = mysqli_fetch_assoc($members_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($member['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-edit" 
                                                    onclick="editMember(<?php echo htmlspecialchars(json_encode([
                                                        'id' => $member['id'],
                                                        'name' => $member['name'],
                                                        'phone' => $member['phone'],
                                                        'email' => $member['email']
                                                    ])); ?>)">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                                </svg>
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                <button type="submit" name="delete_member" class="btn-action btn-delete" 
                                                        onclick="return confirm('Are you sure you want to delete this member? This action cannot be undone.')">
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
    
    <script>
        function editMember(member) {
            document.getElementById('member_id').value = member.id;
            document.getElementById('name').value = member.name;
            document.getElementById('phone').value = member.phone;
            document.getElementById('email').value = member.email;
            document.querySelector('button[name="submit"]').textContent = 'Update Member';
        }
    </script>
</body>
</html> 