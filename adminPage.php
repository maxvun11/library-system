<!DOCTYPE html>
<html>
    <head>
        <title>Admin</title>
        <link rel="stylesheet" href="Styles.css">
    </head>
    <body>
        <?php include('nav.php'); ?>
        <div class='main'>
        <?php
            session_start();
            

            $dbHost = "localhost";
            $dbUsername = "root";
            $dbPassword = "";
            $dbName = "book_exchange_system";

            $conn = mysqli_connect($dbHost, $dbUsername, $dbPassword, $dbName);

            if(!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                for ($i = 0; $i < count($_SESSION["pendingListKey"]); $i++) {
                    if ($_POST["status".$_SESSION["pendingListKey"][$i]] == "accomplished") {
                        $sql = "UPDATE book SET book_condition='".$_POST["condition".$_SESSION["pendingListKey"][$i]]."',book_rate='".$_POST["rate".$_SESSION["pendingListKey"][$i]]."',upload_status='".$_POST["status".$_SESSION["pendingListKey"][$i]]."' WHERE book_id='".$_SESSION["pendingListKey"][$i]."'";
                        if ($conn->query($sql) === TRUE) {
                            echo "Announcement updated successfully<br>";
                        } else {
                            echo "Error: " . $sql . "<br>" . $conn->error;
                        }
                    }
                }    
            }

            $sql = "SELECT * FROM book WHERE upload_status = 'pending'";
            $result = $conn -> query($sql);
            $pendingList = [];
            $pendingListKey = [];

            if ($result -> num_rows > 0) {
                while ($rows = $result -> fetch_assoc()) {
                    $usernameSql = "SELECT username FROM user WHERE user_id = '".$rows["user_id"]."'";
                    $usernameQuery = $conn -> query($usernameSql);
                    $usernameRow = $usernameQuery -> fetch_assoc();
                    $username = $usernameRow['username'];

                    $pendingList[$rows["book_id"]] = [
                        "book_title" => $rows["book_title"],
                        "owner" => $username
                    ];                
                }
                foreach ($pendingList as $key => $value) {
                    array_push($pendingListKey, $key);
                }
                $_SESSION["pendingListKey"] = $pendingListKey;

                echo "<form method='post'>";
                foreach ($pendingList as $book_id => $book_detail) {
                    echo "Book ID: ".$book_id."  Book Title: ".$book_detail["book_title"]."  Owner: ".$book_detail["owner"]."<br>";
                    echo "Book Condition: <select id='condition' name='condition".$book_id."'><option value='Excellent'>Excellent</option><option value='Good'>Good</option><option value='Poor'>Poor</option></select>";
                    echo "<br>Book Rate: <input type='number' id='rate' name='rate".$book_id."' min='1' max='5'>";
                    echo "<br>Book Status: <select id='status' name='status".$book_id."'><option value='pending'>Pending</option><option value='accomplished'>Accomplished</option></select>";
                    echo "<br><br>";
                }
                echo "<br><input type='submit' value='Submit'>";
                echo "</form>";
            } else {
                echo "No pending book.";
            }
        ?>
    </div>
    </body>
</html>
