<?php
$servername = "shopifybundle.crzkedo145qb.us-west-1.rds.amazonaws.com";
$username = "caomingkai";
$password = "moon2181";
$dbname = "shopifybundle";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// sql to create table
$a = "Myasdfd212323";
$b = "Guests";
$c= $a.$b;
$sql = "CREATE TABLE $c (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
firstname VARCHAR(30) NOT NULL unique,
lastname VARCHAR(30) NOT NULL,
email VARCHAR(50)
)";

$conn->query($sql);


$sql = "INSERT IGNORE INTO $c (firstname, lastname, email)
VALUES ('John', 'Doe', 'john@example.com');";
$sql .= "INSERT IGNORE INTO $c (firstname, lastname, email)
VALUES ('Mary', 'Moe', 'mary@example.com');";
$sql .= "INSERT IGNORE INTO $c (firstname, lastname, email)
VALUES ('Julie', 'Dooley', 'julie@example.com')";
$sql .= "INSERT IGNORE INTO $c (firstname, lastname, email)
VALUES ('Julie', 'Dooley', 'julie@example.com')";

if ($conn->multi_query($sql) === TRUE) {
    echo "New records created successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}
$conn->close();


$conn = new mysqli($servername, $username, $password, $dbname);
$sql1 = "SELECT id, firstname, lastname FROM $c";
$result = $conn->query($sql1);
echo var_dump($result);

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    }
} else {
    echo "0 results";
}


 ?>
