<?php
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
    // Common fields for both patient and doctor
    $role = $_POST['role'];
    $fullName = htmlspecialchars(trim($_POST['fullName']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $birthday = !empty($_POST['birthday']) ? $_POST['birthday'] : null;
    $gender = isset($_POST['gender']) ? $_POST['gender'] : null;
    $password = $_POST['password'];
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    if ($role == "doctor") {
        // Doctor-specific fields
        $specialization = htmlspecialchars(trim($_POST['specialization']));
        $licenseNumber = htmlspecialchars(trim($_POST['licenseNumber']));
        $bio = !empty($_POST['bio']) ? htmlspecialchars(trim($_POST['bio'])) : null;
        $experience = !empty($_POST['experience']) ? intval($_POST['experience']) : null;
        $consultationFee = !empty($_POST['consultationFee']) ? floatval($_POST['consultationFee']) : 0.00;
        
        // Insert into doctor table
        $stmt = $conn->prepare("INSERT INTO doctor (DoctorName, DoctorEmail, DoctorPhone, DoctorGender, Specialization, LicenseNumber, Bio, Experience, ConsultationFee, DoctorPassword) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssids", $fullName, $email, $phone, $gender, $specialization, $licenseNumber, $bio, $experience, $consultationFee, $hashed_password);
        
        if ($stmt->execute()) {
            $doctor_id = $stmt->insert_id;
            // Create a default clinic entry for the doctor
            $clinic_stmt = $conn->prepare("INSERT INTO clinic (ClinicName, ClinicAddress, ClinicPhone, DoctorID) VALUES (?, ?, ?, ?)");
            $default_clinic_name = $fullName . "'s Clinic";
            $default_address = "Address not specified";
            $default_phone = $phone;
            $clinic_stmt->bind_param("sssi", $default_clinic_name, $default_address, $default_phone, $doctor_id);
            $clinic_stmt->execute();
            $clinic_stmt->close();
            
            header("Location: signup_success.html");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
    } else {
        // Insert into patient table
        $stmt = $conn->prepare("INSERT INTO patient (PatientName, PatientEmail, PatientPhone, PatientBday, PatientGender, PatientPassword) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $fullName, $email, $phone, $birthday, $gender, $hashed_password);
        
        if ($stmt->execute()) {
            $patient_id = $stmt->insert_id;
            // Create a default patient detail entry
            $detail_stmt = $conn->prepare("INSERT INTO patientdetail (PatientID) VALUES (?)");
            $detail_stmt->bind_param("i", $patient_id);
            $detail_stmt->execute();
            $detail_stmt->close();
            
            header("Location: signup_success.html");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
    }
    
    $stmt->close();
}

$conn->close();
?>