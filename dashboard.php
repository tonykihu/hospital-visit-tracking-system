<?php
session_start();
require 'config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
require 'config.php';

// Get user details
try {
    $stmt = $pdo->prepare("SELECT username, role FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get statistics for dashboard
$patient_count = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$doctor_count = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$today_visits = $pdo->query("SELECT COUNT(*) FROM visits WHERE DATE(visit_date) = CURDATE()")->fetchColumn();
$pending_visits = $pdo->query("SELECT COUNT(*) FROM visits WHERE status = 'Scheduled'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Dashboard</title>
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

        .welcome-message h1 {
            color: var(--primary-dark);
            margin-bottom: 5px;
        }

        .user-role {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            color: var(--gray);
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .quick-actions {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 30px;
        }

        .quick-actions h2 {
            color: var(--primary-dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: var(--primary-light);
            border-radius: 5px;
            color: var(--primary-dark);
            text-decoration: none;
            text-align: center;
            transition: background 0.3s;
        }

        .action-btn i {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .action-btn:hover {
            background: rgba(42, 157, 143, 0.2);
        }

        .recent-activity {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }

        .recent-activity h2 {
            color: var(--primary-dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            color: var(--gray);
            font-weight: 600;
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

        .logout-btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--danger);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="welcome-message">
                <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?></h1>
                <p class="user-role"><?php echo htmlspecialchars($user['role']); ?> Access</p>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </header>

        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Total Patients</h3>
                <div class="stat-value"><?php echo $patient_count; ?></div>
                <p><i class="fas fa-user-injured"></i> Under care</p>
            </div>

            <div class="stat-card">
                <h3>Total Doctors</h3>
                <div class="stat-value"><?php echo $doctor_count; ?></div>
                <p><i class="fas fa-user-md"></i> On staff</p>
            </div>

            <div class="stat-card">
                <h3>Today's Appointments</h3>
                <div class="stat-value"><?php echo $today_visits; ?></div>
                <p><i class="fas fa-calendar-day"></i> Scheduled</p>
            </div>

            <div class="stat-card">
                <h3>Pending Visits</h3>
                <div class="stat-value"><?php echo $pending_visits; ?></div>
                <p><i class="fas fa-clock"></i> Awaiting</p>
            </div>
        </div>

        <div class="quick-actions">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="action-grid">
                <a href="patients.php" class="action-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Add New Patient</span>
                </a>

                <a href="visits.php" class="action-btn">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Schedule Visit</span>
                </a>

                <?php if (in_array($user['role'], ['Admin', 'Receptionist'])) { ?>
                <a href="doctors.php" class="action-btn">
                    <i class="fas fa-user-md"></i>
                    <span>Manage Doctors</span>
                </a>
                <?php } ?>

                <?php if ($user['role'] === 'Admin') { ?>
                <a href="users.php" class="action-btn">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Users</span>
                </a>
                <?php } ?>

                <a href="reports.php" class="action-btn">
                    <i class="fas fa-chart-bar"></i>
                    <span>View Reports</span>
                </a>
            </div>
        </div>

        <div class="recent-activity">
            <h2><i class="fas fa-history"></i> Recent Activity</h2>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Visit Date</th>
                        <th>Doctor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT p.first_name, p.last_name, v.visit_date,
                                        d.first_name AS doc_first, d.last_name AS doc_last, v.status
                                        FROM visits v
                                        JOIN patients p ON v.patient_id = p.patient_id
                                        JOIN doctors d ON v.doctor_id = d.doctor_id
                                        ORDER BY v.visit_date DESC LIMIT 5");
                    while ($row = $stmt->fetch()) {
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($row['visit_date'])); ?></td>
                        <td>Dr. <?php echo htmlspecialchars($row['doc_first']) . ' ' . htmlspecialchars($row['doc_last']); ?></td>
                        <td class="status-<?php echo strtolower($row['status']); ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>