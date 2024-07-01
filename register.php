<!DOCTYPE html>  
<html>  
<head>
 <title>Register an Account</title>
 <link rel="stylesheet" href="styles.css">  
 <style>
  footer {
    background-color: #333;
    color: #fff;
    text-align: center;
    padding: 10px 0;
    bottom: 0;
    left: 0;
    width: 100%;
  }
 </style>
<style>
title {text-align: center;}  
.error {color: #FF0001;} 
</style>  
</head>  
<body>   
<?php include 'header.php'; ?>

<?php include 'nav.php'; ?> 
  
<?php  

$databaseHost = 'localhost';
$databaseName = 'book_exchange_system';
$databaseUsername = 'root';
$databasePassword = '';
$mysqli = mysqli_connect($databaseHost, $databaseUsername, $databasePassword, $databaseName); 
 
// define variables to empty values  
$usernameErr = $emailErr = $passwordErr = "";  
$username = $email = $password = "";  
  
//Input fields validation  
if ($_SERVER["REQUEST_METHOD"] == "POST") {  
	//Username Validation  
    if (empty(trim($_POST["username"]))) {  
         $usernameErr = "Please fill in your username.";  
    } else {  
        $username = input_data($_POST["username"]);    
    }  
      
    //Email Validation   
    if (empty(trim($_POST["email"]))) {  
            $emailErr = "Email is required";  
    } else {  
            $email = input_data($_POST["email"]);  
            // check that the e-mail address is well-formed  
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {  
                $emailErr = "Invalid email format";  
            }  
     }   
	 
	//Password Validation   
    if (empty(trim($_POST["password"]))) {  
        $passwordErr = "Password is required";  
} else {  
        $password = input_data($_POST["password"]);  
 }   
		
}  
function input_data($data) {  
  $data = trim($data);  
  $data = stripslashes($data);  
  $data = htmlspecialchars($data);  
  return $data;  
}  
?>  

<h1 class= "Register">Register</h1>  

<br><br>  
<?php include('register/form.php')?>
  
<?php  
    if(isset($_POST['Register'])) {  
    if($usernameErr == "" && $emailErr == "" && $passwordErr == "") {  
        echo "<h3> <b>Your account has been registered</b> </h3>"; 
		$result = mysqli_query($mysqli, "INSERT INTO user(username, password) VALUES('$username','$password')");
?>
    <script>
  window.alert("Registration successful.");
  window.location.href= "/Assignment/";
    </script>
    <?php
    } else {  
         
		echo "<div style ='color:#ff0000; text-align: center; display:block;'>Registration failed, please register correctly.</div>";
    }  
    }  
?>  




<?php include 'footer.php'; ?>  
</body>  
</html>  
