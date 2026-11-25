<?php
session_start();

$databaseHost = 'localhost';
$databaseName = 'book_exchange_system';
$databaseUsername = 'root';
$databasePassword = '';
$mysqli = mysqli_connect($databaseHost, $databaseUsername, $databasePassword, $databaseName);

if (!$mysqli) {
    die("Database connection failed: " . mysqli_connect_error());
}

$usernameErr = $emailErr = $passwordErr = "";
$username = $email = $password = "";
$registrationError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "") {
        $usernameErr = "Please fill in your username.";
    }

    if ($email === "") {
        $emailErr = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Invalid email format.";
    }

    if (trim($password) === "") {
        $passwordErr = "Password is required.";
    }

    if (isset($_POST["Register"]) && $usernameErr === "" && $emailErr === "" && $passwordErr === "") {
        $statement = mysqli_prepare($mysqli, "INSERT INTO user (username, email, password) VALUES (?, ?, ?)");
        if (!$statement) {
            die("Registration failed: " . mysqli_error($mysqli));
        }

        mysqli_stmt_bind_param($statement, "sss", $username, $email, $password);
        if (mysqli_stmt_execute($statement)) {
            $_SESSION["username"] = $username;
            $_SESSION["userId"] = mysqli_insert_id($mysqli);
            mysqli_stmt_close($statement);
            mysqli_close($mysqli);
            header("Location: userPage.php");
            exit;
        }

        if (mysqli_errno($mysqli) === 1062) {
            $registrationError = "That username or email is already registered.";
        } else {
            $registrationError = "Registration failed: " . mysqli_stmt_error($statement);
        }
        mysqli_stmt_close($statement);
    }
}

mysqli_close($mysqli);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Register an Account</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .error { color: #ff0000; }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>
  <?php include 'nav.php'; ?>

  <h1 class="Register">Register</h1>
  <div class="container">
    <form method="post" action="register.php">
      <div class="row">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required>
        <span class="error"><?php echo htmlspecialchars($usernameErr, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <div class="row">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
        <span class="error"><?php echo htmlspecialchars($emailErr, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <div class="row">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <span class="error"><?php echo htmlspecialchars($passwordErr, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <div class="row">
        <input type="submit" name="Register" value="Register">
      </div>
    </form>
  </div>

  <?php if ($registrationError !== ""): ?>
    <div class="error" role="alert"><?php echo htmlspecialchars($registrationError, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <?php include 'footer.php'; ?>
</body>
</html>
