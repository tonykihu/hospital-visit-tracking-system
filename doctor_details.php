<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user has permission (Admin or Receptionist)
$allowed_roles = ['Admin', 'Receptionist'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: dashboard.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: doctors.php");
    exit();
}

$doctor_id = $_GET['id'];

// Get doctor details
$stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    header("Location: doctors.php");
    exit();
}

// Get doctor's upcoming visits
$upcoming_visits = $pdo->prepare("SELECT v.visit_id, v.visit_date, p.first_name, p.last_name, v.status 
                                 FROM visits v
                                 JOIN patients p ON v.patient_id = p.patient_id
                                 WHERE v.doctor_id = ? AND v.visit_date >= CURDATE()
                                 ORDER BY v.visit_date ASC");
$upcoming_visits->execute([$doctor_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Details</title>
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
        
        .doctor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .doctor-info {
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
        
        .status-scheduled {
            color: #ffc107;
            font-weight: 600;
        }
        
        .status-completed {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-cancelled {
            color: #e74c3c;
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
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Doctor Details</h1>
            <div>
                <a href="doctors.php" class="btn btn-outline">Back to Doctors</a>
                <a href="edit_doctor.php?id=<?php echo $doctor_id; ?>" class="btn">Edit Doctor</a>
            </div>
        </header>
        
        <div class="card">
            <div class="doctor-header">
                <h2>Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h2>
                <span>Doctor ID: <?php echo htmlspecialchars($doctor['doctor_id']); ?></span>
            </div>
            
            <div class="doctor-info">
                <div class="info-group">
                    <h3>Specialization</h3>
                    <p><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                </div>
                <div class="info-group">
                    <h3>Phone</h3>
                    <p><?php echo $doctor['phone'] ? htmlspecialchars($doctor['phone']) : 'N/A'; ?></p>
                </div>
                <div class="info-group">
                    <h3>Email</h3>
                    <p><?php echo $doctor['email'] ? htmlspecialchars($doctor['email']) : 'N/A'; ?></p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>Upcoming Appointments</h2>
            <?php if ($upcoming_visits->rowCount() > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Appointment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($visit = $upcoming_visits->fetch()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($visit['visit_date'])); ?></td>
                            <td class="status-<?php echo strtolower($visit['status']); ?>">
                                <?php echo htmlspecialchars($visit['status']); ?>
                            </td>
                            <td class="action-links">
                                <a href="visit_details.php?id=<?php echo $visit['visit_id']; ?>">View</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No upcoming appointments scheduled for this doctor.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>