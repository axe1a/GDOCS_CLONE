<?php require_once 'core/dbConfig.php'; ?>

<!doctype html>
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
	  <a class="navbar-brand" style="color: #ea4335; font-weight: 500; font-size: 20px;" href="#">Google Docs Clone - Admin</a>
	</nav>
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 p-5">
          <div class="card shadow">
            <div class="card-header">
              <h4>Login - Admin</h4>
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
                <div class="form-group">
                  <label for="exampleInputEmail1">Username</label>
                  <input type="text" class="form-control" name="username">
                </div>
                <div class="form-group">
                  <label for="exampleInputEmail1">Password</label>
                  <input type="password" class="form-control" name="password">
                  <input type="submit" class="btn btn-primary float-right mt-4 mb-4" value="Login" style="background-color: #ea4335; border-color: #ea4335;" name="loginUserBtn">
                </div>
                <a href="register.php"><p class="text-left mt-4" style="background-color:#ffffff; color: #ea4335; font-size: 18px;">Register</p></a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
