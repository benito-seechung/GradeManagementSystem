<?php
$conn = new mysqli("localhost", "root", "", "minds_that_matter_db");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
?>
