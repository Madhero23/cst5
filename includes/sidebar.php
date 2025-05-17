<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Include the database connection file
require_once 'C:\xampp\htdocs\thebakers\CSE7-Project\db_connect.php'; // Ensure this path is correct
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body>  
<nav class="navbar navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-cookie"></i> Bakery Management
        </a>
        <div>
            <!-- User Icon Button -->
            <button class="btn btn-dark" type="button" data-bs-toggle="modal" data-bs-target="#employeeModal">
                <i class="fas fa-user"></i>
            </button>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDarkNavbar" aria-controls="offcanvasDarkNavbar" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        <!-- Offcanvas Menu -->
        <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="offcanvasDarkNavbar" aria-labelledby="offcanvasDarkNavbarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasDarkNavbarLabel">
                    <i class="fas fa-bars"></i> Management Menu
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                    <!-- Core Features -->
                    <li class="nav-item">
                        <a class="nav-link" href="/thebakers/CSE7-Project/index.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/thebakers/CSE7-Project/menu.php">
                            <i class="fas fa-list"></i> Menu List
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/thebakers/CSE7-Project/orders_sales.php">
                            <i class="fas fa-shopping-cart"></i> Orders and Sales
                        </a>
                    </li>

                    <!-- Inventory Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-box"></i> Inventory
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="/thebakers/CSE7-Project/inventory_history.php">Inventory History</a></li>
                            <li><a class="dropdown-item" href="/thebakers/CSE7-Project/stockin.php">Stock In Products</a></li>
                            <li><a class="dropdown-item" href="/thebakers/CSE7-Project/stockout.php">Stock Out</a></li>
                            <li><a class="dropdown-item" href="/thebakers/CSE7-Project/return.php">Refund</a></li>
                        </ul>
                    </li>

                    <!-- POS -->
                    <li class="nav-item">
                        <a class="nav-link" href="/thebakers/CSE7-Project/pos.php">
                            <i class="fas fa-cash-register"></i> POS
                        </a>
                    </li>

                    <!-- Records Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-folder"></i> Records
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="/thebakers/CSE7-Project/employee.php">Employee Records</a></li>
                            <li><a class="dropdown-item" href="/thebakers/CSE7-Project/customer.php">Customer Records</a></li>
                        </ul>
                    </li>

                    <!-- Logout -->
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
<!-- Employee Information Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeModalLabel">
                    <i class="fas fa-user"></i> User Profile
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Tabs for Profile Sections -->
                <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                            Profile
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                            Security
                        </button>
                    </li>
                </ul>
                <!-- Tab Content -->
                <div class="tab-content mt-3" id="profileTabsContent">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                        <?php
                        if (isset($_SESSION['user_id'])) {
                            if ($_SESSION['user_type'] === 'employee') {
                                // Fetch employee details excluding role
                                $employeeID = $_SESSION['user_id'];
                                $sql = "SELECT e.* 
                                        FROM employee e
                                        WHERE e.EmployeeID = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("i", $employeeID);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $employee = $result->fetch_assoc();

                                echo '<form action="update_profile.php" method="post" enctype="multipart/form-data">';
                                echo '<div class="mb-3 text-center">';
                                // Display the current profile picture or a default one
                                if (!empty($_SESSION['profile_picture'])) {
                                    echo '<img src="' . $_SESSION['profile_picture'] . '" alt="Profile Picture" class="img-fluid rounded-circle" style="width: 150px; height: 150px;">';
                                } else {
                                    echo '<img src="images/default_profile.png" alt="Default Profile Picture" class="img-fluid rounded-circle" style="width: 150px; height: 150px;">';
                                }
                                echo '</div>';
                                echo '<div class="mb-3">';
                                echo '<label for="profilePicture" class="form-label">Upload New Profile Picture</label>';
                                echo '<input type="file" class="form-control" id="profilePicture" name="profilePicture" accept="image/jpeg, image/png, image/gif">';
                                echo '</div>';
                                echo '<div class="mb-3">';
                                echo '<label for="name" class="form-label">Name</label>';
                                echo '<input type="text" class="form-control" id="name" name="name" value="' . ($employee['EmployeeName'] ?? 'N/A') . '">';
                                echo '</div>';
                                echo '<div class="mb-3">';
                                echo '<label for="email" class="form-label">Email</label>';
                                echo '<input type="email" class="form-control" id="email" name="email" value="' . ($employee['EmployeeContactInfo'] ?? 'N/A') . '">';
                                echo '</div>';
                                echo '<button type="submit" class="btn btn-primary">Save Changes</button>';
                                echo '</form>';
                            } elseif ($_SESSION['user_type'] === 'customer') {
                                echo '<p><strong>Name:</strong> ' . ($_SESSION['user_name'] ?? 'N/A') . '</p>';
                                echo '<p><strong>Email:</strong> ' . ($_SESSION['user_email'] ?? 'N/A') . '</p>';
                            }
                        } else {
                            echo "<p>No user logged in.</p>";
                        }
                        ?>
                    </div>
                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                        <form action="change_password.php" method="post">
                            <div class="mb-3">
                                <label for="currentPassword" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                            </div>
                            <div class="mb-3">
                                <label for="newPassword" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">
                    <i class="fas fa-sign-out-alt"></i> Confirm Logout
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to logout?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </div>
</div>
<style>
.navbar-nav .nav-link {
    padding: 0.8rem 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.navbar-nav .nav-link i {
    width: 20px;
    text-align: center;
}
.dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0.8rem 1rem;
}
.nav-item.dropdown:hover .dropdown-menu {
    display: block;
}
@media (min-width: 992px) {
    .offcanvas {
        width: 280px;
    }
}
.nav-link:hover, .dropdown-item:hover {
    background: rgba(255,255,255,0.1);
}
/* Add spacing between sections */
.nav-item:not(:last-child) {
    margin-bottom: 0.25rem;
}
/* Active state styling */
.nav-link.active, .dropdown-item.active {
    background: rgba(255,255,255,0.1);
}
/* Add these styles */
body {
    padding-top: 60px; /* Height of the navbar */
}
.content-wrapper {
    margin-left: 250px; /* Width of the sidebar */
    padding: 20px;
    transition: margin-left 0.3s;
}
@media (max-width: 768px) {
    .content-wrapper {
        margin-left: 0;
    }
}
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1030;
}
.sidebar {
    position: fixed;
    top: 60px; /* Height of the navbar */
    left: 0;
    bottom: 0;
    width: 250px;
    z-index: 1020;
    overflow-y: auto;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.search.split('=')[1] || 'dashboard';
    
    document.querySelectorAll('.nav-link, .dropdown-item').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
            link.classList.add('active');
            
            // If it's a dropdown item, also activate parent
            const dropdownParent = link.closest('.dropdown');
            if (dropdownParent) {
                dropdownParent.querySelector('.nav-link').classList.add('active');
            }
        }
    });
});
// Logout function
function logout() {
    // Redirect to login.php
    window.location.href = 'login.php';
}
</script>
</body>
</html>