<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$startTime = microtime(true);

function generateUserEmails($mysqli, $batch_size = 1000): Generator
{
    $offset = 0;
// Закомментил просто потому что устал ждать пока у меня появится нужная запись, соответсвующая условию u.validts BETWEEN UNIX_TIMESTAMP(DATE(NOW())) AND UNIX_TIMESTAMP(DATE_ADD(DATE(NOW()), INTERVAL 1 DAY))
// При желании, можно раскомментить, вдруг повезет или по фикстурам можно поправить разброс дней, сейчас рандомно от 2 до 7
//    $sql = "SELECT u.username, e.email FROM users u
//            INNER JOIN emails e ON u.email = e.id
//            WHERE u.confirmed = 1 AND e.checked = 1 AND e.valid = 1 AND u.validts BETWEEN UNIX_TIMESTAMP(DATE(NOW())) AND UNIX_TIMESTAMP(DATE_ADD(DATE(NOW()), INTERVAL 1 DAY))
//            LIMIT ? OFFSET ?";
    $sql = "SELECT u.username, e.email FROM users u 
            INNER JOIN emails e ON u.email = e.id 
            WHERE u.confirmed = 1 AND e.checked = 1 AND e.valid = 1 
            LIMIT ? OFFSET ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $batch_size, $offset);

    do {
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            yield $row;
        }

        $offset += $batch_size;
    } while ($result->num_rows > 0);
    $result->free_result();
}

$host = getenv('MYSQL_HOST');
$username = getenv('MYSQL_USER');
$password = getenv('MYSQL_PASSWORD');
$dbname = getenv('MYSQL_DATABASE');

$rabbitHost = getenv('RABBITMQ_HOST');
$rabbitPort = getenv('RABBITMQ_PORT');
$rabbitUser = getenv('RABBITMQ_DEFAULT_USER');
$rabbitPass = getenv('RABBITMQ_DEFAULT_PASS');

$rabbitConn = new AMQPStreamConnection('rabbitmq', 5672, $rabbitUser, $rabbitPass, '/');
$channel = $rabbitConn->channel();
$queueName = 'user_email_notification';

$channel->queue_declare($queueName, false, true, false, false);

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$messageCount = 0;

foreach (generateUserEmails($conn) as $row) {
    $username = $row['username'];
    $email = $row['email'];
    $msg = new AMQPMessage(json_encode(compact('username', 'email')));
    $channel->basic_publish($msg, '', $queueName);
    $messageCount++;
}

echo "Finished! Sent $messageCount messages\n";

$channel->close();
$rabbitConn->close();
$conn->close();

$endTime = microtime(true);
$executionTime = ($endTime - $startTime) * 1000;
echo "Execution time: $executionTime ms\n";
