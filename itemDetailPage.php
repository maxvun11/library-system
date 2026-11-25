<?php session_start(); ?>
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
        $offerBooks = [];
        $exchangeMessage = "";
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
        $bookStatus = $row["upload_status"];

        if (isset($_SESSION["username"])) {
            $checkRequest = $conn->prepare("SELECT exchange_id FROM book_exchange WHERE book_id = ? AND user_id = ?");
            $checkRequest->bind_param("ii", $id, $userId);
            $checkRequest->execute();
            $request = $checkRequest->get_result()->num_rows > 0 ? "pending" : null;
            $checkRequest->close();

            if ($ownerId != $userId) {
                $offerStatement = $conn->prepare(
                    "SELECT book_id, book_title FROM book
                     WHERE user_id = ? AND upload_status = 'accomplished'
                     ORDER BY book_title"
                );
                $offerStatement->bind_param("i", $userId);
                $offerStatement->execute();
                $offerBooks = $offerStatement->get_result()->fetch_all(MYSQLI_ASSOC);
                $offerStatement->close();
            }

            if (isset($_POST["exchangeRequest"])) {
                if ($ownerId == $userId) {
                    $exchangeMessage = "You cannot exchange your own book.";
                } elseif ($bookStatus !== "accomplished") {
                    $exchangeMessage = "Only listed books can be requested for exchange.";
                } elseif ($request === "pending") {
                    $exchangeMessage = "You have already requested this book.";
                } else {
                    $offeredBookId = (int) ($_POST["offeredBookId"] ?? 0);
                    $validOfferStatement = $conn->prepare(
                        "SELECT book_id FROM book
                         WHERE book_id = ? AND user_id = ? AND upload_status = 'accomplished'"
                    );
                    $validOfferStatement->bind_param("ii", $offeredBookId, $userId);
                    $validOfferStatement->execute();
                    $validOffer = $validOfferStatement->get_result()->num_rows > 0;
                    $validOfferStatement->close();

                    if (!$validOffer) {
                        $exchangeMessage = "Choose one of your listed books to offer.";
                    } else {
                        $insertRequest = $conn->prepare(
                            "INSERT INTO book_exchange (book_id, user_id, offered_book_id) VALUES (?, ?, ?)"
                        );
                        $insertRequest->bind_param("iii", $id, $userId, $offeredBookId);
                        if ($insertRequest->execute()) {
                            $request = "pending";
                            $exchangeMessage = "Exchange request sent.";
                        } else {
                            $exchangeMessage = "Could not send exchange request: " . $insertRequest->error;
                        }
                        $insertRequest->close();
                    }
                }
            }
        }
        
        ?>

        <div class="book-details">
            <h1><?php echo $title; ?></h1>
            <p><?php echo $summary; ?></p>
            <?php if ($exchangeMessage !== ""): ?>
                <p class="message"><?php echo htmlspecialchars($exchangeMessage, ENT_QUOTES, "UTF-8"); ?></p>
            <?php endif; ?>
            <div class="details">
                <div class="detail">
                    <span class="label">Author:</span>
                    <span class="value"><?php echo $author; ?></span>
                </div>
                <div class="detail">
                    <span class="label">Condition:</span>
                    <span class="value"><?php echo htmlspecialchars($condition ?? "Not specified", ENT_QUOTES, "UTF-8"); ?></span>
                </div>
                <div class="detail">
                    <span class="label">Rating:</span>
                    <span class="value"><?php echo htmlspecialchars($rate ?? "Not rated", ENT_QUOTES, "UTF-8"); ?></span>
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
                <?php if ($request === "pending"): ?>
                    <p class="message blue">Exchange request is pending.</p>
                <?php elseif ($username === ""): ?>
                    <a class="exchange-login" href="login.php">Log in to request an exchange</a>
                <?php elseif ($ownerId == $userId): ?>
                    <p class="message">This is your book.</p>
                <?php elseif ($bookStatus !== "accomplished"): ?>
                    <p class="message">Only listed books can be exchanged.</p>
                <?php elseif (count($offerBooks) === 0): ?>
                    <p class="message">You need a book listed in the catalog before requesting an exchange.</p>
                <?php else: ?>
                    <form method="post" class="exchange-request-form">
                        <label for="offeredBookId">Choose one of your books to offer:</label>
                        <select id="offeredBookId" name="offeredBookId" required>
                            <option value="">Choose a book</option>
                            <?php foreach ($offerBooks as $offerBook): ?>
                                <option value="<?php echo (int) $offerBook['book_id']; ?>">
                                    <?php echo htmlspecialchars($offerBook['book_title'], ENT_QUOTES, "UTF-8"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button id="exchange" type="submit" name="exchangeRequest" value="1">Request exchange</button>
                    </form>
                <?php endif; ?>
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
            if ($bookStatus !== "accomplished") {
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addCart']) && $_POST['addCart'] == 'Add To Cart') {
                    echo "<p class='message'>This book is no longer available.</p>";
                }
            } elseif ($ownerId != $userId) {
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
    </div>
    <?php include 'footer.php'; ?> 
</body>
</html>