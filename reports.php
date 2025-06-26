<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user details
try {
    $stmt = $pdo->prepare("SELECT username, role FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get report data
$current_month = date('Y-m');
$current_year = date('Y');

try {
    // Monthly visits
    $monthly_visits = $pdo->query(
        "SELECT COUNT(*) AS count, DATE_FORMAT(visit_date, '%Y-%m') AS month 
         FROM visits 
         GROUP BY DATE_FORMAT(visit_date, '%Y-%m') 
         ORDER BY month DESC 
         LIMIT 12"
    )->fetchAll();

    // Yearly visits
    $yearly_visits = $pdo->query(
        "SELECT COUNT(*) AS count, YEAR(visit_date) AS year 
         FROM visits 
         GROUP BY YEAR(visit_date) 
         ORDER BY year DESC 
         LIMIT 5"
    )->fetchAll();

    // Doctor performance
    $doctor_performance = $pdo->query(
        "SELECT d.doctor_id, d.first_name, d.last_name, 
                COUNT(v.visit_id) AS visit_count,
                SUM(CASE WHEN v.status = 'Completed' THEN 1 ELSE 0 END) AS completed_count
         FROM doctors d
         LEFT JOIN visits v ON d.doctor_id = v.doctor_id
         GROUP BY d.doctor_id, d.first_name, d.last_name
         ORDER BY visit_count DESC"
    )->fetchAll();

    // Patient demographics - Corrected query
    $patient_demographics = $pdo->query(
        "SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) AS male,
            SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) AS female,
            SUM(CASE WHEN gender = 'Other' THEN 1 ELSE 0 END) AS other,
            FLOOR(AVG(TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()))) AS avg_age
         FROM patients"
    )->fetch();

    // Current month stats
    $current_month_stats = $pdo->prepare(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) AS scheduled,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled
         FROM visits
         WHERE DATE_FORMAT(visit_date, '%Y-%m') = ?"
    );
    $current_month_stats->execute([$current_month]);
    $current_month_stats = $current_month_stats->fetch();

} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
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
        
        .user-role {
            color: var(--gray);
            font-size: 0.9rem;
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
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card h3 {
            color: var(--gray);
            margin-bottom: 10px;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }
        
        .stat-description {
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
            position: sticky;
            top: 0;
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
        
        .badge-primary {
            background-color: var(--primary-light);
            color: var(--primary-dark);
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
        
        .demographic-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .demographic-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-top: 4px solid var(--primary);
        }
        
        .demographic-card h3 {
            color: var(--gray);
            font-size: 0.95rem;
            margin-bottom: 10px;
        }
        
        .demographic-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }
        
        .demographic-percentage {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .action-link {
            color: var(--primary);
            text-decoration: none;
            margin-right: 15px;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .action-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .action-link i {
            margin-right: 5px;
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            color: var(--gray);
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .demographic-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-title">
                <i class="fas fa-chart-bar" style="font-size: 1.8rem; color: var(--primary);"></i>
                <div>
                    <h1>Hospital Reports</h1>
                    <div class="user-info">
                        <span class="user-role"><?php echo htmlspecialchars($user['role']); ?> Access</span>
                    </div>
                </div>
            </div>
            <a href="dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </header>
        
        <div class="dashboard-grid">
            <div class="stat-card">
                <h3><i class="fas fa-calendar-alt" style="margin-right: 8px;"></i> Current Month Visits</h3>
                <div class="stat-value"><?php echo $current_month_stats['total']; ?></div>
                <p class="stat-description"><?php echo date('F Y'); ?></p>
                <div style="margin-top: 10px;">
                    <span class="badge badge-success" style="margin-right: 8px;">
                        <?php echo $current_month_stats['completed']; ?> Completed
                    </span>
                    <span class="badge badge-warning" style="margin-right: 8px;">
                        <?php echo $current_month_stats['scheduled']; ?> Scheduled
                    </span>
                    <span class="badge badge-danger">
                        <?php echo $current_month_stats['cancelled']; ?> Cancelled
                    </span>
                </div>
            </div>
            
            <div class="stat-card">
                <h3><i class="fas fa-check-circle" style="margin-right: 8px;"></i> Completion Rate</h3>
                <div class="stat-value"><?php echo $current_month_stats['total'] > 0 ? round(($current_month_stats['completed']/$current_month_stats['total'])*100, 1) : 0; ?>%</div>
                <p class="stat-description">of visits completed this month</p>
                <div style="margin-top: 15px; height: 6px; background: #eee; border-radius: 3px;">
                    <div style="width: <?php echo $current_month_stats['total'] > 0 ? round(($current_month_stats['completed']/$current_month_stats['total'])*100, 1) : 0; ?>%; height: 100%; background: var(--success); border-radius: 3px;"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <h3><i class="fas fa-user-injured" style="margin-right: 8px;"></i> Average Patient Age</h3>
                <div class="stat-value"><?php echo round($patient_demographics['avg_age'], 1); ?></div>
                <p class="stat-description">years old</p>
                <div style="margin-top: 15px;">
                    <span class="badge badge-primary" style="margin-right: 8px;">
                        <?php echo $patient_demographics['total']; ?> Total Patients
                    </span>
                </div>
            </div>
        </div>
        
        <div class="report-section">
            <div class="section-header">
                <i class="fas fa-users" style="color: var(--primary); font-size: 1.4rem;"></i>
                <h2>Patient Demographics</h2>
            </div>
            
            <div class="demographic-grid">
                <div class="demographic-card">
                    <h3>Male Patients</h3>
                    <div class="demographic-value"><?php echo $patient_demographics['male']; ?></div>
                    <div class="demographic-percentage">
                        <?php echo $patient_demographics['total'] > 0 ? round(($patient_demographics['male']/$patient_demographics['total'])*100, 1) : 0; ?>% of total
                    </div>
                </div>
                
                <div class="demographic-card">
                    <h3>Female Patients</h3>
                    <div class="demographic-value"><?php echo $patient_demographics['female']; ?></div>
                    <div class="demographic-percentage">
                        <?php echo $patient_demographics['total'] > 0 ? round(($patient_demographics['female']/$patient_demographics['total'])*100, 1) : 0; ?>% of total
                    </div>
                </div>
                
                <div class="demographic-card">
                    <h3>Other</h3>
                    <div class="demographic-value"><?php echo $patient_demographics['other']; ?></div>
                    <div class="demographic-percentage">
                        <?php echo $patient_demographics['total'] > 0 ? round(($patient_demographics['other']/$patient_demographics['total'])*100, 1) : 0; ?>% of total
                    </div>
                </div>
                
                <div class="demographic-card">
                    <h3>Average Age</h3>
                    <div class="demographic-value"><?php echo round($patient_demographics['avg_age'], 1); ?></div>
                    <div class="demographic-percentage">years</div>
                </div>
            </div>
            
            <div class="chart-container">
                <canvas id="genderChart"></canvas>
            </div>
        </div>
        
        <div class="report-section">
            <div class="section-header">
                <i class="fas fa-calendar-alt" style="color: var(--primary); font-size: 1.4rem;"></i>
                <h2>Visits Overview</h2>
            </div>
            
            <div class="chart-container">
                <canvas id="monthlyVisitsChart"></canvas>
            </div>
            
            <div class="chart-container">
                <canvas id="yearlyVisitsChart"></canvas>
            </div>
        </div>
        
        <div class="report-section">
            <div class="section-header">
                <i class="fas fa-user-md" style="color: var(--primary); font-size: 1.4rem;"></i>
                <h2>Doctor Performance</h2>
            </div>
            
            <?php if (count($doctor_performance) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Total Visits</th>
                            <th>Completed</th>
                            <th>Completion Rate</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctor_performance as $doctor): ?>
                        <tr>
                            <td><strong>Dr. <?php echo htmlspecialchars($doctor['first_name']) . ' ' . htmlspecialchars($doctor['last_name']); ?></strong></td>
                            <td><?php echo $doctor['visit_count']; ?></td>
                            <td><?php echo $doctor['completed_count']; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span><?php echo $doctor['visit_count'] > 0 ? round(($doctor['completed_count']/$doctor['visit_count'])*100, 1) : 0; ?>%</span>
                                    <div style="flex-grow: 1; height: 6px; background: #eee; border-radius: 3px;">
                                        <div style="width: <?php echo $doctor['visit_count'] > 0 ? round(($doctor['completed_count']/$doctor['visit_count'])*100, 1) : 0; ?>%; height: 100%; background: var(--success); border-radius: 3px;"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="doctor_report.php?id=<?php echo $doctor['doctor_id']; ?>" class="action-link">
                                    <i class="fas fa-chart-line"></i> Details
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px; color: var(--gray);"></i>
                    <p>No doctor performance data available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Gender Distribution Pie Chart
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        const genderChart = new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female', 'Other'],
                datasets: [{
                    data: [
                        <?php echo $patient_demographics['male']; ?>,
                        <?php echo $patient_demographics['female']; ?>,
                        <?php echo $patient_demographics['other']; ?>
                    ],
                    backgroundColor: [
                        '#3498db',
                        '#e83e8c',
                        '#6c757d'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Patient Gender Distribution',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '70%'
            }
        });

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

        // Yearly Visits Bar Chart
        const yearlyCtx = document.getElementById('yearlyVisitsChart').getContext('2d');
        const yearlyChart = new Chart(yearlyCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php foreach(array_reverse($yearly_visits) as $visit): ?>
                        '<?php echo $visit['year']; ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Visits',
                    data: [
                        <?php foreach(array_reverse($yearly_visits) as $visit): ?>
                            <?php echo $visit['count']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(42, 157, 143, 0.7)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Yearly Visits',
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
</body>
</html>