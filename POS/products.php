<?php
  // Database connection (make sure to include your connection file)
  require_once 'db_connection.php'; // Adjust this path as needed

  // Query to fetch available products
  $query = "SELECT ProductID, ProductName, Price, StockQuantity, Status 
           FROM products 
           WHERE Status = 'Available'";
           
  $result = mysqli_query($conn, $query);
  
  $products = array();
  
  // Convert query results to array format
  while($row = mysqli_fetch_assoc($result)) {
      $products[] = array(
          'name' => $row['ProductName'],
          'image' => 'hamburguer.svg', // You might want to add an image field to your database
          'value' => $row['Price'] * 100, // Converting to cents as your original code used
          'id' => $row['ProductID'],
          'stock' => $row['StockQuantity']
      );
  }
?>