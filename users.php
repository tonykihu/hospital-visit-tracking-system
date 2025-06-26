<?php
session_start();

// Redirect to login if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Database connection
require 'config.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Add new user
        $username = trim($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password, $role]);
            $success = "User added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding user: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_user'])) {
        // Update existing user
        $user_id = $_POST['user_id'];
        $role = $_POST['role'];
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
            $stmt->execute([$role, $user_id]);
            $success = "User updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating user: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        $user_id = $_POST['user_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $success = "User deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting user: " . $e->getMessage();
        }
    } elseif (isset($_POST['reset_password'])) {
        // Reset password
        $user_id = $_POST['user_id'];
        $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$password, $user_id]);
            $success = "Password reset successfully!";
        } catch (PDOException $e) {
            $error = "Error resetting password: " . $e->getMessage();
        }
    }
}

// Get all users
try {
    $stmt = $pdo->query("SELECT user_id, username, role FROM users ORDER BY username");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #2a9d8f;
            --primary-light: rgba(42, 157, 143, 0.1);
            --primary-dark: #1d7874;
            --secondary: #e9c46a;
            --danger: #e76f51;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        h1 {
            color: var(--primary-dark);
            margin-bottom: 20px;
        }

        .back-btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--gray);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: background 0.3s;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background-color: #5a6268;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 30px;
        }

        .card h2 {
            color: var(--primary-dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--gray);
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }

        .btn:hover {
            background-color: var(--primary-dark);
        }

        .btn-danger {
            background-color: var(--danger);
        }

        .btn-danger:hover {
            background-color: #dc3545;
        }

        .btn-secondary {
            background-color: var(--gray);
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            color: var(--gray);
            font-weight: 600;
            background-color: var(--primary-light);
        }

        tr:hover {
            background-color: var(--primary-light);
        }

        .action-btns {
            display: flex;
            gap: 5px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h1>Manage Users</h1>
                <p>Admin Panel</p>
            </div>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </header>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><i class="fas fa-user-plus"></i> Add New User</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="Admin">Admin</option>
                        <option value="Doctor">Doctor</option>
                        <option value="Nurse">Nurse</option>
                        <option value="Staff" selected>Staff</option>
                    </select>
                </div>
                <button type="submit" name="add_user" class="btn">
                    <i class="fas fa-save"></i> Add User
                </button>
            </form>
        </div>

        <div class="card">
            <h2><i class="fas fa-users"></i> Existing Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td class="action-btns">
                            <button onclick="openEditModal(<?php echo $user['user_id']; ?>, '<?php echo $user['role']; ?>')" 
                                    class="btn btn-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="openResetModal(<?php echo $user['user_id']; ?>)" 
                                    class="btn btn-secondary">
                                <i class="fas fa-key"></i> Reset Password
                            </button>
                            <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this user?')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit User Role</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="form-group">
                    <label for="editRole">Role</label>
                    <select id="editRole" name="role" required>
                        <option value="Admin">Admin</option>
                        <option value="Doctor">Doctor</option>
                        <option value="Nurse">Nurse</option>
                        <option value="Staff">Staff</option>
                    </select>
                </div>
                <button type="submit" name="update_user" class="btn">
                    <i class="fas fa-save"></i> Update User
                </button>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeResetModal()">&times;</span>
            <h2>Reset Password</h2>
            <form method="POST" id="resetForm">
                <input type="hidden" name="user_id" id="resetUserId">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <button type="submit" name="reset_password" class="btn">
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </form>
        </div>
    </div>

    <script>
        // Edit User Modal Functions
        function openEditModal(userId, currentRole) {
            document.getElementById('editUserId').value = userId;
            document.getElementById('editRole').value = currentRole;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Reset Password Modal Functions
        function openResetModal(userId) {
            document.getElementById('resetUserId').value = userId;
            document.getElementById('resetModal').style.display = 'block';
        }

        function closeResetModal() {
            document.getElementById('resetModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>