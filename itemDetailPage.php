<!DOCTYPE html>
<html>
<head>
    <title>Item Detail Page</title>
    <link rel="stylesheet" href="itemDetailStyles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include('nav.php'); ?>
    <div class="main">
        <?php
        session_start();

        if (isset($_GET['id'])) {
            // Retrieve the id value from the URL
            $id = (int) $_GET['id'];
        } else {
            echo "No id is found.";
        }

        $username = $_SESSION["username"] ?? "";
        $userId = $_SESSION["userId"] ?? 0;

        $db_host = 'localhost';
        $db_username = 'root';
        $db_password = '';
        $db_name = 'book_exchange_system';

        $conn = mysqli_connect($db_host, $db_username, $db_password, $db_name);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $request = null;
        $sql = "SELECT * FROM book WHERE book_id=" . $id;
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $title = $row["book_title"];
        $age = $row["book_age"];
        $condition = $row["book_condition"];
        $price = $row["book_price"];
        $rate = $row["book_rate"];
        $summary = $row["book_summary"];
        $author = $row["book_author"];
        $ownerId = $row["user_id"];

        if (isset($_SESSION["username"])) {
            if (isset($_POST["exchangeRequest"]) && $ownerId != $userId) {
                $checkRequest = $conn->prepare("SELECT exchange_id FROM book_exchange WHERE book_id = ? AND user_id = ?");
                $checkRequest->bind_param("ii", $id, $userId);
                $checkRequest->execute();
                $existingRequest = $checkRequest->get_result();

                if ($existingRequest->num_rows === 0) {
                    $insertRequest = $conn->prepare("INSERT INTO book_exchange (book_id, user_id) VALUES (?, ?)");
                    $insertRequest->bind_param("ii", $id, $userId);
                    if ($insertRequest->execute()) {
                        $request = "pending";
                    } else {
                        echo "<p class='message'>Could not send exchange request: " . htmlspecialchars($insertRequest->error, ENT_QUOTES, "UTF-8") . "</p>";
                    }
                    $insertRequest->close();
                } else {
                    $request = "pending";
                }

                $checkRequest->close();
            } else {
                $checkRequest = $conn->prepare("SELECT exchange_id FROM book_exchange WHERE book_id = ? AND user_id = ?");
                $checkRequest->bind_param("ii", $id, $userId);
                $checkRequest->execute();
                $request = $checkRequest->get_result()->num_rows > 0 ? "pending" : null;
                $checkRequest->close();
            }
        }
        
        ?>

        <div class="book-details">
            <h1><?php echo $title; ?></h1>
            <p><?php echo $summary; ?></p>
            <div class="details">
                <div class="detail">
                    <span class="label">Author:</span>
                    <span class="value"><?php echo $author; ?></span>
                </div>
                <div class="detail">
                    <span class="label">Condition:</span>
                    <span class="value"><?php echo $condition; ?></span>
                </div>
                <div class="detail">
                    <span class="label">Rating:</span>
                    <span class="value"><?php echo $rate; ?></span>
                </div>
                <div class="detail">
                    <span class="label">Age:</span>
                    <span class="value"><?php echo $age; ?></span>
                </div>
                <div class="detail">
                    <span class="label">Price:</span>
                    <span class="value">RM <?php echo $price; ?></span>
                </div>
            </div>
            <div class="actions">
                <form method="post">
                    <button id="exchange" type="submit" name="exchangeRequest" value="1">Exchange Book</button>
                </form>
                <form method="post" action="itemDetailPage.php?id=<?php echo $id; ?>">
                    <input type="submit" name="addCart" value="Add To Cart">
                </form>
            </div>
        </div>

        <?php
        if (isset($_SESSION["username"])) {
            if (!isset($_SESSION['cart' . $userId])) {
                $_SESSION['cart' . $userId] = array();
            }
            if ($ownerId != $userId) {
                if (!in_array($id, $_SESSION['cart' . $userId])) {
                    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addCart']) && $_POST['addCart'] == 'Add To Cart') {
                        $_SESSION['cart' . $userId][] = $id;
                    }
                } else {
                    echo "<p class='message'>The book is already added to cart</p>";
                }
            } else {
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addCart']) && $_POST['addCart'] == 'Add To Cart') {
                    echo "<p class='message'>Cannot add your own book to the shopping cart</p>";
                }
            }
        } elseif (!isset($_SESSION["username"])) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addCart']) && $_POST['addCart'] == 'Add To Cart') {
                echo "<p class='message'>Please login to add this book to your cart</p>";
            }
        }
        ?>
        <script>
            let book_id = "<?php echo "$id"?>";
            let username = "<?php echo "$username" ?>";
            let userId = "<?php echo "$userId" ?>";
            let ownerId = "<?php echo "$ownerId" ?>";
            let request = "<?php echo "$request" ?>";
            const body = document.body;
            const exchange = document.getElementById("exchange");

            if (request == "pending") {
                exchange.remove();
                const promp = document.createElement("p");
                promp.textContent = "Exchange request is sent";
                promp.classList.add("message", "blue");
                body.appendChild(promp);
            } else {
                exchange.addEventListener("click", (event) => {
                    if (!username) {
                            event.preventDefault();
                        const promp = document.createElement("p");
                        promp.textContent = "Please login";
                        promp.classList.add("message", "red");
                        body.appendChild(promp);
                    } else if (userId == ownerId) {
                            event.preventDefault();
                        const promp = document.createElement("p");
                        promp.textContent = "Cannot exchange your own book";
                        promp.classList.add("message", "red");
                        body.appendChild(promp);
                    }
                });
            }
        </script>
    </div>
    <?php include 'footer.php'; ?> 
</body>
</html>