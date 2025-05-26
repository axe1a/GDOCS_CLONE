<?php require_once 'core/dbConfig.php'; ?>
<html lang="en">
<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
  <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
  <link href="https://fonts.cdnfonts.com/css/product-sans" rel="stylesheet">
    <style>
	  body {
        font-family: 'Product Sans', sans-serif;
		  background-color: #f5f5f5;
      }
    </style>
    <title>Hello, world!</title>
  </head>
  <body>
  <nav class="navbar navbar-expand-lg navbar-dark p-4" style="background-color: #ffffff;">
	  <a class="navbar-brand" style="color: #1d5db1; font-weight: 500; font-size: 20px;" href="#">Google Docs Clone</a>
	</nav>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 p-5">
        <div class="card shadow">
          <div class="card-header">
            <h4>Register</h4>
          </div>
          <form action="core/handleForms.php" method="POST">
            <div class="card-body">
              <?php  
                if (isset($_SESSION['message']) && isset($_SESSION['status'])) {

                  if ($_SESSION['status'] == "200") {
                    echo "<h1 style='color: green;'>{$_SESSION['message']}</h1>";
                  }

                  else {
                    echo "<h1 style='color: red;'>{$_SESSION['message']}</h1>"; 
                  }

                }
                unset($_SESSION['message']);
                unset($_SESSION['status']);
              ?>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="exampleInputEmail1">First Name</label>
                    <input type="text" class="form-control" name="first_name" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="exampleInputEmail1">Last Name</label>
                    <input type="text" class="form-control" name="last_name" required>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label for="exampleInputEmail1">Username</label>
                <input type="text" class="form-control" name="username" required>
              </div>
              <div class="form-group">
                <label for="exampleInputEmail1">Password</label>
                <input type="password" class="form-control" name="password" required>
              </div>
              <div class="form-group">
                <label for="exampleInputEmail1">Confirm Password</label>
                <input type="password" class="form-control" name="confirm_password" required>
                <input type="submit" class="btn btn-primary float-right mt-4" value="Register" style="background-color: #1d5db1; border-color: #1d5db1;" name="insertNewUserBtn">
              </div>
              <a href="login.php"><p class="text-left mt-4" style="background-color:#ffffff; color: #1d5db1; font-size: 18px;">Back to login</p></a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
