<?php

$start_time = microtime(true);
$host = getenv('MYSQL_HOST');
$username = getenv('MYSQL_USER');
$password = getenv('MYSQL_PASSWORD');
$dbname = getenv('MYSQL_DATABASE');

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

for ($j = 0; $j < 1000; $j++) {
    $sql = "INSERT INTO emails (email, checked, valid) VALUES ";
    for ($i = 0; $i < 100; $i++) {
        // добавим немного некорректных емейлов к корректным
        $mailPart = rand(0, 4) == 0 ? "" : "@example.com";
        $email = "email_" . ($j*100 + $i) . $mailPart;
        $checked = 0;
        $valid = 0;
        $sql .= "('" . $email . "', " . $checked . ", " . $valid . "),";
    }
    $sql = rtrim($sql, ",");
    if ($conn->query($sql) !== true) {
        echo "Error inserting records into table Emails: " . $conn->error . "\n";
    }
}

echo "100000 records inserted into table emails successfully\n";

for ($j = 0; $j < 1000; $j++) {
    $sql = "INSERT INTO users (username, email, validts, confirmed) VALUES ";
    for ($i = 0; $i < 100; $i++) {
        $username = "user_" . ($j*100 + $i);
        $email_id = $j*100 + $i + 1;
        $validts = time() + rand(2, 7) * 24 * 60 * 60;
        /**
         * мы не будем в рамках тестового запрашивать подтверждения почтового ящика,
         * поэтому проставим рандомно
         */
        $confirmed = rand(0, 6) == 0 ? 0 : 1;
        $sql .= "('" . $username . "', " . $email_id . ", " . $validts . ", " . $confirmed . "),";
    }
    $sql = rtrim($sql, ",");
    if ($conn->query($sql) !== true) {
        echo "Error inserting records into table Users: " . $conn->error . "\n";
    }
}

echo "100000 records inserted into table users successfully\n";

$conn->close();

$end_time = microtime(true);
$execution_time = ($end_time - $start_time) * 1000; // время в миллисекундах
echo "Execution time: $execution_time ms\n";
