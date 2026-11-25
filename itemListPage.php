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
            $db_host = 'localhost';
            $db_username = 'root';
            $db_password = '';
            $db_name = 'book_exchange_system';
            $conn = new mysqli($db_host, $db_username, $db_password, $db_name);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $sortColumns = [
                'age' => 'b.book_age',
                'conditions' => 'b.book_condition',
                'price' => 'b.book_price',
                'rate' => 'b.book_rate'
            ];
            $query = "SELECT b.*, u.username AS uploader_username
                      FROM book b
                      JOIN user u ON u.user_id = b.user_id
                      WHERE b.upload_status = 'accomplished'";

            if (isset($_POST['sort'])) {
                $sortBy = $_POST['sort_by'] ?? '';
                $query .= " ORDER BY " . ($sortColumns[$sortBy] ?? 'b.book_title');
                $result = $conn->query($query);
            } elseif (isset($_POST['search'])) {
                $searchTitle = '%' . trim($_POST['search_title'] ?? '') . '%';
                $searchAuthor = '%' . trim($_POST['search_author'] ?? '') . '%';
                $query .= " AND b.book_title LIKE ? AND b.book_author LIKE ? ORDER BY b.book_title";
                $statement = $conn->prepare($query);
                if (!$statement) {
                    die("Could not prepare book search: " . $conn->error);
                }
                $statement->bind_param("ss", $searchTitle, $searchAuthor);
                if (!$statement->execute()) {
                    die("Could not search books: " . $statement->error);
                }
                $result = $statement->get_result();
            } else {
                $query .= " ORDER BY b.book_title";
                $result = $conn->query($query);
            }

            if (!$result) {
                die("Could not load books: " . $conn->error);
            }

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<a href='itemDetailPage.php?id=" . $row["book_id"] . "' class='book-card-link'>";
                    echo "<div class='book-card'>";
                    echo "<h2>" . htmlspecialchars($row["book_title"], ENT_QUOTES, 'UTF-8') . "</h2>";
                    echo "<p>Author: " . htmlspecialchars($row["book_author"] ?? '', ENT_QUOTES, 'UTF-8') . "</p>";
                    echo "<p class='book-uploader'>Uploaded by: " . htmlspecialchars($row["uploader_username"], ENT_QUOTES, 'UTF-8') . "</p>";
                    echo "<p>Book Age: " . htmlspecialchars($row["book_age"] ?? '', ENT_QUOTES, 'UTF-8') . "</p>";
                    echo "<p>Condition: " . htmlspecialchars($row["book_condition"] ?? 'Not specified', ENT_QUOTES, 'UTF-8') . "</p>";
                    echo "<p>Price: RM " . htmlspecialchars($row["book_price"] ?? '', ENT_QUOTES, 'UTF-8') . "</p>";
                    echo "<p>Rate: " . htmlspecialchars($row["book_rate"] ?? 'Not rated', ENT_QUOTES, 'UTF-8') . "</p>";
                    echo "</div>";
                    echo "</a>";
                }
            } else {
                echo "0 results";
            }

            if (isset($statement)) {
                $statement->close();
            }
            // Close database connection
            $conn->close();
            ?>
        </div>
        <?php include 'footer.php'; ?> 
</body>

</html>

