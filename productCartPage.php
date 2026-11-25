<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Cart</title>
    <link rel="stylesheet" href="cartStyles.css">
</head>
<body>
<?php include 'header.php'; ?>
    <?php include('nav.php');?>
    <div class="main-container">
        <h1>Product Cart</h1>
        <div class="cart-container">
            <h2>Selected Items</h2>
            <div class="cart-items">
                <?php
                $userId = (int) ($_SESSION["userId"] ?? 0);
                $cartKey = "cart" . $userId;

                // Function to add item to cart
                function addItemToCart($itemId, $userId) {
                    $cartKey = "cart" . $userId;
                    if (!isset($_SESSION[$cartKey])) {
                        $_SESSION[$cartKey] = [];
                    }
                    if (!in_array($itemId, $_SESSION[$cartKey], true)) {
                        $_SESSION[$cartKey][] = $itemId;
                    }
                }

                // Function to remove item from cart
                function removeItemFromCart($itemId, $userId) {
                    $cartKey = "cart" . $userId;
                    if (!isset($_SESSION[$cartKey])) {
                        return;
                    }
                    $cartIndex = array_search((int) $itemId, $_SESSION[$cartKey], true);
                    if ($cartIndex !== false) {
                        unset($_SESSION[$cartKey][$cartIndex]);
                        $_SESSION[$cartKey] = array_values($_SESSION[$cartKey]);
                    }
                }

                // Database connection
                $servername = "localhost";
                $username = "root";
                $password = "";
                $dbname = "book_exchange_system";

                // Create connection
                $conn = mysqli_connect($servername, $username, $password, $dbname);

                // Check connection
                if (!$conn) {
                    die("Connection failed: " . mysqli_connect_error());
                }

                if ($userId > 0 && isset($_SESSION[$cartKey])) {
                    $normalizedCart = [];
                    foreach ($_SESSION[$cartKey] as $key => $value) {
                        $itemId = is_numeric($value) ? (int) $value : (int) $key;
                        if ($itemId > 0 && !in_array($itemId, $normalizedCart, true)) {
                            $normalizedCart[] = $itemId;
                        }
                    }
                    $_SESSION[$cartKey] = $normalizedCart;
                }

                // Check if it's an AJAX request and the addItem parameter is set
                if(isset($_POST['addItem'])) {
                    $itemId = (int) ($_POST['itemId'] ?? 0);
                    if ($userId <= 0 || $itemId <= 0) {
                        http_response_code(400);
                        exit();
                    }
                    $availabilityStatement = $conn->prepare(
                        "SELECT book_id FROM book
                         WHERE book_id = ? AND upload_status = 'accomplished' AND user_id <> ?"
                    );
                    if (!$availabilityStatement) {
                        die("Could not prepare book availability check: " . $conn->error);
                    }
                    $availabilityStatement->bind_param("ii", $itemId, $userId);
                    if (!$availabilityStatement->execute()) {
                        die("Could not check book availability: " . $availabilityStatement->error);
                    }
                    $isAvailable = $availabilityStatement->get_result()->num_rows === 1;
                    $availabilityStatement->close();
                    if (!$isAvailable) {
                        http_response_code(404);
                        exit();
                    }
                    addItemToCart($itemId, $userId);
                    http_response_code(200);
                    exit();
                }

                // Check if an item is removed from cart
                if(isset($_POST['removeItem'])) {
                    $itemId = (int) ($_POST['itemId'] ?? 0);
                    if ($userId > 0) {
                        removeItemFromCart($itemId, $userId);
                    }
                }

                $purchaseRequested = isset($_POST['purchase']);
                $purchasedBooks = [];
                $unavailableBooks = [];
                $purchaseTotal = 0.0;
                $purchaseError = "";
                if ($purchaseRequested) {
                    if ($userId <= 0) {
                        $purchaseError = "Please log in before purchasing books.";
                    } elseif (empty($_SESSION[$cartKey])) {
                        $purchaseError = "Your cart is empty.";
                    } else {
                        foreach ($_SESSION[$cartKey] as $itemId) {
                            $itemId = (int) $itemId;
                            $bookStatement = $conn->prepare(
                                "SELECT book_title, book_price FROM book
                                 WHERE book_id = ? AND upload_status = 'accomplished' AND user_id <> ?"
                            );
                            if (!$bookStatement) {
                                die("Could not prepare book lookup: " . $conn->error);
                            }
                            $bookStatement->bind_param("ii", $itemId, $userId);
                            if (!$bookStatement->execute()) {
                                die("Could not check book availability: " . $bookStatement->error);
                            }
                            $book = $bookStatement->get_result()->fetch_assoc();
                            $bookStatement->close();

                            if (!$book) {
                                $unavailableBooks[] = $itemId;
                                continue;
                            }

                            $delistStatement = $conn->prepare(
                                "UPDATE book SET upload_status = 'sold'
                                 WHERE book_id = ? AND upload_status = 'accomplished' AND user_id <> ?"
                            );
                            if (!$delistStatement) {
                                die("Could not prepare book delisting: " . $conn->error);
                            }
                            $delistStatement->bind_param("ii", $itemId, $userId);
                            if (!$delistStatement->execute()) {
                                die("Could not delist purchased book: " . $delistStatement->error);
                            }
                            $wasDelisted = $delistStatement->affected_rows === 1;
                            $delistStatement->close();

                            if (!$wasDelisted) {
                                $unavailableBooks[] = $itemId;
                                continue;
                            }

                            $purchasedBooks[] = [
                                "book_id" => $itemId,
                                "book_title" => $book["book_title"],
                                "book_price" => (float) $book["book_price"]
                            ];
                            $purchaseTotal += (float) $book["book_price"];

                            $requestCleanup = $conn->prepare(
                                "DELETE FROM book_exchange WHERE book_id = ? OR offered_book_id = ?"
                            );
                            if (!$requestCleanup) {
                                die("Could not prepare exchange request cleanup: " . $conn->error);
                            }
                            $requestCleanup->bind_param("ii", $itemId, $itemId);
                            if (!$requestCleanup->execute()) {
                                die("Could not clear exchange requests for purchased book: " . $requestCleanup->error);
                            }
                            $requestCleanup->close();
                        }
                        $_SESSION[$cartKey] = [];
                    }
                }

                // Display selected items in the cart
                if (isset($_SESSION["userId"])) {
                    if (isset($_SESSION['cart'.$userId]) && !empty($_SESSION['cart'.$userId])) {
                        foreach ($_SESSION['cart'.$userId] as $itemId) {
                            $sql = "SELECT book_title, book_price FROM book
                                    WHERE book_id = ? AND upload_status = 'accomplished' AND user_id <> ?";
                            $stmt = $conn->prepare($sql);
                            if (!$stmt) {
                                die("Could not prepare cart item lookup: " . $conn->error);
                            }
                            $stmt->bind_param("ii", $itemId, $userId);
                            if (!$stmt->execute()) {
                                die("Could not load cart item: " . $stmt->error);
                            }
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $row = $result->fetch_assoc();
                                echo "<div class='cart-item'>";
                                echo "<div class='item-details'>";
                                echo "<h3>" . htmlspecialchars($row['book_title'], ENT_QUOTES, "UTF-8") . "</h3>";
                                echo "<p>$ " . htmlspecialchars($row['book_price'], ENT_QUOTES, "UTF-8") . "</p>";
                                echo "</div>";
                                echo "<form method='post'>";
                                echo "<input type='hidden' name='itemId' value='".$itemId."'>";
                                echo "<button type='submit' name='removeItem' class='remove-button'>Remove</button>";
                                echo "</form>";
                                echo "</div>";
                            } else {
                                echo "<div class='cart-item'>";
                                echo "<p>This book is no longer available.</p>";
                                echo "<form method='post'>";
                                echo "<input type='hidden' name='itemId' value='" . (int) $itemId . "'>";
                                echo "<button type='submit' name='removeItem' class='remove-button'>Remove</button>";
                                echo "</form>";
                                echo "</div>";
                            }
                            $stmt->close();
                        }
                    } else {
                        echo "<p>Your cart is empty.</p>";
                    }
                } else {
                    echo "<p>Please Login</p>";
                }
                ?>
            </div>
            <div class="cart-total">
                <?php
                if ($purchaseError !== "") {
                    echo "<p class='purchase-message'>" . htmlspecialchars($purchaseError, ENT_QUOTES, "UTF-8") . "</p>";
                }
                if (!empty($purchasedBooks)) {
                    echo "<h3>Books purchased</h3>";
                    echo "<ul>";
                    foreach ($purchasedBooks as $book) {
                        echo "<li>Book Id: " . (int) $book["book_id"] . " - Title: "
                            . htmlspecialchars($book["book_title"], ENT_QUOTES, "UTF-8") . "</li>";
                    }
                    echo "</ul>";
                    echo "<h3>Total Price:</h3>";
                    echo "<div class='total-price'>";
                    echo "<p>$ " . number_format($purchaseTotal, 2) . "</p>";
                    echo "</div>";
                }
                if (!empty($unavailableBooks)) {
                    echo "<p class='purchase-message'>These books were no longer available and were not purchased: "
                        . htmlspecialchars(implode(", ", array_map("intval", $unavailableBooks)), ENT_QUOTES, "UTF-8")
                        . "</p>";
                }
                ?>
            </div>
        </div>
        <div class="purchase-button">
            <?php if ($userId > 0 && !empty($_SESSION[$cartKey])): ?>
                <form method='post'>
                    <input type='submit' name='purchase' value='Purchase' class="btn">
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // Close database connection
    $conn->close();
    ?>
    <?php include 'footer.php'; ?> 
</body>
</html>