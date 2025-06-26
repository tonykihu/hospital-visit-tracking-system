<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Add new patient
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_patient'])) {
        $stmt = $pdo->prepare("INSERT INTO patients (first_name, last_name, date_of_birth, gender, address, phone, email, insurance_info) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            htmlspecialchars($_POST['first_name']),
            htmlspecialchars($_POST['last_name']),
            $_POST['date_of_birth'],
            $_POST['gender'],
            htmlspecialchars($_POST['address']),
            htmlspecialchars($_POST['phone']),
            htmlspecialchars($_POST['email']),
            htmlspecialchars($_POST['insurance_info'])
        ]);
        header("Location: patients.php?success=added");
        exit();
    }
    elseif (isset($_POST['delete_patient'])) {
        $patient_id = $_POST['patient_id'];
        
        // First delete related visits to maintain referential integrity
        try {
            $pdo->beginTransaction();
            
            // Delete visits first
            $stmt = $pdo->prepare("DELETE FROM visits WHERE patient_id = ?");
            $stmt->execute([$patient_id]);
            
            // Then delete the patient
            $stmt = $pdo->prepare("DELETE FROM patients WHERE patient_id = ?");
            $stmt->execute([$patient_id]);
            
            $pdo->commit();
            header("Location: patients.php?success=deleted");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Error deleting patient: " . $e->getMessage());
        }
    }
}

// Get all patients
$patients = $pdo->query("SELECT * FROM patients ORDER BY last_name, first_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management</title>
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
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .success-message {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
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
            <h1>Patient Management</h1>
            <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        </header>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <?php 
                if ($_GET['success'] === 'added') {
                    echo "Patient record has been successfully added.";
                } elseif ($_GET['success'] === 'deleted') {
                    echo "Patient record has been successfully deleted.";
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Add New Patient</h2>
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
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select class="form-control" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea class="form-control" id="address" name="address"></textarea>
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
                
                <div class="form-group">
                    <label for="insurance_info">Insurance Information</label>
                    <textarea class="form-control" id="insurance_info" name="insurance_info"></textarea>
                </div>
                
                <button type="submit" class="btn" name="add_patient">Add Patient</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Patient List</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>DOB</th>
                        <th>Gender</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($patient['patient_id']); ?></td>
                        <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($patient['date_of_birth']); ?></td>
                        <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                        <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                        <td>
                            <div class="action-links">
                                <a href="patient_details.php?id=<?php echo $patient['patient_id']; ?>">View</a>
                                <a href="edit_patient.php?id=<?php echo $patient['patient_id']; ?>">Edit</a>
                                <button class="delete-btn" onclick="confirmDelete(<?php echo $patient['patient_id']; ?>)">Delete</button>
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
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to delete this patient record? This action cannot be undone.</p>
            <form id="deleteForm" method="post">
                <input type="hidden" name="patient_id" id="deletePatientId">
                <input type="hidden" name="delete_patient" value="1">
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Delete confirmation modal functions
        function confirmDelete(patientId) {
            document.getElementById('deletePatientId').value = patientId;
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