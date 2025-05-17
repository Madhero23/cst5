<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <style>
        body  { 
            box-sizing: border-box;
            padding: 0;
            display: flex;
            flex-direction: row;
            margin: 0;
        }

        .create-form { 
            padding: 206px 95px;
        }

        .create-section-image { 
            display: flex;
            padding: 0;
        }

        .create-section-image  img { 
            width: 100%;
            height: 54vw;
        }

        .create-form h1 {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 28px;
            font-weight: bold;
        }

        input { 
            padding: 7px;
            min-width: 240px;
        }

        .create-container {
            display: flex;
            justify-content: end;
            padding-top: 14px;
        }

        .create { 
            padding: 10px;
            min-width: 130px;
        }

        .login-container p { 
            text-align: end;
        }

        .login-container a { 
            display: flex;
            justify-content: end;
            text-decoration: none;
            list-style: none;
            padding: auto;
            color: black;
        }

        .login-container a:hover {
            color: rgb(210, 194, 100);
        }

        .error-message {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <form action="create.php" method="post" onsubmit="return validateForm()">
        <div class="create-form">
            <h1>CREATE ACCOUNT</h1>
            <div id="error-message" class="error-message"></div>

            <label>First Name</label><br>
            <input type="text" id="firstname" name="firstname" placeholder="First Name" required><br>

            <label>Last Name</label><br>
            <input type="text" id="lastname" name="lastname" placeholder="Last Name" required><br>

            <label>Email</label><br>
            <input type="email" id="email" name="email" placeholder="Email" required><br>

            <label>Password</label><br>
            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="Password" required>
                <span class="toggle-password" onclick="togglePasswordVisibility()">👁️</span><br>
            </div>
            <small>Password must be at least 8 characters long.</small><br>

            <label>Confirm Password</label><br>
            <input type="password" id="confirm" name="confirm" placeholder="Confirm Password" required><br>

            <label>Birthday</label><br>
            <input type="date" id="birthday" name="birthday" required><br>

            <div class="terms">
                <input type="checkbox" id="terms" name="terms" required><br>
                <label for="terms">I agree to the <a href="/terms">Terms and Conditions</a></label><br>
            </div>

            <div class="create-container">
                <button type="submit" class="create" name="create">CREATE</button><br>
            </div>

            <div class="login-container">
                <p>Already have an account? <a href="login.php">Login here</a></p><br>
            </div>
        </div>
    </form>
    <section class="create-section-image">
        <img src="images/createimg.jpeg" alt="CREATE FORM" class="image-1">
    </section>

    <script>
        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm').value;
            const errorMessage = document.getElementById('error-message');

            if (password.length < 8) {
                errorMessage.textContent = 'Password must be at least 8 characters long.';
                return false;
            }

            if (password !== confirmPassword) {
                errorMessage.textContent = 'Passwords do not match.';
                return false;
            }

            return true;
        }

        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.textContent = '👁️';
            } else {
                passwordField.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }
    </script>
</body>
</html>

<?php
include("db_connect.php");

if (isset($_POST['create'])) {
    // Start a transaction
    mysqli_begin_transaction($conn);

    try {
        // Sanitize inputs
        $firstname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_SPECIAL_CHARS);
        $lastname = filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_SPECIAL_CHARS);
        $username = $firstname . ' ' . $lastname;
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $birthday = filter_input(INPUT_POST, 'birthday', FILTER_SANITIZE_SPECIAL_CHARS);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
        $confirm_password = filter_input(INPUT_POST, 'confirm', FILTER_SANITIZE_SPECIAL_CHARS);

        // Validate password match
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }

        // Insert into Customer table with plain text password (not recommended for production)
        $sql = "INSERT INTO Customer (CustomerName, CustomerEmail, CustomerPassword, CustomerBirthday) 
                VALUES ('$username', '$email', '$password', '$birthday')";

        if (mysqli_query($conn, $sql)) {
            // Commit the transaction
            mysqli_commit($conn);
            echo "<script>alert('Account Created Successfully!'); window.location.href='login.php';</script>";
        } else {
            // Rollback the transaction if there's an error
            mysqli_rollback($conn);
            if (mysqli_errno($conn) == 1062) { // Error code for duplicate entry
                throw new Exception("Email already exists. Please use a different email.");
            } else {
                throw new Exception("Error: " . mysqli_error($conn));
            }
        }
    } catch (Exception $e) {
        // Handle exceptions and display error messages
        echo "<script>alert('" . $e->getMessage() . "');</script>";
    } finally {
        // Close the connection
        mysqli_close($conn);
    }
}
?>