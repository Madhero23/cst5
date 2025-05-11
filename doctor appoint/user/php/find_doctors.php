<?php
session_start();

// Check if user is logged in as patient
// if (!isset($_SESSION['loggedin']) {
//     header("Location: login.php");
//     exit();
// }

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

// Get filters from GET parameters
$specialization = isset($_GET['specialization']) ? $_GET['specialization'] : '';
$gender = isset($_GET['gender']) ? $_GET['gender'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'rating';

// Build base query
$query = "SELECT d.DoctorID, d.DoctorName, d.DoctorGender, d.Specialization, 
                 d.ConsultationFee, d.Bio, d.Experience,
                 c.ClinicName, c.ClinicAddress, c.ClinicPhone
          FROM doctor d
          LEFT JOIN clinic c ON d.DoctorID = c.DoctorID
          WHERE 1=1";

// Add filters
$params = [];
$types = '';

if (!empty($specialization)) {
    $query .= " AND d.Specialization = ?";
    $params[] = $specialization;
    $types .= 's';
}

if (!empty($gender)) {
    $query .= " AND d.DoctorGender = ?";
    $params[] = $gender;
    $types .= 's';
}

// Add sorting
switch ($sort) {
    case 'availability':
        // This would require appointment data to determine availability
        $query .= " ORDER BY (SELECT MIN(AppointmentTime) FROM appointment WHERE DoctorID = d.DoctorID AND Status = 'available') ASC";
        break;
    case 'price-low':
        $query .= " ORDER BY d.ConsultationFee ASC";
        break;
    case 'price-high':
        $query .= " ORDER BY d.ConsultationFee DESC";
        break;
    default:
        // Default sort by rating (you might need to add a rating field to your database)
        $query .= " ORDER BY d.DoctorID DESC"; // Temporary fallback
}

// Prepare and execute query
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$doctors = $result->fetch_all(MYSQLI_ASSOC);

// Get patient data for header
$patient_id = $_SESSION['user_id'];
$patient_stmt = $conn->prepare("SELECT PatientName FROM patient WHERE PatientID = ?");
$patient_stmt->bind_param("i", $patient_id);
$patient_stmt->execute();
$patient_result = $patient_stmt->get_result();
$patient = $patient_result->fetch_assoc();
$patient_stmt->close();

$conn->close();

// Load HTML template
$html = file_get_contents('../html/find_doctors.html');

// Generate doctor cards
$doctor_cards = '';
foreach ($doctors as $doctor) {
    $doctor_cards .= '
    <div class="doctor-card">
        <div class="doctor-image">
            <div class="doctor-avatar">'.strtoupper(substr($doctor['DoctorName'], 0, 1)).'</div>
            <div class="rating-badge">
                <i class="fas fa-star"></i> 4.5
            </div>
        </div>
        <div class="doctor-info">
            <h3>Dr. '.htmlspecialchars($doctor['DoctorName']).'</h3>
            <p class="specialty">'.htmlspecialchars($doctor['Specialization']).'</p>
            <p class="hospital"><i class="fas fa-hospital"></i> '.htmlspecialchars($doctor['ClinicName'] ?? 'Akasi Clinic').'</p>
            <p class="availability"><i class="fas fa-calendar-alt"></i> Accepting New Patients</p>
            <p class="fee"><i class="fas fa-money-bill-wave"></i> $'.number_format($doctor['ConsultationFee'], 2).'</p>
            <div class="doctor-actions">
                <a href="book_appointment.php?doctor_id='.$doctor['DoctorID'].'" class="btn btn-primary">Book Appointment</a>
                <a href="doctor_profile.php?id='.$doctor['DoctorID'].'" class="btn btn-secondary">View Profile</a>
            </div>
        </div>
    </div>';
}

// Replace placeholders
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
    '<!-- DOCTOR_CARDS -->' => $doctor_cards,
    'MediCare Clinic' => 'Akasi Clinic',
    'value="'.$specialization.'"' => 'value="'.$specialization.'" selected',
    'value="'.$gender.'"' => 'value="'.$gender.'" selected',
    'value="'.$sort.'"' => 'value="'.$sort.'" selected'
];

foreach ($replacements as $placeholder => $content) {
    $html = str_replace($placeholder, $content, $html);
}

echo $html;
?>