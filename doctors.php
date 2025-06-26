<?php
session_start();
require 'config.php';

// Redirect if not logged in
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_doctor'])) {
        // Add new doctor
        $stmt = $pdo->prepare("INSERT INTO doctors (first_name, last_name, specialization, phone, email) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            htmlspecialchars($_POST['first_name']),
            htmlspecialchars($_POST['last_name']),
            htmlspecialchars($_POST['specialization']),
            htmlspecialchars($_POST['phone']),
            htmlspecialchars($_POST['email'])
        ]);
        header("Location: doctors.php?success=added");
        exit();
    }
    elseif (isset($_POST['delete_doctor'])) {
        // Delete doctor
        $doctor_id = $_POST['doctor_id'];
        
        try {
            $pdo->beginTransaction();
            
            // First check if doctor has any visits
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM visits WHERE doctor_id = ?");
            $stmt->execute([$doctor_id]);
            $visit_count = $stmt->fetchColumn();
            
            if ($visit_count > 0) {
                // Doctor has visits, don't delete
                header("Location: doctors.php?error=has_visits");
                exit();
            }
            
            // Delete doctor if no visits
            $stmt = $pdo->prepare("DELETE FROM doctors WHERE doctor_id = ?");
            $stmt->execute([$doctor_id]);
            
            $pdo->commit();
            header("Location: doctors.php?success=deleted");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Error deleting doctor: " . $e->getMessage());
        }
    }
}

// Get all doctors
$doctors = $pdo->query("SELECT * FROM doctors ORDER BY last_name, first_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management</title>
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
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
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
        
        .action-links {
            display: flex;
            gap: 10px;
        }
        
        .action-links a, .action-links button {
            color: var(--primary);
            text-decoration: none;
            border: none;
            background: none;
            cursor: pointer;
            padding: 0;
            font-size: inherit;
        }
        
        .action-links .delete-btn {
            color: var(--danger);
        }
        
        .action-links a:hover, .action-links button:hover {
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
        
        .error-message {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Doctor Management</h1>
            <a href="dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </header>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <?php 
                if ($_GET['success'] === 'added') {
                    echo "Doctor has been successfully added.";
                } elseif ($_GET['success'] === 'deleted') {
                    echo "Doctor has been successfully deleted.";
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error']) && $_GET['error'] === 'has_visits'): ?>
            <div class="error-message">
                Cannot delete doctor because they have associated visits. Please reassign or delete visits first.
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2><i class="fas fa-user-md"></i> Add New Doctor</h2>
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="specialization">Specialization</label>
                    <input type="text" class="form-control" id="specialization" name="specialization" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                </div>
                
                <button type="submit" class="btn" name="add_doctor">
                    <i class="fas fa-save"></i> Add Doctor
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-list"></i> Doctor List</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Specialization</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($doctors as $doctor): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($doctor['doctor_id']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                        <td>
                            <?php if ($doctor['phone']): ?>
                                <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($doctor['phone']); ?></div>
                            <?php endif; ?>
                            <?php if ($doctor['email']): ?>
                                <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($doctor['email']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-links">
                                <a href="doctor_details.php?id=<?php echo $doctor['doctor_id']; ?>">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="edit_doctor.php?id=<?php echo $doctor['doctor_id']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button class="delete-btn" onclick="confirmDelete(<?php echo $doctor['doctor_id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h2>
            <p>Are you sure you want to delete this doctor record? This action cannot be undone.</p>
            <form id="deleteForm" method="post">
                <input type="hidden" name="doctor_id" id="deleteDoctorId">
                <input type="hidden" name="delete_doctor" value="1">
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Delete confirmation modal functions
        function confirmDelete(doctorId) {
            document.getElementById('deleteDoctorId').value = doctorId;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('deleteModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>