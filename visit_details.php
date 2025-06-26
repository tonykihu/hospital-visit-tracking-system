<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$visit_id = $_GET['id'] ?? 0;

// Get visit details
$stmt = $pdo->prepare("SELECT v.*, p.first_name AS patient_first, p.last_name AS patient_last, 
                       d.first_name AS doctor_first, d.last_name AS doctor_last, d.specialization
                       FROM visits v
                       JOIN patients p ON v.patient_id = p.patient_id
                       JOIN doctors d ON v.doctor_id = d.doctor_id
                       WHERE v.visit_id = ?");
$stmt->execute([$visit_id]);
$visit = $stmt->fetch();

if (!$visit) {
    die("Visit not found");
}

// Update diagnosis/prescription
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_visit'])) {
    $stmt = $pdo->prepare("UPDATE visits SET diagnosis = ?, prescription = ?, status = ? WHERE visit_id = ?");
    $stmt->execute([
        htmlspecialchars($_POST['diagnosis']), 
        htmlspecialchars($_POST['prescription']),
        $_POST['status'],
        $visit_id
    ]);
    header("Location: visit_details.php?id=$visit_id&success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visit Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #2a9d8f;
            --primary-light: rgba(42, 157, 143, 0.1);
            --primary-dark: #1d7874;
            --secondary: #e9c46a;
            --danger: #e76f51;
            --warning: #f39c12;
            --success: #27ae60;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --white: #ffffff;
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
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        h1 {
            color: var(--primary-dark);
            margin-bottom: 20px;
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 10px;
        }

        h2 {
            color: var(--primary);
            margin: 25px 0 15px;
            font-size: 1.3rem;
        }

        h3 {
            color: var(--dark);
            margin: 20px 0 10px;
            font-size: 1.1rem;
        }

        .info-card {
            background: var(--white);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: 600;
            color: var(--gray);
            min-width: 120px;
        }

        .info-value {
            color: var(--dark);
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-scheduled {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .status-completed {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }

        .status-cancelled {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        form {
            margin-top: 30px;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-height: 120px;
            margin-bottom: 20px;
            font-size: 1rem;
            line-height: 1.5;
        }

        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .form-group {
            margin-bottom: 20px;
        }

        select {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 1rem;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary-light);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .success-message {
            background-color: rgba(39, 174, 96, 0.2);
            color: var(--success);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            border-left: 4px solid var(--success);
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-calendar-check"></i> Visit Details</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Visit details have been successfully updated.
            </div>
        <?php endif; ?>
        
        <div class="info-card">
            <h2><i class="fas fa-user-injured"></i> Patient Information</h2>
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($visit['patient_first'] . ' ' . $visit['patient_last']); ?></span>
            </div>
        </div>
        
        <div class="info-card">
            <h2><i class="fas fa-user-md"></i> Doctor Information</h2>
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value">Dr. <?php echo htmlspecialchars($visit['doctor_first'] . ' ' . $visit['doctor_last']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Specialization:</span>
                <span class="info-value"><?php echo htmlspecialchars($visit['specialization']); ?></span>
            </div>
        </div>
        
        <div class="info-card">
            <h2><i class="fas fa-info-circle"></i> Visit Information</h2>
            <div class="info-row">
                <span class="info-label">Date/Time:</span>
                <span class="info-value"><?php echo date('F j, Y g:i A', strtotime($visit['visit_date'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Purpose:</span>
                <span class="info-value"><?php echo htmlspecialchars($visit['purpose']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="status-badge status-<?php echo strtolower($visit['status']); ?>">
                        <?php echo htmlspecialchars($visit['status']); ?>
                    </span>
                </span>
            </div>
        </div>
        
        <form method="post">
            <div class="form-group">
                <h3><i class="fas fa-stethoscope"></i> Diagnosis</h3>
                <textarea name="diagnosis" placeholder="Enter diagnosis details..."><?php echo htmlspecialchars($visit['diagnosis']); ?></textarea>
            </div>
            
            <div class="form-group">
                <h3><i class="fas fa-prescription-bottle-alt"></i> Prescription</h3>
                <textarea name="prescription" placeholder="Enter prescription details..."><?php echo htmlspecialchars($visit['prescription']); ?></textarea>
            </div>
            
            <div class="form-group">
                <h3><i class="fas fa-info-circle"></i> Status</h3>
                <select name="status">
                    <option value="Scheduled" <?php echo $visit['status'] == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="Completed" <?php echo $visit['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Cancelled" <?php echo $visit['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="action-buttons">
                <button type="submit" name="update_visit" class="btn">
                    <i class="fas fa-save"></i> Update Visit
                </button>
                <a href="visits.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Visits
                </a>
            </div>
        </form>
    </div>
</body>
</html>