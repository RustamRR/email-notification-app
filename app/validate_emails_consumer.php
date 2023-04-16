<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$host = getenv('MYSQL_HOST');
$username = getenv('MYSQL_USER');
$password = getenv('MYSQL_PASSWORD');
$dbname = getenv('MYSQL_DATABASE');

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$rabbitConn = new AMQPStreamConnection(
    getenv('RABBITMQ_HOST'),
    getenv('RABBITMQ_PORT'),
    getenv('RABBITMQ_DEFAULT_USER'),
    getenv('RABBITMQ_DEFAULT_PASS')
);
$channel = $rabbitConn->channel();
$queueName = 'validate_emails';
$channel->queue_declare($queueName, false, true, false, false);

function checkEmail($email): int
{
    sleep(random_int(1, 60));
    return random_int(0, 1);
}

function validateEmail(array $data, $connection): void
{
    $valid = 0;

    if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $valid = checkEmail($data['email']);
    }

    $stmt = $connection->prepare("UPDATE emails SET checked = 1, valid = ? WHERE id = ?");
    $stmt->bind_param("ii", $valid, $data['id']);
    $stmt->execute();
}

$callback = function ($msg) use ($conn) {
    $data = json_decode($msg->body, true);

    validateEmail($data, $conn);

    echo "Processed message\n";
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};

$channel->basic_qos(0, 1, false);
$channel->basic_consume($queueName, '', false, false, false, false, $callback);

while (true) {
    try {
        $channel->wait(null, false, 5);
    } catch (PhpAmqpLib\Exception\AMQPTimeoutException $e) {
        // Если время ожидания истекло, ничего не делаем и продолжаем ждать сообщения
    }
}

$conn->close();
$channel->close();
$rabbitConn->close();
