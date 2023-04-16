<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

function sendEmail($email, $from, $to, $subj, $body): true
{
    sleep(rand(1, 10));
    return true;
}

$rabbitHost = getenv('RABBITMQ_HOST');
$rabbitPort = getenv('RABBITMQ_PORT');
$rabbitUser = getenv('RABBITMQ_DEFAULT_USER');
$rabbitPass = getenv('RABBITMQ_DEFAULT_PASS');

$rabbitConn = new AMQPStreamConnection('rabbitmq', 5672, $rabbitUser, $rabbitPass, '/');
$channel = $rabbitConn->channel();
$queueName = 'user_email_notification';

$channel->queue_declare($queueName, false, true, false, false);

$callback = function($msg) {
    $data = json_decode($msg->body, true);
    $email = $data['email'];
    $from = 'noreply@example.com';
    $to = $email;
    $subj = 'Test Subject';
    $body = 'Test Body';
    $result = sendEmail($email, $from, $to, $subj, $body);
    if ($result) {
        echo "Email sent to $email\n";
    } else {
        echo "Failed to send email to $email\n";
    }
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

$channel->close();
$rabbitConn->close();
