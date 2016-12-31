<?php
   ob_start();
   session_start();
?>

<html lang = "en">
   <head>
      <title>Management System - BB Forum - Project Evaluation</title>
      <link href = "css/bootstrap.min.css" type="text/css" rel = "stylesheet">
      <link href = "css/style.css" type="text/css" rel = "stylesheet">  
   </head>
	
   <body>
      
      <h2>MSBB - Login</h2> 
      <div class = "container form-signin">
         
         <?php
            $msg = '';
            
            if (isset($_POST['login']) && !empty($_POST['username']) 
               && !empty($_POST['password'])) {
				
               if ($_POST['username'] == 'crossover' && 
                  $_POST['password'] == 'crossover') {
                  $_SESSION['valid'] = true;
                  $_SESSION['timeout'] = time();
                  $_SESSION['username'] = 'crossover';
                  
                  echo 'You have entered valid use name and password';
               }else {
                  $msg = 'Wrong username or password';
               }
            }
         ?>
      </div> <!-- /container -->
      
      <div class = "container">
      
         <form class = "form-signin" role = "form" action = "main.php" method = "post">
            <h4 class = "form-signin-heading"><?php echo $msg; ?></h4>
            <input type = "text" class = "form-control" 
               name = "username" placeholder = "username = crossover" 
               required autofocus></br>
            <input type = "password" class = "form-control"
               name = "password" placeholder = "password = crossover" required>
            <button class = "btn btn-lg btn-primary btn-block" type = "submit" 
               name = "login">Login</button>
         </form>
         
      </div> 
      
   </body>
</html>
