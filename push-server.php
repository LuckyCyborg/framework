<?php

use Workerman\Protocols\Http;
use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Lib\Timer;
use PHPSocketIO\SocketIO;

//--------------------------------------------------------------------------
// Global Constants
//--------------------------------------------------------------------------

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

define('BASEPATH', realpath(__DIR__) .DS);

define('STORAGE_PATH', BASEPATH .'storage' .DS);


//--------------------------------------------------------------------------
// Server Configuration
//--------------------------------------------------------------------------

define('SENDER_PORT', 2120);

define('SERVER_HOST', '0.0.0.0');
define('SERVER_PORT', 2121);

define('SECRET_KEY', 'SomeRandomStringThere_1234567890');


//--------------------------------------------------------------------------
// Load the Composer Autoloader
//--------------------------------------------------------------------------

include BASEPATH . 'vendor/autoload.php';


//--------------------------------------------------------------------------
// Create the Push Server
//--------------------------------------------------------------------------

// The PHPSocketIO service.
$senderIo = new SocketIO(SENDER_PORT);

// When the client initiates a connection event, set various event callbacks for connecting sockets.
$senderIo->on('connection', function ($socket)
{
    // Triggered when the client sends a authenticate event.
    $socket->on('authenticate', function ($channel, $payload) use ($socket)
    {
        $channel = (string) $channel;

        if (preg_match('#^(?:(private|presence)-)?([-a-zA-Z0-9_=@,.;]+)$#', $channel, $matches) !== 1) {
            Worker::log("Invalid channel name [{$channel}]");

            $socket->disconnect();

            return;
        }

        $channelType = ! empty($matches[1]) ? $matches[1] : 'public';

        if ($channelType == 'public') {
            $socket->join($channel);

            return;
        }

        // A private or presence channel.
        else if (isset($payload['channel_data']) && ! empty(payload['channel_data'])) {
            $channelData = $payload['channel_data'];

            $hash = hash_hmac('sha256', $socket->id .':' .$channel .':' .$channelData, SECRET_KEY, false);

            // Decode the custom data.
            $channelData = json_decode($channelData);
        } else {
            $hash = hash_hmac('sha256', $socket->id .':' .$channel, SECRET_KEY, false);

            $channelData = null;
        }

        if ($hash !== $payload['auth']) {
            Worker::log("Invalid hash [$hash] for channel [$channel]");

            $socket->disconnect();

            return;
        }

        // The socket can join this channel.
        else if (! is_null($channelData)) {
            // Do something with the channel data.
        }

        $socket->join($channel);
    });

    // When the client is disconnected is triggered (usually caused by closing the web page or refresh)
    $socket->on('disconnect', function () use ($socket)
    {
        // Do something here.
    });
});

// When $senderIo is started, it listens on an HTTP port, through which data can be pushed to any channel.
$senderIo->on('workerStart', function () use ($senderIo)
{
    // Listen on a HTTP port.
    $innerHttpWorker = new Worker('http://' .SERVER_HOST .':' .SERVER_PORT);

    // Triggered when HTTP client sends data.
    $innerHttpWorker->onMessage = function ($connection) use ($senderIo)
    {
        $method = $_SERVER['REQUEST_METHOD'];

        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

        if ($method !== 'POST') {
            Http::header('HTTP/1.1 405 Method Not Allowed');

            return $connection->close('405 Method Not Allowed');
        }

        $authToken = isset($_SERVER['HTTP_AUTHORIZATION'])
            ? str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']) : null;

        if (empty($authToken) || ($path != 'events')) {
            Http::header('HTTP/1.1 400 Bad Request');

            return $connection->close('400 Bad Request');
        }

        $hash = hash_hmac('sha256', $method ."\n" .$path .':' .json_encode($_POST), SECRET_KEY, false);

        if ($authToken !== $hash) {
            Http::header('HTTP/1.1 403 Forbidden');

            return $connection->close('403 Forbidden');
        }

        if (isset($_POST['channels'])) {
            $channels = $_POST['channels'];
        } else if (isset($_POST['channel'])) {
            $channels = (array) $_POST['channel'];
        } else {
            $channels = array();
        }

        $event = $_POST['event'];

        $data = json_decode($_POST['data']);

        $socket = null;

        if (isset($_POST['socketId'])) {
            $socketId = $_POST['socketId'];

            // Get the connected sockets.
            $connected = $senderIo->sockets->connected;

            if (isset($connected[$socketId]) {
                $socket = $connected[$socketId];
            }
        }

        foreach ($channels as $channel) {
            if (! is_null($socket)) {
                // Send to other subscribers.
                $socket->to($channel)->emit($event, $data);
            } else {
                // Send to all subscribers.
                $senderIo->to($channel)->emit($event, $data);
            }
        }

        Http::header('HTTP/1.1 200 OK');

        return $connection->close('200 OK');
    };

    // Perform monitoring.
    $innerHttpWorker->listen();
});


//--------------------------------------------------------------------------
// Setup the Workerman Environment
//--------------------------------------------------------------------------

if (! file_exists($pidPath = STORAGE_PATH .'workerman')) {
    mkdir($pidPath, 0755, true);
}

Worker::$pidFile = $pidPath .DS .sha1(__FILE__) .'.pid';

Worker::$logFile = STORAGE_PATH .'logs' .DS .'workerman.log';


//--------------------------------------------------------------------------
// Run all Workers
//--------------------------------------------------------------------------

Worker::runAll();
