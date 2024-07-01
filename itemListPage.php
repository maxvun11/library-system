<!DOCTYPE html>
<html>
<head>
    <title>Item List Page</title>
    <link rel="stylesheet" href="itemListStyles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include('nav.php');?>

    <h1>Item List Page</h1>
    

    <form method="post" action="">
        <label>Search Title:</label>
        <input type="text" name="search_title">
        <label>Search Author:</label>
        <input type="text" name="search_author">
        <input type="submit" name="search" value="Search">
    </form>

    <form method="post" action="">
        <label>Sort By:</label>
        <select name="sort_by">
            <option value=""> </option> <!-- Blank option -->
            <option value="age">Book Age</option>
            <option value="conditions">Condition</option>
            <option value="price">Price</option>
            <option value="rate">Rate</option>
        </select>
        <input type="submit" name="sort" value="Sort">

    </form>

           <div class="book-list">
            <?php
            // Establish database connection
            $db_host = 'localhost';
            $db_username = 'root';
            $db_password = '';
            $db_name = 'book_exchange_system';
            $conn = new mysqli($db_host, $db_username, $db_password, $db_name);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Process sorting
            if (isset($_POST['sort'])) {
                $sort_by = $_POST['sort_by'];
                $sql = "SELECT * FROM book ORDER BY $sort_by";
            }
            // Process searching
            elseif (isset($_POST['search'])) {
                $search_title = $_POST['search_title'];
                $search_author = $_POST['search_author'];
                $sql = "SELECT * FROM book WHERE book_title LIKE '%$search_title%' AND book_author LIKE '%$search_author%'";
            } else {
                // Default query to display all items
                $sql = "SELECT * FROM book WHERE upload_status='accomplished'";
            }

            // Execute SQL query
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<a href='itemDetailPage.php?id=" . $row["book_id"] . "' class='book-card-link'>";
                    echo "<div class='book-card'>";
                    echo "<h2>" . $row["book_title"] . "</h2>";
                    echo "<p>Author: " . $row["book_author"] . "</p>";
                    echo "<p>Book Age: " . $row["book_age"] . "</p>";
                    echo "<p>Condition: " . $row["book_condition"] . "</p>";
                    echo "<p>Price: RM " . $row["book_price"] . "</p>";
                    echo "<p>Rate: " . $row["book_rate"] . "</p>";
                    echo "</div>";
                    echo "</a>";
                }
            } else {
                echo "0 results";
            }

            // Close database connection
            $conn->close();
            ?>
        </div>
        <?php include 'footer.php'; ?> 
</body>

</html>

