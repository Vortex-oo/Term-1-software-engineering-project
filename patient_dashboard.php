<?php
require_once 'config.php';
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'patient') {
    redirect('login.php');
}

$patient_id = $_SESSION['id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="navbar">
    <a href="#">Dashboard</a>
    <a href="logout.php" class="right">Logout</a>
</div>
<div class="container">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
    
    <h3>Your Appointments</h3>
    <?php
    $sql = "SELECT a.id, u.username as doctor_name, dp.specialty, a.appointment_time, a.status 
            FROM appointments a 
            JOIN users u ON a.doctor_id = u.id 
            JOIN doctors_profiles dp ON u.id = dp.user_id
            WHERE a.patient_id = ? 
            ORDER BY a.appointment_time DESC";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0){
            echo "<ul>";
            while($row = $result->fetch_assoc()){
                echo "<li>Dr. " . htmlspecialchars($row['doctor_name']) . " (" . htmlspecialchars($row['specialty']) . ") on " . $row['appointment_time'] . " - Status: " . htmlspecialchars($row['status']);
                if($row['status'] == 'scheduled'){
                     echo " <a href='chat.php?id=".$row['id']."' class='btn'>Join Chat</a>";
                }
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>You have no appointments.</p>";
        }
        $stmt->close();
    }
    ?>

    <hr>
    <h3>Book a New Appointment</h3>
    <form action="book_appointment.php" method="post">
        <label>Find Doctor by Specialty:</label>
        <select name="doctor_id" required>
            <option value="">--Select a Doctor--</option>
            <?php
            $sql_doctors = "SELECT u.id, u.username, dp.specialty FROM users u JOIN doctors_profiles dp ON u.id = dp.user_id WHERE u.role = 'doctor' AND dp.approved = 1";
            $result_doctors = $conn->query($sql_doctors);
            while($doctor = $result_doctors->fetch_assoc()){
                echo "<option value='".$doctor['id']."'>Dr. ".htmlspecialchars($doctor['username'])." (".htmlspecialchars($doctor['specialty']).")</option>";
            }
            ?>
        </select>
        <label>Appointment Time:</label>
        <input type="datetime-local" name="appointment_time" required>
        <input type="submit" value="Book Appointment">
    </form>
</div>
</body>
</html>
