<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Schedule new visit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['schedule_visit'])) {
    $stmt = $pdo->prepare("INSERT INTO visits (patient_id, doctor_id, visit_date, purpose, status) 
                           VALUES (?, ?, ?, ?, 'Scheduled')");
    $stmt->execute([
        $_POST['patient_id'],
        $_POST['doctor_id'],
        $_POST['visit_date'],
        htmlspecialchars($_POST['purpose'])
    ]);
    header("Location: visits.php?success=1");
    exit();
}

// Update visit status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE visits SET status = ? WHERE visit_id = ?");
    $stmt->execute([$_POST['status'], $_POST['visit_id']]);
    header("Location: visits.php");
    exit();
}

// Get all visits
$visits = $pdo->query("SELECT v.*, p.first_name AS patient_first, p.last_name AS patient_last, 
                       d.first_name AS doctor_first, d.last_name AS doctor_last
                       FROM visits v
                       JOIN patients p ON v.patient_id = p.patient_id
                       JOIN doctors d ON v.doctor_id = d.doctor_id
                       ORDER BY v.visit_date DESC")->fetchAll();

// Get patients and doctors for dropdowns
$patients = $pdo->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY last_name")->fetchAll();
$doctors = $pdo->query("SELECT doctor_id, first_name, last_name FROM doctors ORDER BY last_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visit Management | Hospital System</title>
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tr:hover {
            background-color: var(--primary-light);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-scheduled {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-links a {
            color: var(--primary);
            text-decoration: none;
            margin-right: 10px;
        }
        
        .action-links a:hover {
            text-decoration: underline;
        }
        
        .success-message {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
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
            <h1><i class="fas fa-calendar-check"></i> Visit Management</h1>
            <a href="dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </header>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Visit has been successfully scheduled.
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2><i class="fas fa-plus-circle"></i> Schedule New Visit</h2>
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="patient_id">Patient</label>
                        <select class="form-control" id="patient_id" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo htmlspecialchars($patient['patient_id']); ?>">
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
                                <option value="<?php echo htmlspecialchars($doctor['doctor_id']); ?>">
                                    <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group datetime-input">
                        <label for="visit_date">Date & Time</label>
                        <input type="datetime-local" class="form-control" id="visit_date" name="visit_date" required>
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="purpose">Purpose of Visit</label>
                    <textarea class="form-control" id="purpose" name="purpose" placeholder="Enter the reason for the visit"></textarea>
                </div>
                
                <button type="submit" class="btn" name="schedule_visit">
                    <i class="fas fa-calendar-plus"></i> Schedule Visit
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-list"></i> Visit List</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Date/Time</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visits as $visit): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($visit['visit_id']); ?></td>
                        <td><?php echo htmlspecialchars($visit['patient_first'] . ' ' . $visit['patient_last']); ?></td>
                        <td>Dr. <?php echo htmlspecialchars($visit['doctor_first'] . ' ' . $visit['doctor_last']); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($visit['visit_date'])); ?></td>
                        <td><?php echo htmlspecialchars($visit['purpose']); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="visit_id" value="<?php echo htmlspecialchars($visit['visit_id']); ?>">
                                <select name="status" onchange="this.form.submit()" class="status-badge status-<?php echo strtolower($visit['status']); ?>">
                                    <option value="Scheduled" <?php echo $visit['status'] == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="Completed" <?php echo $visit['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo $visit['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </td>
                        <td class="action-links">
                            <a href="visit_details.php?id=<?php echo $visit['visit_id']; ?>">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <?php if ($visit['status'] == 'Scheduled'): ?>
                                <a href="edit_visit.php?id=<?php echo $visit['visit_id']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Set minimum datetime to current time
        document.getElementById('visit_date').min = new Date().toISOString().slice(0, 16);
    </script>
</body>
</html>