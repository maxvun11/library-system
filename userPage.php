<!DOCTYPE html>
<html>
<head>
    <title>User Page</title>
    <link rel="stylesheet" href="userStyles.css"> 
</head>
<body>
<?php include 'header.php'; ?>
<?php include('nav.php');?>

<div class="main">
<form action="logout.php" method="post"> 
    <input type="submit" name="logout" value="Logout">
</form>

<?php
    session_start();
     
    if (isset($_SESSION['username'])) {
        echo "<h1>".strtoupper($_SESSION['username'])."</h1>";
    } else {
        echo "<h1>USER</h1>";
        echo "Please login to your account.<br>";
    }
?>
<div class="dropdown">
    <button onclick="myFunction()" class="dropbtn">Check Exchange Request</button>
    <div id="myDropdown" class="dropdown-content"></div>
</div>

<div class='container'>
<form method="post">
    <div class="row">
        <div class="col-25">
            <label for="title">Book Title: </label>
        </div>
        <div class="col-75">
            <input type="text" id="title" name="title" placeholder="Your book's title.." required>
        </div>
    </div>
    <div class="row">
        <div class="col-25">
            <label for="age">Book Age: </label>
        </div>
        <div class="col-75">
            <input type="number" id="age" name="age" min="0" placeholder="Your book's age in year.." required>
        </div>
    </div>
    <div class="row">
        <div class="col-25">
            <label for="price">Book Price: </label>
        </div>
        <div class="col-75">
            <input type="number" id="price" name="price" placeholder="Your book's price.." required>
        </div>
    </div>
    <div class="row">
        <div class="col-25">
            <label for="author">Author: </label>
        </div>
        <div class="col-75">
            <input type="text" id="author" name="author" placeholder="Your book's author.." required>
        </div>
    </div>
    <div class="row">
        <div class="col-25">
            <label for="summary">Book Summary: </label>
        </div>
        <div class="col-75">
            <textarea id="summary" name="summary" placeholder="Your book's summary.." required></textarea>
        </div>
    </div>
    <br>
    <div class="row">
        <input type="submit" name="submit" value="Upload">
    </div>
</form>
</div>
<br><br>

<?php
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

    echo "<h2>Uploaded Book List</h2>";

    if (isset($_SESSION['username'])){
        $sqlUserId = "SELECT user_id FROM user WHERE username = '$_SESSION[username]'";
        $userIdQueryResult = $conn -> query($sqlUserId);
        $userIdRow = $userIdQueryResult -> fetch_assoc();
        $userId = $userIdRow['user_id'];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == 'Upload') {
            $title = $_POST["title"];
            $age = $_POST["age"];
            $price = $_POST["price"];
            $summary = $_POST["summary"];
            $author = $_POST["author"];
            $sql = "INSERT INTO book (book_title, book_age, book_price, book_summary, upload_status, user_id, book_author) VALUES ('$title', '$age', '$price', '$summary', 'pending', '$userId', '$author')";

            if ($conn->query($sql) === TRUE) {
                echo "Book uploaded successfully<br><br>";
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }

        $sql = "SELECT * FROM book WHERE user_id = '$userId'";
        $result = $conn->query($sql);
        $int_count = 0;

        echo "<div class='book-list'>";
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<div class='book-card'>";
                echo "<h3>" . $row["book_title"] . "</h3>";
                echo "<p>Book Age: " . $row["book_age"] . "</p>";
                echo "<p>Book Price: " . $row["book_price"] . "</p>";
                echo "<p>Book Author: " . $row["book_author"] . "</p>";
                echo "<p>Book Summary: " . $row["book_summary"] . "</p>";
                echo "<p class='upload-status'>Upload Status: " . $row["upload_status"] . "</p>";
                echo "</div>";
                

                $sqlExchange = "SELECT * FROM book_exchange WHERE book_id = '".$row["book_id"]."'";
                $resultExchange = $conn->query($sqlExchange);
                
                if ($resultExchange->num_rows > 0) {
                    while ($rowExchange = $resultExchange->fetch_assoc()) {
                        $sqlExUsername = "SELECT username FROM user WHERE user_id = '".$rowExchange["user_id"]."'";
                        $usernameQueryResult = $conn -> query($sqlExUsername);
                        $exUsernameRow = $usernameQueryResult -> fetch_assoc();
                        $username = $exUsernameRow['username'];
                        $exUser_id[$int_count] = $rowExchange["user_id"];
                        $exchange_id[$int_count] = $rowExchange["exchange_id"];
                        $exchange_username[$int_count] = $username;
                        $exchange_bookTitle[$int_count] = $row["book_title"];
                        $int_count++;
                    }
                }
            }
        } else {
            echo "0 results";
        }
        echo "</div>";
    }
?>

<br>

<script>
    function myFunction() {
    document.getElementById("myDropdown").classList.toggle("show");
    }

    // Close the dropdown if the user clicks outside of it
    window.onclick = function(event) {
        if (!event.target.matches('.dropbtn')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            var i;
            for (i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
</script>
<script type="text/javascript">
    var exchangeId = <?php echo json_encode($exchange_id); ?>;
    var exUsername = <?php echo json_encode($exchange_username); ?>;
    var exBookTitle = <?php echo json_encode($exchange_bookTitle); ?>;
    var exUserId = <?php echo json_encode($exUser_id); ?>;
    const droplist = document.getElementById("myDropdown");

    for(var i = 0; i < exUsername.length; i++) {
        const request_user = document.createElement('a');
        request_user.href = "/AssignmentTest1/userPage.php?exId=" + exchangeId[i] + "&exUserId=" + exUserId[i];
        request_user.innerHTML = exUsername[i] + "\n" + exBookTitle[i];
        droplist.appendChild(request_user);
    }
</script>
<?php
    if(isset($_GET['exId']) && isset($_GET['exUserId'])) {
    // Retrieve the id value from the URL
        $exchangeId = $_GET['exId'];
        $exUserId = $_GET['exUserId'];
        echo "<form method='post' action='userPage.php?exchangeId=".$exchangeId."'>";
        $sql = "SELECT * FROM book WHERE user_id = '$exUserId'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "Title: " . $row["book_title"]. " - Book Condition: " . $row["book_condition"]." - Book Age: " . $row["book_age"] . " ";
                echo "<input type='checkbox' id='book' name='book' value='".$row["book_id"]."' onclick='onlyOne(this)'><br>";
            }
            echo "<input type='submit' name='accept' value='Accept'>";
            echo "<input type='submit' name='decline' value='Decline'>";
        }
        echo "</form>";
    } 

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accept']) && $_POST['accept'] == 'Accept') {
        $exchangeId = $_GET['exchangeId'];
        $book_id1 = $_POST["book"];
        $sqlGetExchange = "SELECT * FROM book_exchange WHERE exchange_id='$exchangeId'";
        $getExchangeResult = $conn -> query($sqlGetExchange);
        $rowGetExchange = $getExchangeResult -> fetch_assoc();
        $book_id2 = $rowGetExchange["book_id"];
        $sqlRemoveBook = "DELETE FROM book WHERE book_id IN ('$book_id1', '$book_id2')";
        $sqlRemoveExchange = "DELETE FROM book_exchange WHERE exchange_id='$exchangeId'";

        if ($conn -> query($sqlRemoveBook)) {
            if ($conn -> query($sqlRemoveExchange)) {
                echo "Exchange Book Successfully";
            } else {
                echo "Exchange Book Fail";
            }
        }
    } else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['decline']) && $_POST['decline'] == 'Decline'){
        $exchangeId = $_GET['exchangeId'];
        $sqlRemoveExchange = "DELETE FROM book_exchange WHERE exchange_id='$exchangeId'";
        if ($conn -> query($sqlRemoveExchange)) {
            echo "Request denied Successfully";
        } else {
            echo "Request denied Fail";
        }
    }
    
?>

<script>
    function onlyOne(checkbox) {
        var checkboxes = document.getElementsByName('book')
        checkboxes.forEach((item) => {
            if (item !== checkbox) item.checked = false
        })
    }
</script>


</div>
<?php include 'footer.php'; ?>
</body>
</html>

