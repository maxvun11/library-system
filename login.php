<!DOCTYPE html>
<html>
    <head>
        <title>Book Exchange System</title>
        <link rel="stylesheet" href="styles.css"> 
    </head>
     
    <body>
        <?php include 'header.php'; ?>
        <?php include('nav.php'); ?>
        <?php 
            session_start();
            

            // Database configuration
            $dbHost = 'localhost';
            $dbUsername = 'root';
            $dbPassword = '';
            $dbName = 'book_exchange_system';

            // Connect to database
            $conn = mysqli_connect($dbHost, $dbUsername, $dbPassword, $dbName);

            // Check connection
            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }

            $sql = "SELECT * FROM user";
            $result = mysqli_query($conn, $sql);
            

            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btnSubmit']) && $_POST['btnSubmit'] == 'Login') {
                $username = $_POST["username"];
                $password = $_POST["password"];
                
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        if ($username == $row["username"] && $password == $row["password"]) {
                            echo "Login Successful. Hello, ".$username."!";
                            $_SESSION['username'] = $username;
                            $_SESSION['userId'] = $row["user_id"];
        ?>
                        <script>
                            window.alert("Login successful.");
                            window.location.href= "/Assignment/";
                        </script>
        <?php
                        }
                    }
                    if (!isset($_SESSION['username'])) {
                        echo "Login fail.";
                    }
                }
            }

            // Free result set and close connection
            mysqli_free_result($result);
            mysqli_close($conn);

        ?>
        <h1 class="Login">Login</h1><br><br>
        <div class="container">
        <form id='loginForm' method="post">
            <div class="row">
                <div class="col-25">
                    <label for = "username" > Username: </label>
                </div>
                <div class="col-75">
                    <input type="text" id = "username" name="username" placeholder="Your username.." required>
                </div>
            </div>
            <div class="row">
                <div class="col-25">
                    <label for="password">Password</label>
                </div>
                <div class="col-75">
                    <input type="password" id="password" name="password" placeholder="Your password.." required>
                </div>
            </div>
            <br>
            <div class="row">
                <input type="submit" name="btnSubmit" value="Login">
            </div>
        </form>
        </div>
        <?php include 'footer.php'; ?> 
    </body>
</html>
