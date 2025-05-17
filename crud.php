<?php
// Include database connection
include 'db_connect.php';

// Get entity type (supplier, employee, etc.)
$entity = isset($_GET['entity']) ? $_GET['entity'] : '';

// Determine action (add, update, delete, view)
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'add' && $_SERVER["REQUEST_METHOD"] == "POST") {
    // ADD a new record
    $name = $_POST['name'];
    $email = $_POST['email'];

    $sql = "INSERT INTO $entity (name, email) VALUES ('$name', '$email')";
    if (mysqli_query($conn, $sql)) {
        echo "Record added successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} elseif ($action == 'update' && $_SERVER["REQUEST_METHOD"] == "POST") {
    // UPDATE an existing record
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];

    $sql = "UPDATE $entity SET name='$name', email='$email' WHERE id=$id";
    if (mysqli_query($conn, $sql)) {
        echo "Record updated successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} elseif ($action == 'delete' && isset($_GET['id'])) {
    // DELETE a record
    $id = $_GET['id'];

    $sql = "DELETE FROM $entity WHERE id=$id";
    if (mysqli_query($conn, $sql)) {
        echo "Record deleted successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} elseif ($action == 'view') {
    // VIEW records
    $result = mysqli_query($conn, "SELECT * FROM $entity");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "ID: " . $row['id'] . " - Name: " . $row['name'] . " - Email: " . $row['email'] . "<br>";
    }
} else {
    echo "Invalid action!";
}

// Close database connection
mysqli_close($conn);
?>
