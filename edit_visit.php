<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if visit ID is provided
if (!isset($_GET['id'])) {
    header("Location: visits.php");
    exit();
}

$visit_id = $_GET['id'];

// Fetch visit details
$stmt = $pdo->prepare("SELECT * FROM visits WHERE visit_id = ?");
$stmt->execute([$visit_id]);
$visit = $stmt->fetch();

if (!$visit) {
    header("Location: visits.php");
    exit();
}

// Get patients and doctors for dropdowns
$patients = $pdo->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY last_name")->fetchAll();
$doctors = $pdo->query("SELECT doctor_id, first_name, last_name FROM doctors ORDER BY last_name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_visit'])) {
    $stmt = $pdo->prepare("UPDATE visits SET 
                          patient_id = ?, 
                          doctor_id = ?, 
                          visit_date = ?, 
                          purpose = ?
                          WHERE visit_id = ?");
    $stmt->execute([
        $_POST['patient_id'],
        $_POST['doctor_id'],
        $_POST['visit_date'],
        htmlspecialchars($_POST['purpose']),
        $visit_id
    ]);
    
    header("Location: visits.php?updated=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Visit | Hospital System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Use the same CSS as visits.php for consistency */
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
        
        select.form-control {
            height: 40px;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .datetime-input {
            position: relative;
        }
        
        .datetime-input i {
            position: absolute;
            right: 10px;
            top: 10px;
            color: var(--gray);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-edit"></i> Edit Visit</h1>
            <a href="visits.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Visits
            </a>
        </header>
        
        <div class="card">
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="patient_id">Patient</label>
                        <select class="form-control" id="patient_id" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo htmlspecialchars($patient['patient_id']); ?>"
                                    <?php echo $patient['patient_id'] == $visit['patient_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="doctor_id">Doctor</label>
                        <select class="form-control" id="doctor_id" name="doctor_id" required>
                            <option value="">Select Doctor</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo htmlspecialchars($doctor['doctor_id']); ?>"
                                    <?php echo $doctor['doctor_id'] == $visit['doctor_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group datetime-input">
                        <label for="visit_date">Date & Time</label>
                        <input type="datetime-local" class="form-control" id="visit_date" name="visit_date" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($visit['visit_date'])); ?>" required>
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="purpose">Purpose of Visit</label>
                    <textarea class="form-control" id="purpose" name="purpose" 
                              placeholder="Enter the reason for the visit"><?php echo htmlspecialchars($visit['purpose']); ?></textarea>
                </div>
                
                <button type="submit" class="btn" name="update_visit">
                    <i class="fas fa-save"></i> Update Visit
                </button>
            </form>
        </div>
    </div>

    <script>
        // Set minimum datetime to current time
        document.getElementById('visit_date').min = new Date().toISOString().slice(0, 16);
    </script>
</body>
</html>