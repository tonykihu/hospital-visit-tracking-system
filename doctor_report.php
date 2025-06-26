<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if doctor ID is provided
if (!isset($_GET['id'])) {
    header("Location: reports.php");
    exit();
}

$doctor_id = $_GET['id'];

// Get doctor details
try {
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();

    if (!$doctor) {
        header("Location: reports.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get doctor statistics
try {
    // General stats
    $stats = $pdo->prepare(
        "SELECT 
            COUNT(*) AS total_visits,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) AS scheduled,
            SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled,
            MIN(visit_date) AS first_visit,
            MAX(visit_date) AS last_visit
         FROM visits 
         WHERE doctor_id = ?"
    );
    $stats->execute([$doctor_id]);
    $stats = $stats->fetch();

    // Monthly visits
    $monthly_visits = $pdo->prepare(
        "SELECT COUNT(*) AS count, DATE_FORMAT(visit_date, '%Y-%m') AS month 
         FROM visits 
         WHERE doctor_id = ?
         GROUP BY DATE_FORMAT(visit_date, '%Y-%m') 
         ORDER BY month DESC 
         LIMIT 12"
    );
    $monthly_visits->execute([$doctor_id]);
    $monthly_visits = $monthly_visits->fetchAll();

    // Recent visits
    $recent_visits = $pdo->prepare(
        "SELECT v.*, p.first_name, p.last_name 
         FROM visits v
         JOIN patients p ON v.patient_id = p.patient_id
         WHERE v.doctor_id = ?
         ORDER BY v.visit_date DESC
         LIMIT 10"
    );
    $recent_visits->execute([$doctor_id]);
    $recent_visits = $recent_visits->fetchAll();

    // Most common patients
    $common_patients = $pdo->prepare(
        "SELECT p.patient_id, p.first_name, p.last_name, COUNT(*) AS visit_count
         FROM visits v
         JOIN patients p ON v.patient_id = p.patient_id
         WHERE v.doctor_id = ?
         GROUP BY p.patient_id, p.first_name, p.last_name
         ORDER BY visit_count DESC
         LIMIT 5"
    );
    $common_patients->execute([$doctor_id]);
    $common_patients = $common_patients->fetchAll();

} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Report | Hospital System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Use the same CSS as reports.php for consistency */
        :root {
            --primary: #2a9d8f;
            --primary-light: rgba(42, 157, 143, 0.1);
            --primary-dark: #1d7874;
            --secondary: #e9c46a;
            --danger: #e76f51;
            --warning: #ffc107;
            --success: #28a745;
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
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        h1 {
            color: var(--primary-dark);
            margin: 0;
            font-size: 1.8rem;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: 1px solid var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-light);
        }
        
        .doctor-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .doctor-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--primary-dark);
        }
        
        .doctor-info h2 {
            margin: 0;
            color: var(--primary-dark);
            font-size: 1.8rem;
        }
        
        .doctor-specialty {
            color: var(--gray);
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .report-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .section-header h2 {
            color: var(--primary-dark);
            margin: 0;
            font-size: 1.4rem;
        }
        
        .chart-container {
            position: relative;
            height: 350px;
            margin-bottom: 25px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
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
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }
        
        .badge-warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }
        
        .badge-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }
        
        .patient-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            color: var(--primary-dark);
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            color: var(--gray);
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .doctor-header {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-title">
                <i class="fas fa-user-md" style="font-size: 1.8rem; color: var(--primary);"></i>
                <h1>Doctor Performance Report</h1>
            </div>
            <a href="reports.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
        </header>
        
        <div class="doctor-header">
            <div class="doctor-avatar">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="doctor-info">
                <h2>Dr. <?php echo htmlspecialchars($doctor['first_name']) . ' ' . htmlspecialchars($doctor['last_name']); ?></h2>
                <div class="doctor-specialty">
                    <i class="fas fa-certificate"></i> <?php echo htmlspecialchars($doctor['specialization']); ?>
                </div>
                <div style="margin-top: 10px;">
                    <span class="badge badge-success" style="margin-right: 8px;">
                        <i class="fas fa-calendar-check"></i> <?php echo $stats['completed']; ?> Completed Visits
                    </span>
                    <span class="badge badge-warning">
                        <i class="fas fa-calendar-alt"></i> <?php echo $stats['scheduled']; ?> Scheduled Visits
                    </span>
                </div>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_visits']; ?></div>
                <div class="stat-label">Total Visits</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_visits'] > 0 ? round(($stats['completed']/$stats['total_visits'])*100, 1) : 0; ?>%</div>
                <div class="stat-label">Completion Rate</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['first_visit'] ? date('M Y', strtotime($stats['first_visit'])) : 'N/A'; ?></div>
                <div class="stat-label">First Visit</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['last_visit'] ? date('M Y', strtotime($stats['last_visit'])) : 'N/A'; ?></div>
                <div class="stat-label">Last Visit</div>
            </div>
        </div>
        
        <div class="report-section">
            <div class="section-header">
                <i class="fas fa-chart-line" style="color: var(--primary); font-size: 1.4rem;"></i>
                <h2>Monthly Visits</h2>
            </div>
            
            <?php if (count($monthly_visits) > 0): ?>
                <div class="chart-container">
                    <canvas id="monthlyVisitsChart"></canvas>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px; color: var(--gray);"></i>
                    <p>No visit data available for this doctor</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="report-section">
            <div class="section-header">
                <i class="fas fa-procedures" style="color: var(--primary); font-size: 1.4rem;"></i>
                <h2>Recent Visits</h2>
            </div>
            
            <?php if (count($recent_visits) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_visits as $visit): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center;">
                                    <div class="patient-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <?php echo htmlspecialchars($visit['first_name']) . ' ' . htmlspecialchars($visit['last_name']); ?>
                                </div>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($visit['visit_date'])); ?></td>
                            <td>
                                <?php if ($visit['status'] == 'Completed'): ?>
                                    <span class="badge badge-success">Completed</span>
                                <?php elseif ($visit['status'] == 'Scheduled'): ?>
                                    <span class="badge badge-warning">Scheduled</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Cancelled</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="visit_details.php?id=<?php echo $visit['visit_id']; ?>" style="color: var(--primary); text-decoration: none;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px; color: var(--gray);"></i>
                    <p>No recent visits found for this doctor</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="report-section">
            <div class="section-header">
                <i class="fas fa-user-friends" style="color: var(--primary); font-size: 1.4rem;"></i>
                <h2>Most Common Patients</h2>
            </div>
            
            <?php if (count($common_patients) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Visits</th>
                            <th>Last Visit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($common_patients as $patient): 
                            // Get last visit date for this patient
                            $last_visit = $pdo->prepare(
                                "SELECT MAX(visit_date) AS last_visit 
                                 FROM visits 
                                 WHERE doctor_id = ? AND patient_id = ?"
                            );
                            $last_visit->execute([$doctor_id, $patient['patient_id']]);
                            $last_visit = $last_visit->fetch();
                        ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center;">
                                    <div class="patient-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <?php echo htmlspecialchars($patient['first_name']) . ' ' . htmlspecialchars($patient['last_name']); ?>
                                </div>
                            </td>
                            <td><?php echo $patient['visit_count']; ?></td>
                            <td><?php echo $last_visit['last_visit'] ? date('M j, Y', strtotime($last_visit['last_visit'])) : 'N/A'; ?></td>
                            <td>
                                <a href="patient_report.php?id=<?php echo $patient['patient_id']; ?>" style="color: var(--primary); text-decoration: none;">
                                    <i class="fas fa-chart-line"></i> View Report
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px; color: var(--gray);"></i>
                    <p>No patient data available for this doctor</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (count($monthly_visits) > 0): ?>
    <script>
        // Monthly Visits Line Chart
        const monthlyCtx = document.getElementById('monthlyVisitsChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php foreach(array_reverse($monthly_visits) as $visit): ?>
                        '<?php echo date('M Y', strtotime($visit['month'] . '-01')); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Visits',
                    data: [
                        <?php foreach(array_reverse($monthly_visits) as $visit): ?>
                            <?php echo $visit['count']; ?>,
                        <?php endforeach; ?>
                    ],
                    borderColor: '#2a9d8f',
                    backgroundColor: 'rgba(42, 157, 143, 0.1)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#2a9d8f',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Visits (Last 12 Months)',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>