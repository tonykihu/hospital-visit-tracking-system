<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: patients.php");
    exit();
}

$patient_id = $_GET['id'];

// Get patient details
$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if (!$patient) {
    header("Location: patients.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_patient'])) {
    $stmt = $pdo->prepare("UPDATE patients SET 
                          first_name = ?, 
                          last_name = ?, 
                          date_of_birth = ?, 
                          gender = ?, 
                          address = ?, 
                          phone = ?, 
                          email = ?, 
                          insurance_info = ?
                          WHERE patient_id = ?");
    
    $stmt->execute([
        htmlspecialchars($_POST['first_name']),
        htmlspecialchars($_POST['last_name']),
        $_POST['date_of_birth'],
        $_POST['gender'],
        htmlspecialchars($_POST['address']),
        htmlspecialchars($_POST['phone']),
        htmlspecialchars($_POST['email']),
        htmlspecialchars($_POST['insurance_info']),
        $patient_id
    ]);
    
    header("Location: patient_details.php?id=$patient_id&success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient</title>
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
            background-color: #f0f2f5;
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
            border-bottom: 1px solid #ddd;
        }
        
        h1 {
            color: var(--primary-dark);
        }
        
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary-light);
        }
        
        .btn-danger {
            background-color: var(--danger);
        }
        
        .btn-danger:hover {
            background-color: #dc3545;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .success-message {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Edit Patient</h1>
            <a href="patient_details.php?id=<?php echo $patient_id; ?>" class="btn btn-outline">Back to Patient</a>
        </header>
        
        <div class="card">
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($patient['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($patient['last_name']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                               value="<?php echo htmlspecialchars($patient['date_of_birth']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select class="form-control" id="gender" name="gender" required>
                            <option value="Male" <?php echo $patient['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $patient['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $patient['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea class="form-control" id="address" name="address"><?php echo htmlspecialchars($patient['address']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($patient['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($patient['email']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="insurance_info">Insurance Information</label>
                    <textarea class="form-control" id="insurance_info" name="insurance_info"><?php echo htmlspecialchars($patient['insurance_info']); ?></textarea>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn" name="update_patient">Update Patient</button>
                    <a href="patient_details.php?id=<?php echo $patient_id; ?>" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>