<?php
require 'config.php';

// User credentials to create
$username = 'admin';       // Change if needed
$password = 'admin123';    // Change to your desired password
$role = 'Admin';           // Can be 'Admin', 'Doctor', or 'Receptionist'

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert into database
try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hashed_password, $role]);
    
    echo "<h1>User Created Successfully!</h1>";
    echo "<p><strong>Username:</strong> $username</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo "<p><strong>Role:</strong> $role</p>";
    
    echo '<p><a href="login.php">Go to Login Page</a></p>';
} catch (PDOException $e) {
    echo "<h1>Error Creating User</h1>";
    echo "<p style='color:red'>" . $e->getMessage() . "</p>";
    
    // Special message for duplicate username
    if ($e->getCode() == 23000) {
        echo "<p>User already exists. Try logging in or use a different username.</p>";
        echo '<p><a href="login.php">Go to Login Page</a></p>';
    }
}