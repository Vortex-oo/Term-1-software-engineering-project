<?php
require_once 'config.php';
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'patient') {
    redirect('login.php');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = $_SESSION['id'];
    $doctor_id = $_POST['doctor_id'];
    $appointment_time = $_POST['appointment_time'];

    // For simplicity, we assume premium plan. You can add logic here to check subscription.
    // For example: check if user has an active premium subscription from the 'subscriptions' table.
    
    $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_time) VALUES (?, ?, ?)";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("iis", $patient_id, $doctor_id, $appointment_time);
        if($stmt->execute()){
            redirect('patient_dashboard.php');
        } else {
            echo "Error booking appointment.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>
