<?php
echo "hola mundo";
/*
$array = array("foo", "bar", "hello", "world");
$conn=mysql_connect('localhost', 'u4477', 'osgv67');
mysql_select_db("spectrum",$conn);
$array_string=(serialize($array));
$q = "INSERT INTO `table`(`column`) VALUES ('ot222ooo')";
echo $q;
$resultado=mysql_query($q,$conn);
if (!$resultado) {
    die('Consulta no valida: ' . mysql_error());
}
*/

$servername = "localhost";
$username = "u4477";
$password = "osgv67";
$dbname = "spectrum";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT `id`, `userid`, `name`, `description`, `nodeid`, `processList`, `time`, `value` FROM `input` WHERE 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "id: " . $row["id"]. " - userid: " . $row["userid"]. " " . $row["name"]. "<br>";
    }
} else {
    echo "0 results";
}
$dato=123.345;

$sql = "INSERT INTO `table`(`column`) VALUES ($dato)";
$result = $conn->query($sql);


    echo $result;


$conn->close();

?>