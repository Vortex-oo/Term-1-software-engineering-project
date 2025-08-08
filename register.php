<?php
require_once 'config.php';
$username = $email = $password = $role = "";
$username_err = $email_err = $password_err = $role_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic validation
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else {
        $username = trim($_POST["username"]);
    }

    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } else {
        // Check if email is already taken
        $sql = "SELECT id FROM users WHERE email = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("s", $param_email);
            $param_email = trim($_POST["email"]);
            if($stmt->execute()){
                $stmt->store_result();
                if($stmt->num_rows == 1){
                    $email_err = "This email is already taken.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }

    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    if(empty(trim($_POST["role"]))){
        $role_err = "Please select a role.";
    } else {
        $role = trim($_POST["role"]);
    }

    // If no errors, insert into database
    if(empty($username_err) && empty($email_err) && empty($password_err) && empty($role_err)){
        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                if ($role == 'doctor') {
                    $user_id = $stmt->insert_id;
                    $specialty = trim($_POST['specialty']);
                    
                    // MODIFIED: This query now sets 'approved' to 1 automatically
                    $sql_doctor = "INSERT INTO doctors_profiles (user_id, specialty, approved) VALUES (?, ?, 1)";
                    
                    if($stmt_doctor = $conn->prepare($sql_doctor)){
                        $stmt_doctor->bind_param("is", $user_id, $specialty);
                        $stmt_doctor->execute();
                        $stmt_doctor->close();
                    }
                }
                // Redirect to login page after successful registration
                redirect("login.php");
            } else {
                echo "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>Register</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div>
            <label>Username</label>
            <input type="text" name="username" required>
            <span class="error"><?php echo $username_err; ?></span>
        </div>
        <div>
            <label>Email</label>
            <input type="email" name="email" required>
            <span class="error"><?php echo $email_err; ?></span>
        </div>
        <div>
            <label>Password</label>
            <input type="password" name="password" required>
            <span class="error"><?php echo $password_err; ?></span>
        </div>
        <div>
            <label>I am a:</label>
            <select name="role" id="role_select" onchange="toggleSpecialty()" required>
                <option value="">--Select Role--</option>
                <option value="patient">Patient</option>
                <option value="doctor">Doctor</option>
            </select>
            <span class="error"><?php echo $role_err; ?></span>
        </div>
        <div id="specialty_field" style="display:none;">
            <label>Specialty</label>
            <input type="text" name="specialty" required>
        </div>
        <div>
            <input type="submit" value="Register">
        </div>
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </form>
</div>
<script>
function toggleSpecialty() {
    var role = document.getElementById('role_select').value;
    var specialtyField = document.getElementById('specialty_field');
    var specialtyInput = specialtyField.querySelector('input');

    if (role === 'doctor') {
        specialtyField.style.display = 'block';
        specialtyInput.required = true;
    } else {
        specialtyField.style.display = 'none';
        specialtyInput.required = false;
    }
}
</script>
</body>
</html>
