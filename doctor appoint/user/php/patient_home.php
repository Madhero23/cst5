<?php
session_start();

// Check if user is logged in as patient
if (!isset($_SESSION['loggedin']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

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

// Get patient data
$patient_id = $_SESSION['user_id'];
$patient_stmt = $conn->prepare("SELECT PatientName, PatientEmail, PatientPhone FROM patient WHERE PatientID = ?");
$patient_stmt->bind_param("i", $patient_id);
$patient_stmt->execute();
$patient_result = $patient_stmt->get_result();
$patient = $patient_result->fetch_assoc();
$patient_stmt->close();

// Get upcoming appointments
$appointments = [];
$appt_stmt = $conn->prepare("SELECT a.AppointmentID, a.AppointmentTime, a.Status, d.DoctorName, d.Specialization 
                            FROM appointment a 
                            JOIN doctor d ON a.DoctorID = d.DoctorID 
                            WHERE a.PatientID = ? AND a.AppointmentTime > NOW() 
                            ORDER BY a.AppointmentTime ASC LIMIT 3");
$appt_stmt->bind_param("i", $patient_id);
$appt_stmt->execute();
$appt_result = $appt_stmt->get_result();
while ($row = $appt_result->fetch_assoc()) {
    $appointments[] = $row;
}
$appt_stmt->close();

$conn->close();

// Load HTML content
$html = file_get_contents('../html/patient_home.html');

// Replace placeholders with dynamic content
$replacements = [
    '<!-- USER_PROFILE -->' => '
        <div class="account-dropdown">
            <div class="user-avatar">'.strtoupper(substr($patient['PatientName'], 0, 1)).'</div>
            <div class="dropdown-content">
                <a href="dashboard.php">Dashboard</a>
                <a href="settings.php">Settings</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>',
    '<!-- WELCOME_MESSAGE -->' => '<div class="welcome-message">Welcome, '.htmlspecialchars($patient['PatientName']).'!</div>',
    '<!-- UPCOMING_APPOINTMENTS -->' => !empty($appointments) ? 
        '<div class="appointments-summary">
            <h3>Your Upcoming Appointments</h3>
            '.array_reduce($appointments, function($carry, $appt) {
                return $carry . '
                <div class="appointment-card">
                    <div class="appt-date">'.date('M j, Y g:i A', strtotime($appt['AppointmentTime'])).'</div>
                    <div class="appt-doctor">Dr. '.htmlspecialchars($appt['DoctorName']).' ('.htmlspecialchars($appt['Specialization']).')</div>
                    <div class="appt-status '.strtolower($appt['Status']).'">'.$appt['Status'].'</div>
                </div>';
            }, '') . '
            <a href="my_appointments.php" class="view-all">View All Appointments</a>
        </div>' : 
        '<div class="no-appointments">
            <p>You don\'t have any upcoming appointments</p>
            <a href="find_doctors.php" class="btn">Book an Appointment</a>
        </div>'
];

foreach ($replacements as $placeholder => $content) {
    $html = str_replace($placeholder, $content, $html);
}

echo $html;
?>