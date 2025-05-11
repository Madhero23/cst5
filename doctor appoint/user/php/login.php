<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "akasi";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    // Validate inputs
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }
    
    // First check if it's a doctor
    $stmt = $conn->prepare("SELECT DoctorID, DoctorName, DoctorPassword FROM doctor WHERE DoctorEmail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $doctor_result = $stmt->get_result();
    
    if ($doctor_result->num_rows == 1) {
        $doctor = $doctor_result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $doctor['DoctorPassword'])) {
            // Set session variables
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $doctor['DoctorID'];
            $_SESSION['user_name'] = $doctor['DoctorName'];
            $_SESSION['user_role'] = 'doctor';
            
            // Redirect to doctor dashboard
            header("Location: ../html/doctor_home.html");
            exit();
        } else {
            echo "Invalid password";
        }
    } else {
        // If not a doctor, check if it's a patient
        $stmt = $conn->prepare("SELECT PatientID, PatientName, PatientPassword FROM patient WHERE PatientEmail = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $patient_result = $stmt->get_result();
        
        if ($patient_result->num_rows == 1) {
            $patient = $patient_result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $patient['PatientPassword'])) {
                // Set session variables
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $patient['PatientID'];
                $_SESSION['user_name'] = $patient['PatientName'];
                $_SESSION['user_role'] = 'patient';
                
                // Redirect to patient dashboard
                header("Location: ../html/patient_home.html");
                exit();
            } else {
                echo "Invalid password";
            }
        } else {
            echo "User not found";
        }
    }
    
    $stmt->close();
}

$conn->close();
?>