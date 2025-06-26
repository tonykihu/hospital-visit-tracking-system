<?php
session_start();
require 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role']; // This captures the selected role
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // Insert user with role
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $role]);
            
            $success = "Registration successful! You can now login.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Username already exists";
            } else {
                $error = "Registration error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Registration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; }
        .error { color: red; padding: 10px; margin-bottom: 10px; border: 1px solid red; }
        .success { color: green; padding: 10px; margin-bottom: 10px; border: 1px solid green; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background: #4CAF50; color: white; padding: 10px 15px; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>User Registration</h1>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="role">User Role:</label>
            <select id="role" name="role" required>
                <option value="">Select a role</option>
                <option value="Admin">Administrator</option>
                <option value="Doctor">Doctor</option>
                <option value="Receptionist">Receptionist</option>
            </select>
        </div>
        
        <button type="submit">Register</button>
    </form>
    
    <p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>