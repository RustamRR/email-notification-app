<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$startTime = microtime(true);

function generateEmails($mysqli, $batch_size = 1000): Generator
{
    $offset = 0;
    $sql = "SELECT id, email FROM emails WHERE checked = 0 LIMIT $batch_size OFFSET ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $offset);

    do {
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            yield json_encode(['id' => $row['id'], 'email' => $row['email']]);
        }

        $offset += $batch_size;
    } while ($result->num_rows > 0);

    $result->free_result();
}

$host = getenv('MYSQL_HOST');
$username = getenv('MYSQL_USER');
$password = getenv('MYSQL_PASSWORD');
$dbname = getenv('MYSQL_DATABASE');

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$rabbitHost = getenv('RABBITMQ_HOST');
$rabbitPort = getenv('RABBITMQ_PORT');
$rabbitUser = getenv('RABBITMQ_DEFAULT_USER');
$rabbitPass = getenv('RABBITMQ_DEFAULT_PASS');

$rabbitConn = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPass);
$channel = $rabbitConn->channel();

$queueName = 'validate_emails';

$channel->queue_declare($queueName, false, true, false, false);

$messageCount = 0;

foreach (generateEmails($conn) as $json) {
    $msg = new AMQPMessage($json);
    $channel->basic_publish($msg, '', $queueName);
    $messageCount++;
}

echo "$messageCount messages sent to queue $queueName\n";

$channel->close();
$rabbitConn->close();

$endTime = microtime(true);
$executionTime = ($endTime - $startTime) * 1000;
echo "Execution time: $executionTime ms\n";
