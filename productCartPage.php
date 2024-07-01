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
                // Start PHP session to store selected items
                session_start();

                if (isset($_SESSION["username"])) {
                    $userId = $_SESSION["userId"];
                }

                // Function to add item to cart
                function addItemToCart($itemId) {
                    $_SESSION['cart'.$userId][$itemId] = true;
                }

                // Function to remove item from cart
                function removeItemFromCart($itemId, $userId) {
                    $cartIndex = array_search($itemId, $_SESSION["cart" . $userId]);
                    if ($cartIndex !== false) {
                        unset($_SESSION["cart" . $userId][$cartIndex]);
                        $_SESSION["cart" . $userId] = array_values($_SESSION["cart" . $userId]); // Reindex the array
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
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                // Check if it's an AJAX request and the addItem parameter is set
                if(isset($_POST['addItem'])) {
                    $itemId = $_POST['itemId'];
                    addItemToCart($itemId);
                    http_response_code(200);
                    exit();
                }

                // Check if an item is removed from cart
                if(isset($_POST['removeItem'])) {
                    $itemId = $_POST['itemId'];
                    removeItemFromCart($itemId, $userId);
                }

                // Display selected items in the cart
                if (isset($_SESSION["userId"])) {
                    if (isset($_SESSION['cart'.$userId]) && !empty($_SESSION['cart'.$userId])) {
                        foreach ($_SESSION['cart'.$userId] as $itemId) {
                            $sql = "SELECT book_title, book_price FROM book WHERE book_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $itemId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $row = $result->fetch_assoc();
                                echo "<div class='cart-item'>";
                                echo "<div class='item-details'>";
                                echo "<h3>{$row['book_title']}</h3>";
                                echo "<p>$ {$row['book_price']}</p>";
                                echo "</div>";
                                echo "<form method='post'>";
                                echo "<input type='hidden' name='itemId' value='".$itemId."'>";
                                echo "<button type='submit' name='removeItem' class='remove-button'>Remove</button>";
                                echo "</form>";
                                echo "</div>";
                            }
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
                // Calculate and display the information after purchase
                if(isset($_POST['purchase'])) {
                    // Display selected items with titles and IDs
                    echo "<h3>Books purchased</h3>";
                    echo "<ul>";
                    foreach ($_SESSION['cart'.$userId] as $itemId) {
                        $sql = "SELECT book_id, book_title FROM book WHERE book_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $itemId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            echo "<li>Book Id: {$row['book_id']} - Title: {$row['book_title']}</li>";
                        }
                    }
                    echo "</ul>";

                    // Calculate total price
                    $totalPrice = calculateTotalPrice($userId);

                    // Display total price
                    echo "<h3>Total Price:</h3>";
                    echo "<div class='total-price'>";
                    echo "<p>$ {$totalPrice}</p>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>
        <div class="purchase-button">
            <form method='post'>
                <input type='submit' name='purchase' value='Purchase' class="btn">
            </form>
        </div>
    </div>

    <?php
    // Define the function to calculate total price
    function calculateTotalPrice($userId) {
        global $conn;
        $totalPrice = 0;
        foreach ($_SESSION['cart'.$userId] as $itemId) {
            $sql = "SELECT book_price FROM book WHERE book_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $itemId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $totalPrice += $row['book_price'];
            }
        }
        return $totalPrice;
    }

    // Close database connection
    $conn->close();
    ?>
    <?php include 'footer.php'; ?> 
</body>
</html>