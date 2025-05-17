<?php
function uploadImage($file, $destination_path = "images/products/") {
   // Create directory if it doesn't exist
   if (!file_exists($destination_path)) {
       mkdir($destination_path, 0777, true);
   }


   // Generate unique filename
   $file_name = time() . '_' . basename($file["name"]);
   $target_file = $destination_path . $file_name;


   // Check if file is an actual image
   $check = getimagesize($file["tmp_name"]);
   if($check === false) {
       throw new Exception("File is not an image.");
   }


   // Check file size (5MB max)
   if ($file["size"] > 5000000000) {
       throw new Exception("File is too large. Maximum size is 5MB.");
   }


   // Allow only certain file formats
   $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
   $file_extension = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
   if(!in_array($file_extension, $allowed_types)) {
       throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed.");
   }


   // Upload file
   if (!move_uploaded_file($file["tmp_name"], $target_file)) {
       throw new Exception("Failed to upload file.");
   }


   return $target_file;
}
?>


