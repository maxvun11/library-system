<?php
session_start();

if (!isset($_SESSION['username'])) {
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Page</title>
    <link rel="stylesheet" href="userStyles.css">
</head>
<body>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>
<main class="main">
    <h1>USER</h1>
    <p>Please register or log in to access your account.</p>
    <a class="register-button" href="register.php">Register</a>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
<?php
    exit;
}

$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName = 'book_exchange_system';
$conn = mysqli_connect($dbHost, $dbUsername, $dbPassword, $dbName);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$userId = (int) ($_SESSION['userId'] ?? 0);
$exchangeMessage = $_SESSION['exchangeMessage'] ?? '';
unset($_SESSION['exchangeMessage']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exchangeAction'])) {
    $exchangeId = (int) ($_POST['exchangeId'] ?? 0);
    $action = $_POST['exchangeAction'];
    $actionSucceeded = false;

    $requestStatement = $conn->prepare(
        "SELECT e.book_id, e.user_id, e.offered_book_id,
                requested_book.user_id AS owner_id,
                requested_book.upload_status AS requested_book_status
         FROM book_exchange e
         JOIN book requested_book ON requested_book.book_id = e.book_id
         WHERE e.exchange_id = ?"
    );
    $requestStatement->bind_param("i", $exchangeId);
    $requestStatement->execute();
    $exchangeRequest = $requestStatement->get_result()->fetch_assoc();
    $requestStatement->close();

    if (!$exchangeRequest || (int) $exchangeRequest['owner_id'] !== $userId) {
        $exchangeMessage = "This exchange request is no longer available.";
    } elseif ($action === 'decline') {
        $declineStatement = $conn->prepare(
            "DELETE e FROM book_exchange e
             JOIN book b ON b.book_id = e.book_id
             WHERE e.exchange_id = ? AND b.user_id = ?"
        );
        $declineStatement->bind_param("ii", $exchangeId, $userId);
        $actionSucceeded = $declineStatement->execute() && $declineStatement->affected_rows === 1;
        $exchangeMessage = $actionSucceeded
            ? "Exchange request declined."
            : "Could not decline this exchange request: " . $declineStatement->error;
        $declineStatement->close();
    } elseif ($action === 'accept') {
        $offeredBookId = (int) ($exchangeRequest['offered_book_id'] ?? 0);
        $requesterId = (int) $exchangeRequest['user_id'];
        $requestedBookId = (int) $exchangeRequest['book_id'];
        $offeredBookStatement = $conn->prepare(
            "SELECT book_id FROM book
             WHERE book_id = ? AND user_id = ? AND upload_status = 'accomplished'"
        );
        $offeredBookStatement->bind_param("ii", $offeredBookId, $requesterId);
        $offeredBookStatement->execute();
        $offeredBook = $offeredBookStatement->get_result()->fetch_assoc();
        $offeredBookStatement->close();

        if (
            !$offeredBook
            || $requesterId === $userId
            || $exchangeRequest['requested_book_status'] !== 'accomplished'
        ) {
            $exchangeMessage = "The offered or requested book is no longer available for exchange.";
        } else {
            $completeStatement = $conn->prepare(
                "DELETE b, e FROM book b
                 LEFT JOIN book_exchange e ON e.book_id = b.book_id
                 WHERE (b.book_id = ? AND b.user_id = ?)
                    OR (b.book_id = ? AND b.user_id = ?)"
            );
            $completeStatement->bind_param(
                "iiii",
                $requestedBookId,
                $userId,
                $offeredBookId,
                $requesterId
            );
            $actionSucceeded = $completeStatement->execute() && $completeStatement->affected_rows >= 2;
            $exchangeMessage = $actionSucceeded
                ? "Exchange completed. Both books are no longer listed."
                : "Could not complete this exchange: " . $completeStatement->error;
            $completeStatement->close();
        }
    } else {
        $exchangeMessage = "Invalid exchange action.";
    }

    mysqli_close($conn);
    $_SESSION['exchangeMessage'] = $exchangeMessage;
    $redirect = 'userPage.php';
    if (!$actionSucceeded && $exchangeId > 0) {
        $redirect .= '?exId=' . $exchangeId;
    }
    header("Location: " . $redirect);
    exit;
}
?>
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
<?php if ($exchangeMessage !== ""): ?>
    <p class="exchange-message"><?php echo htmlspecialchars($exchangeMessage, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php
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
            <label for="condition">Condition: </label>
        </div>
        <div class="col-75">
            <select id="condition" name="condition" required>
                <option value="">Choose condition</option>
                <option value="Excellent">Excellent</option>
                <option value="Good">Good</option>
                <option value="Poor">Poor</option>
            </select>
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
    echo "<h2>Uploaded Book List</h2>";

    $exchange_id = [];
    $exchange_username = [];
    $exchange_bookTitle = [];
    $exchange_offerTitle = [];

    if (isset($_SESSION['username'])){
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == 'Upload') {
            $title = $_POST["title"];
            $age = $_POST["age"];
            $price = $_POST["price"];
            $summary = $_POST["summary"];
            $author = $_POST["author"];
            $condition = $_POST["condition"] ?? "";
            $validConditions = ["Excellent", "Good", "Poor"];

            if (!in_array($condition, $validConditions, true)) {
                echo "Choose a valid book condition.<br><br>";
            } else {
                $age = (string) $age;
                $price = (float) $price;
                $uploadStatus = "accomplished";
                $uploadStatement = $conn->prepare(
                    "INSERT INTO book
                        (book_title, book_age, book_price, book_summary, upload_status, user_id, book_author, book_condition)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                if (!$uploadStatement) {
                    echo "Could not prepare book upload: " . htmlspecialchars($conn->error, ENT_QUOTES, "UTF-8") . "<br><br>";
                } else {
                    $uploadStatement->bind_param(
                        "ssdssiss",
                        $title,
                        $age,
                        $price,
                        $summary,
                        $uploadStatus,
                        $userId,
                        $author,
                        $condition
                    );
                    if ($uploadStatement->execute()) {
                        echo "Book uploaded and listed successfully.<br><br>";
                    } else {
                        echo "Could not upload book: " . htmlspecialchars($uploadStatement->error, ENT_QUOTES, "UTF-8") . "<br><br>";
                    }
                    $uploadStatement->close();
                }
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
                echo "<p>Condition: " . htmlspecialchars($row["book_condition"] ?? "Not specified", ENT_QUOTES, "UTF-8") . "</p>";
                echo "<p>Rating: " . htmlspecialchars($row["book_rate"] ?? "Not rated", ENT_QUOTES, "UTF-8") . "</p>";
                echo "<p>Book Summary: " . $row["book_summary"] . "</p>";
                echo "<p class='upload-status'>Upload Status: " . $row["upload_status"] . "</p>";
                echo "</div>";
                

                $requestListStatement = $conn->prepare(
                    "SELECT e.exchange_id, requester.username,
                            offered_book.book_title AS offered_book_title
                     FROM book_exchange e
                     JOIN user requester ON requester.user_id = e.user_id
                     LEFT JOIN book offered_book ON offered_book.book_id = e.offered_book_id
                     WHERE e.book_id = ?"
                );
                $bookId = (int) $row["book_id"];
                $requestListStatement->bind_param("i", $bookId);
                $requestListStatement->execute();
                $resultExchange = $requestListStatement->get_result();
                
                if ($resultExchange->num_rows > 0) {
                    while ($rowExchange = $resultExchange->fetch_assoc()) {
                        $exchange_id[$int_count] = $rowExchange["exchange_id"];
                        $exchange_username[$int_count] = $rowExchange["username"];
                        $exchange_bookTitle[$int_count] = $row["book_title"];
                        $exchange_offerTitle[$int_count] = $rowExchange["offered_book_title"] ?? "Offer unavailable";
                        $int_count++;
                    }
                }
                $requestListStatement->close();
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
    var exOfferTitle = <?php echo json_encode($exchange_offerTitle); ?>;
    const droplist = document.getElementById("myDropdown");

    for(var i = 0; i < exUsername.length; i++) {
        const request_user = document.createElement('a');
        request_user.href = "userPage.php?exId=" + exchangeId[i];
        request_user.textContent = exUsername[i] + " offers " + exOfferTitle[i] + " for " + exBookTitle[i];
        droplist.appendChild(request_user);
    }
</script>
<?php
    if (isset($_GET['exId'])) {
        $exchangeId = (int) $_GET['exId'];
        $requestStatement = $conn->prepare(
            "SELECT e.exchange_id, e.book_id, e.user_id AS requester_id,
                    e.offered_book_id, requester.username AS requester_name,
                    requested_book.book_title,
                    offered_book.book_title AS offered_book_title,
                    offered_book.user_id AS offered_book_owner_id,
                    offered_book.upload_status AS offered_book_status,
                    requested_book.upload_status AS requested_book_status
             FROM book_exchange e
             JOIN book requested_book ON requested_book.book_id = e.book_id
             JOIN user requester ON requester.user_id = e.user_id
             LEFT JOIN book offered_book ON offered_book.book_id = e.offered_book_id
             WHERE e.exchange_id = ? AND requested_book.user_id = ?"
        );
        $requestStatement->bind_param("ii", $exchangeId, $userId);
        $requestStatement->execute();
        $exchangeRequest = $requestStatement->get_result()->fetch_assoc();
        $requestStatement->close();

        if (!$exchangeRequest) {
            if ($exchangeMessage === "") {
                echo "<p class='exchange-message'>This exchange request is no longer available.</p>";
            }
        } else {
            echo "<section class='exchange-response'>";
            echo "<h2>Exchange request</h2>";
            echo "<p>" . htmlspecialchars($exchangeRequest['requester_name'], ENT_QUOTES, 'UTF-8')
                . " offers <strong>"
                . htmlspecialchars($exchangeRequest['offered_book_title'] ?? 'an unavailable book', ENT_QUOTES, 'UTF-8')
                . "</strong> for your book <strong>"
                . htmlspecialchars($exchangeRequest['book_title'], ENT_QUOTES, 'UTF-8')
                . "</strong>.</p>";

            $offerIsValid = (int) ($exchangeRequest['offered_book_id'] ?? 0) > 0
                && (int) ($exchangeRequest['offered_book_owner_id'] ?? 0) === (int) $exchangeRequest['requester_id']
                && $exchangeRequest['offered_book_status'] === 'accomplished'
                && $exchangeRequest['requested_book_status'] === 'accomplished';

            if ($offerIsValid) {
                echo "<form method='post' action='userPage.php'>";
                echo "<input type='hidden' name='exchangeId' value='" . $exchangeId . "'>";
                echo "<button type='submit' name='exchangeAction' value='accept'>Accept exchange</button>";
                echo "</form>";
            } else {
                echo "<p>The offered book is no longer available, so this request cannot be accepted.</p>";
            }

            echo "<form method='post' action='userPage.php'>";
            echo "<input type='hidden' name='exchangeId' value='" . $exchangeId . "'>";
            echo "<button class='decline-button' type='submit' name='exchangeAction' value='decline'>Decline request</button>";
            echo "</form>";
            echo "</section>";
        }
    }

    $conn->close();
?>


</div>
<?php include 'footer.php'; ?>
</body>
</html>

