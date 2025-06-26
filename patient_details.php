<?php
session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if patient ID is provided
if (!isset($_GET['id'])) {
    header("Location: patients.php");
    exit();
}

$patient_id = $_GET['id'];

// Get patient details
try {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();

    if (!$patient) {
        header("Location: patients.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get patient visits
try {
    $visits = $pdo->prepare("SELECT v.visit_id, v.visit_date, v.diagnosis, v.prescription, v.status, 
                            d.first_name AS doctor_first, d.last_name AS doctor_last
                            FROM visits v
                            JOIN doctors d ON v.doctor_id = d.doctor_id
                            WHERE v.patient_id = ?
                            ORDER BY v.visit_date DESC");
    $visits->execute([$patient_id]);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details</title>
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
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .patient-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .patient-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-group h3 {
            color: var(--gray);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .info-group p {
            font-size: 1.1rem;
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
        
        .status-pending {
            color: #ffc107;
            font-weight: 600;
        }
        
        .status-completed {
            color: #28a745;
            font-weight: 600;
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
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Patient Details</h1>
            <div>
                <a href="patients.php" class="btn btn-outline">Back to Patients</a>
                <a href="edit_patient.php?id=<?php echo $patient_id; ?>" class="btn">Edit Patient</a>
            </div>
        </header>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                Patient record has been successfully updated.
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="patient-header">
                <h2><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h2>
                <span>Patient ID: <?php echo htmlspecialchars($patient['patient_id']); ?></span>
            </div>
            
            <div class="patient-info">
                <div class="info-group">
                    <h3>Date of Birth</h3>
                    <p><?php echo htmlspecialchars($patient['date_of_birth']); ?></p>
                </div>
                <div class="info-group">
                    <h3>Gender</h3>
                    <p><?php echo htmlspecialchars($patient['gender']); ?></p>
                </div>
                <div class="info-group">
                    <h3>Phone</h3>
                    <p><?php echo htmlspecialchars($patient['phone']); ?></p>
                </div>
                <div class="info-group">
                    <h3>Email</h3>
                    <p><?php echo htmlspecialchars($patient['email']); ?></p>
                </div>
                <div class="info-group">
                    <h3>Address</h3>
                    <p><?php echo nl2br(htmlspecialchars($patient['address'])); ?></p>
                </div>
                <div class="info-group">
                    <h3>Insurance Information</h3>
                    <p><?php echo nl2br(htmlspecialchars($patient['insurance_info'])); ?></p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>Visit History</h2>
            <?php if ($visits->rowCount() > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Doctor</th>
                            <th>Diagnosis</th>
                            <th>Prescription</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visits as $visit): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($visit['visit_date'])); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($visit['doctor_first'] . ' ' . $visit['doctor_last']); ?></td>
                            <td><?php echo htmlspecialchars($visit['diagnosis']); ?></td>
                            <td><?php echo htmlspecialchars($visit['prescription']); ?></td>
                            <td class="status-<?php echo strtolower($visit['status']); ?>">
                                <?php echo htmlspecialchars($visit['status']); ?>
                            </td>
                            <td class="action-links">
                                <a href="visit_details.php?id=<?php echo $visit['visit_id']; ?>">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No visit history found for this patient.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>