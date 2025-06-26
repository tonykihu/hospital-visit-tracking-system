<?php
session_start();
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        
        if ($user = $stmt->fetch()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                header("Location: dashboard.php");
                exit();
            }
        }
        $error = "Invalid username or password";
    } catch (PDOException $e) {
        $error = "System error. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital System - Login</title>
    <style>
        :root {
            --primary: #2a9d8f;
            --primary-dark: #1d7874;
            --secondary: #e9c46a;
            --danger: #e76f51;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: linear-gradient(rgba(255,255,255,0.9), rgba(255,255,255,0.9)), 
                              url('hospital-bg.jpg');
            background-size: cover;
            background-position: center;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .login-header {
            background-color: var(--primary);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .login-header p {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .login-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.2);
        }
        
        .btn {
            display: inline-block;
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .error-message {
            color: var(--danger);
            background-color: rgba(231, 111, 81, 0.1);
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border-left: 4px solid var(--danger);
        }
        
        .login-footer {
            text-align: center;
            padding: 0 30px 30px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .hospital-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .hospital-logo img {
            height: 60px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="hospital-logo">
                <img src="hospital-logo.png" alt="Hospital Logo">
            </div>
            <h1>Hospital Portal</h1>
            <p>Secure access to medical records</p>
        </div>
        
        <form class="login-form" method="post">
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="login-footer">
            <p>Don't have an account? <a href="register.php">Request access</a></p>
            <p>Forgot password? <a href="forgot-password.php">Reset here</a></p>
        </div>
    </div>
</body>
</html>