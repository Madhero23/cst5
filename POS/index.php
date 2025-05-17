<?php
  @include_once( './products.php' );
  $servername = "localhost";
$username = "root";
$password = "";
$dbname = "thebakerydb";
include 'includes/sidebar.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['update_customer'])) {
    $stmt = $conn->prepare("UPDATE customer SET CustomerName=?, CustomerEmail=?, CustomerBirthday=? WHERE CustomerID=?");
    $stmt->bind_param("sssi", $name, $email, $birthday, $id);
    
    $id = $_POST['customer_id']; // ✅ Fix: Make sure ID is retrieved correctly
    $name = $_POST['customer_name'];
    $email = $_POST['customer_email'];
    $birthday = $_POST['customer_birthday'];

    if ($stmt->execute()) {
        echo "<script>alert('Customer updated successfully!'); window.location.href = '".$_SERVER['PHP_SELF']."';</script>";
    } else {
        echo "<script>alert('Update failed: " . $stmt->error . "');</script>";
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS</title>
    <link rel="stylesheet" href="./style.css">
  </head>
  <body>
    <section class="products">
      <?php
        foreach ( $products as $key => $product ) { ?>
          <div class="product" data-index="<?php echo $key; ?>" data-name="<?php echo $product['name']; ?>" data-value="<?php echo $product['value']; ?>">
            <img src="./images/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
            <p class="product-name"><?php echo $product['name']; ?></p>
            <p class="product-value"><?php echo $product['value']; ?></p>
          </div>
        <?php
        }
      ?>
    </section>
    <section class="bill">
      <div class="bill-products">
        <h2>Productos</h2>
      </div>
      <div class="bill-client">
        <form method="POST" action="./bill.php">
          <div class="hidden">
            <label for="products">Products</label>
            <input type="text" name="products" id="products" placeholder="Products" value="">
          </div>
          <div>
            <label for="name">Name</label>
            <input type="text" name="name" id="name" placeholder="Client Name">
          </div>
          <div>
            <label for="id">ID</label>
            <input type="text" name="id" id="id" placeholder="Client ID">
          </div>
          <div>
            <input type="submit" value="Print">
          </div>
        </form>
      </div>
    </section>
    <script>
      (function() {
        let products = document.querySelectorAll('section.products > .product');
        let billProducts = document.querySelector('section.bill > .bill-products');
        let productsInput = document.querySelector('section.bill #products');

        productsInput.value = '';
        
        products.forEach( product => {
          product.addEventListener( 'click', function( e ) {
            let index = e.srcElement.dataset.index;
            let name = e.srcElement.dataset.name;
            let value = e.srcElement.dataset.value;

            let p = document.createElement('p');
            p.innerHTML = name + ' - $' + value;
            billProducts.appendChild( p );

            if ( productsInput.value == '' ) {
              productsInput.value += index;
            } else {
              productsInput.value += ',' + index;
            }
          });
        });
      })();
    </script>
  </body>
</html>