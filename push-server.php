<?php

use Workerman\Protocols\Http;
use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Lib\Timer;
use PHPSocketIO\SocketIO;

//--------------------------------------------------------------------------
// Global Configuration
//--------------------------------------------------------------------------

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

define('BASEPATH', realpath(__DIR__) .DS);

define('STORAGE_PATH', BASEPATH .'storage' .DS);

//--------------------------------------------------------------------------
// Load the Composer Autoloader
//--------------------------------------------------------------------------

include BASEPATH . 'vendor/autoload.php';


//--------------------------------------------------------------------------
// Server Configuration
//--------------------------------------------------------------------------

define('SENDER_PORT', 2120);

define('SERVER_HOST', '0.0.0.0');
define('SERVER_PORT', 2121);


//--------------------------------------------------------------------------
// Clients Configuration
//--------------------------------------------------------------------------

$clients = array(
    // 'appId' => 'secretKey',

    'JPNSWIRFavLVLhjI25MXXVRyMHUjjeWI' => 'PBxOhgCQbfn03qJA0TH94fYDPiNKlpYq',
    'RirfFRAqEAigkzFXlxCj1arDfqLhWiDW' => 'HJQOOlGkHVILcHRoA8M9MZ563k2IT2ki',
);


//--------------------------------------------------------------------------
// Helper Functions
//--------------------------------------------------------------------------

function is_member(array $members, $userId)
{
    return ! empty(array_filter($members, function ($member) use ($userId)
    {
        return $member['userId'] === $userId;
    }));
}


//--------------------------------------------------------------------------
// Create the Push Server
//--------------------------------------------------------------------------

// The active presence channels.
$presence = array();

// The PHPSocketIO service.
$socketIo = new SocketIO(SENDER_PORT);

// When the client initiates a connection event, set various event callbacks for connecting sockets.
foreach (array_keys($clients) as $appId) {
    $senderIo = $socketIo->of($appId);

    $senderIo->presence = array();

    $senderIo->on('connection', function ($socket) use ($senderIo)
    {
        // Triggered when the client sends a subscribe event.
        $socket->on('subscribe', function ($channel, $authKey, $data = null) use ($socket, $senderIo)
        {
            global $clients;

            //
            $socketId = $socket->id;

            $channel = (string) $channel;

            if (preg_match('#^(?:(private|presence)-)?([-a-zA-Z0-9_=@,.;]+)$#', $channel, $matches) !== 1) {
                $socket->disconnect();

                return;
            }

            $type = ! empty($matches[1]) ? $matches[1] : 'public';

            if ($type == 'public') {
                $socket->join($channel);

                return;
            }

            $appId = trim($socket->nsp->name, '/');

            $secretKey = isset($clients[$appId]) ? $clients[$appId] : null;

            if (is_null($secretKey)) {
                $socket->disconnect();

                return;
            }

            // We have a valid appId and its secretKey.
            else if ($type == 'presence') {
                $hash = hash_hmac('sha256', $socketId .':' .$channel .':' .$data, $secretKey, false);
            } else /* private channel */ {
                $hash = hash_hmac('sha256', $socketId .':' .$channel, $secretKey, false);
            }

            if ($hash !== $authKey) {
                $socket->disconnect();

                return;
            }

            $socket->join($channel);

            if ($type == 'private') {
                return;
            }

            // A presence channel additionally needs to store the subscribed member's information.
            else if (! isset($socket->nsp->presence[$channel])) {
                $socket->nsp->presence[$channel] = array();
            }

            $members =& $socket->nsp->presence[$channel];

            // Prepare the member information and add its socketId.
            $member = json_decode($data, true);

            $member['socketId'] = $socketId;

            // Determine if the user is already a member of this channel.
            $alreadyMember = is_member($members, $member['userId']);

            $members[$socketId] = $member;

            // Emit the events associated with the channel subscription.
            $items = array();

            foreach (array_values($members) as $member) {
                if (! array_key_exists($userId = $member['userId'], $items)) {
                    $items[$userId] = $member;
                }
            }

            $senderIo->to($socketId)->emit('presence:subscribed', $channel, array_values($items));

            if (! $alreadyMember) {
                $socket->to($channel)->emit('presence:joining', $channel, $member);
            }
        });

        // Triggered when the client sends a unsubscribe event.
        $socket->on('unsubscribe', function ($channel) use ($socket)
        {
            $socketId = $socket->id;

            $channel = (string) $channel;

            if ((strpos($channel, 'presence-') === 0) && isset($socket->nsp->presence[$channel])) {
                $members =& $socket->nsp->presence[$channel];

                if (array_key_exists($socketId, $members)) {
                    $member = $members[$socketId];

                    unset($member['socketId']);

                    //
                    unset($members[$socketId]);

                    if (! is_member($members, $member['userId'])) {
                        $socket->to($channel)->emit('presence:leaving', $channel, $member);
                    }
                }

                if (empty($members)) {
                    unset($socket->nsp->presence[$channel]);
                }
            }

            $socket->leave($channel);
        });

        // Triggered when the client sends a message event.
        $socket->on('channel:event', function ($channel, $event, $data) use ($socket)
        {
            if (preg_match('#^(private|presence)-(.*)#', $channel) !== 1) {
                // The specified channel is not private.

                return;
            }

            // If it is a client event and socket joined the channel, we will emit this event.
            else if ((preg_match('#^client-(.*)$#', $event) === 1) && isset($socket->rooms[$channel])) {
                $socket->to($channel)->emit($event, $channel, $data);
            }
        });

        // When the client is disconnected is triggered (usually caused by closing the web page or refresh)
        $socket->on('disconnect', function () use ($socket)
        {
            $socketId = $socket->id;

            foreach ($socket->nsp->presence as $channel => &$members) {
                if (! array_key_exists($socketId, $members)) {
                    continue;
                }

                $member = $members[$socketId];

                unset($member['socketId']);

                //
                unset($members[$socketId]);

                if (! is_member($members, $member['userId'])) {
                    $socket->to($channel)->emit('presence:leaving', $channel, $member);
                }

                $socket->leave($channel);

                if (empty($members)) {
                    unset($socket->nsp->presence[$channel]);
                }
            }
        });
    });
}

// When $socketIo is started, it listens on an HTTP port, through which data can be pushed to any channel.
$socketIo->on('workerStart', function () use ($socketIo)
{
    // Listen on a HTTP port.
    $innerHttpWorker = new Worker('http://' .SERVER_HOST .':' .SERVER_PORT);

    // Triggered when HTTP client sends data.
    $innerHttpWorker->onMessage = function ($connection) use ($socketIo)
    {
        global $clients;

        //
        $method = $_SERVER['REQUEST_METHOD'];

        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

        if ($method !== 'POST') {
            Http::header('HTTP/1.1 405 Method Not Allowed');

            return $connection->close('405 Method Not Allowed');
        }

        // It is a POST request, check its path.
        else if (preg_match('#^apps/([^/]+)/events$#', $path, $matches) !== 1) {
            Http::header('HTTP/1.1 404 Not Found');

            return $connection->close('404 Not Found');
        }

        $appId = $matches[1];

        $authKey = isset($_SERVER['HTTP_AUTHORIZATION'])
            ? str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']) : null;

        if (! array_key_exists($appId, $clients) || empty($authKey)) {
            Http::header('HTTP/1.1 400 Bad Request');

            return $connection->close('400 Bad Request');
        }

        $secretKey = $clients[$appId];

        $hash = hash_hmac('sha256', $method ."\n/" .$path .':' .json_encode($_POST), $secretKey, false);

        if ($authKey !== $hash) {
            Http::header('HTTP/1.1 403 Forbidden');

            return $connection->close('403 Forbidden');
        }

        $channels = json_decode($_POST['channels'], true);

        $event = str_replace('\\', '.', $_POST['event']);

        $data = json_decode($_POST['data'], true);

        // Get the Sender instance.
        $senderIo = $socketIo->of($appId);

        // We will try to find the Socket instance when a socketId is specified.
        $socket = null;

        if (! empty($_POST['socketId'])) {
            $socketId = $_POST['socketId'];

            if (isset($senderIo->connected[$socketId])) {
                $socket = $senderIo->connected[$socketId];
            }
        }

        foreach ($channels as $channel) {
            if (! is_null($socket)) {
                // Send the event to other subscribers, excluding this socket.
                $socket->to($channel)->emit($event, $data);
            } else {
                // Send the event to all subscribers from specified channel.
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
