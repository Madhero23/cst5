<?php
session_start();
include("db_connect.php");

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Fetch user information based on user type
try {
    if ($user_type === 'employee') {
        $stmt = $conn->prepare("SELECT EmployeeID, EmployeeName, EmployeeContactInfo, Salary FROM employee WHERE EmployeeID = ?");
    } else if ($user_type === 'customer') {
        $stmt = $conn->prepare("SELECT CustomerID, CustomerName, CustomerEmail, CustomerBirthday, RegistrationDate FROM customer WHERE CustomerID = ?");
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        throw new Exception("User not found.");
    }

    $stmt->close();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: login.php");
    exit();
}

// Handle form submission for profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        // Start a transaction
        $conn->begin_transaction();

        if ($user_type === 'customer') {
            // Validate customer inputs
            if (empty($_POST['name']) || empty($_POST['email'])) {
                throw new Exception("Name and email are required.");
            }

            $stmt = $conn->prepare("UPDATE customer SET CustomerName = ?, CustomerEmail = ?, CustomerBirthday = ? WHERE CustomerID = ?");
            $stmt->bind_param("sssi", $_POST['name'], $_POST['email'], $_POST['birthday'], $user_id);
        } else {
            // Validate employee inputs
            if (empty($_POST['name']) || empty($_POST['contact'])) {
                throw new Exception("Name and contact information are required.");
            }

            $stmt = $conn->prepare("UPDATE employee SET EmployeeName = ?, EmployeeContactInfo = ? WHERE EmployeeID = ?");
            $stmt->bind_param("ssi", $_POST['name'], $_POST['contact'], $user_id);
        }

        if (!$stmt->execute()) {
            throw new Exception("Failed to update profile.");
        }

        // Commit the transaction
        $conn->commit();
        $_SESSION['message'] = "Profile updated successfully!";
        header("Location: account.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Information</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/account.css">
    <style>
        :root {
            --bakery-primary: #8B4513;
            --bakery-secondary: #DEB887;
            --bakery-light: #FFEFD5;
            --bakery-dark: #654321;
            --card-shadow: 0 4px 15px rgba(139, 69, 19, 0.1);
        }

        body {
            font-family: 'Quicksand', sans-serif;
            min-height: 100vh;
            padding-top: 80px;
            
        }

        .account-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px dashed var(--bakery-secondary);
        }

        .profile-icon {
            width: 100px;
            height: 100px;
            background: var(--bakery-secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2.5rem;
        }

        .info-section {
            background: rgba(222, 184, 135, 0.1);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .info-row {
            display: flex;
            margin-bottom: 1rem;
            padding: 0.5rem;
            border-bottom: 1px solid rgba(139, 69, 19, 0.1);
        }

        .info-label {
            font-weight: 600;
            width: 150px;
            color: var(--bakery-dark);
        }

        .info-value {
            flex: 1;
        }

        .btn-action {
            background: #1b3c3d;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            background: var(--bakery-dark);
            transform: translateY(-2px);
            box-shadow: var(--card-shadow);
        }

        .btn-danger {
            background: #dc3545;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .navigation-container {
            background: var(--bakery-dark);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 0.5rem 1rem;
        }

        .nav-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            color: var(--bakery-light);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
    </style>
</head>
<body>
 

    <div class="account-container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="profile-icon">
                <i class="fas fa-user"></i>
            </div>
            <h2><?php echo htmlspecialchars($user_type === 'customer' ? $user['CustomerName'] : $user['EmployeeName']); ?></h2>
            <span class="status-badge">
                <?php echo ucfirst($user_type); ?>
            </span>
        </div>

        <div class="info-section">
            <form method="POST" action="">
                <?php if ($user_type === 'customer'): ?>
                    <div class="info-row">
                        <div class="info-label">Customer ID</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['CustomerID']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Name</div>
                        <div class="info-value">
                            <input type="text" name="name" class="form-control"
                                   value="<?php echo htmlspecialchars($user['CustomerName']); ?>" required>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email</div>
                        <div class="info-value">
                            <input type="email" name="email" class="form-control"
                                   value="<?php echo htmlspecialchars($user['CustomerEmail']); ?>" required>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Birthday</div>
                        <div class="info-value">
                            <input type="date" name="birthday" class="form-control"
                                   value="<?php echo $user['CustomerBirthday']; ?>">
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Member Since</div>
                        <div class="info-value">
                            <?php echo date('F d, Y', strtotime($user['RegistrationDate'])); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="info-row">
                        <div class="info-label">Employee ID</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['EmployeeID']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Name</div>
                        <div class="info-value">
                            <input type="text" name="name" class="form-control"
                                   value="<?php echo htmlspecialchars($user['EmployeeName']); ?>" required>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Contact Info</div>
                        <div class="info-value">
                            <input type="text" name="contact" class="form-control"
                                   value="<?php echo htmlspecialchars($user['EmployeeContactInfo']); ?>" required>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="text-center mt-4">
                    <button type="submit" name="update_profile" class="btn btn-action">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        <div class="action-buttons">
            <a href="logout.php" class="btn btn-action btn-danger"
               onclick="return confirm('Are you sure you want to logout?')">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
    <script>
        // Add client-side validation
        document.addEventListener('DOMContentLoaded', function() {
            // Handle session timeout
            let sessionTimeout = setTimeout(function() {
                alert('Your session is about to expire. Please save any changes.');
                window.location.href = 'logout.php';
            }, 30 * 60 * 1000); // 30 minutes

            // Reset timeout on user activity
            document.addEventListener('mousemove', function() {
                clearTimeout(sessionTimeout);
                sessionTimeout = setTimeout(function() {
                    alert('Your session is about to expire. Please save any changes.');
                    window.location.href = 'logout.php';
                }, 30 * 60 * 1000);
            });
        });
    </script>
</body>
</html>