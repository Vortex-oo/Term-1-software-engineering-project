<?php
require_once 'config.php';
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'doctor') {
    redirect('login.php');
}

$doctor_id = $_SESSION['id'];

// Get the view from URL, default to 'scheduled'
$view = isset($_GET['view']) ? $_GET['view'] : 'scheduled';

// Base SQL query
$sql = "SELECT a.id, u.username as patient_name, a.appointment_time, a.status 
        FROM appointments a 
        JOIN users u ON a.patient_id = u.id 
        WHERE a.doctor_id = ?";

// Append status condition based on the view
if ($view != 'all') {
    $sql .= " AND a.status = ?";
}

$sql .= " ORDER BY a.appointment_time DESC";

$appointments = [];
if($stmt = $conn->prepare($sql)){
    // Bind parameters based on the view
    if ($view != 'all') {
        $stmt->bind_param("is", $doctor_id, $view);
    } else {
        $stmt->bind_param("i", $doctor_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $appointments[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Additional styles for the dashboard tabs and table */
        .tab-nav {
            border-bottom: 2px solid #ccc;
            margin-bottom: 20px;
        }
        .tab-link {
            display: inline-block;
            padding: 10px 15px;
            border: 1px solid transparent;
            border-bottom: none;
            cursor: pointer;
            text-decoration: none;
            color: #0056b3;
            font-size: 1.1em;
            margin-bottom: -2px;
        }
        .tab-link.active {
            border-color: #ccc #ccc #fff #ccc;
            background-color: #fff;
            border-radius: 5px 5px 0 0;
            font-weight: bold;
        }
        .appointment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .appointment-table th, .appointment-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .appointment-table th {
            background-color: #f2f2f2;
            color: #333;
        }
        .appointment-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .appointment-table tr:hover {
            background-color: #f1f1f1;
        }
        .no-appointments {
            text-align: center;
            padding: 20px;
            color: #777;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
<div class="navbar">
    <a href="doctor_dashboard.php">Dashboard</a>
    <a href="logout.php" class="right">Logout</a>
</div>
<div class="container">
    <h2>Welcome, Dr. <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
    
    <div class="tab-nav">
        <a href="doctor_dashboard.php?view=scheduled" class="tab-link <?php if($view == 'scheduled') echo 'active'; ?>">Upcoming</a>
        <a href="doctor_dashboard.php?view=completed" class="tab-link <?php if($view == 'completed') echo 'active'; ?>">Completed</a>
        <a href="doctor_dashboard.php?view=all" class="tab-link <?php if($view == 'all') echo 'active'; ?>">All Appointments</a>
    </div>

    <div class="appointment-list">
        <?php if (!empty($appointments)): ?>
            <table class="appointment-table">
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Appointment Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                            <td><?php echo date('F j, Y, g:i a', strtotime($row['appointment_time'])); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($row['status'])); ?></td>
                            <td>
                                <?php if ($row['status'] == 'scheduled'): ?>
                                    <a href='chat.php?id=<?php echo $row['id']; ?>' class='btn'>Start Chat</a>
                                <?php else: ?>
                                    <span>N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-appointments">You have no <?php echo htmlspecialchars($view); ?> appointments.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
