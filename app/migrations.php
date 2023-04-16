<?php

$host = getenv('MYSQL_HOST');
$username = getenv('MYSQL_USER');
$password = getenv('MYSQL_PASSWORD');
$dbname = getenv('MYSQL_DATABASE');

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS emails (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  checked TINYINT(1) NOT NULL DEFAULT 0,
  valid TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE(email)
)";
if ($conn->query($sql) === TRUE) {
    echo "Table Emails created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$sql = "CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL,
  email INT UNSIGNED NOT NULL,
  validts BIGINT NOT NULL,
  confirmed TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE(username),
  FOREIGN KEY (email) REFERENCES emails(id)
)";
if ($conn->query($sql) === TRUE) {
    echo "Table Users created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();
