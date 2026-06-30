<?php
/**
 * Suppress deprecation warnings (PHP 8.2+ compatibility for phpsocket.io)
 */
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

use PHPSocketIO\SocketIO;
use Workerman\Worker;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/events/StepEvent.php';

/**
 * Create Socket.IO server on port 3000
 */
$io = new SocketIO(3000);

/**
 * Set worker name (IMPORTANT when multiple projects)
 */
$io->worker->name = 'firsttrackapi-socket';

/**
 * Create handler ONCE
 */
$handler = new StepEvent();

/**
 * Connection event
 */
$io->on('connection', function ($socket) use ($handler) {

    echo "Client connected: {$socket->id}\n";

    /**
     * Step event listener
     */
    $socket->on('step_event', function ($data) use ($socket, $handler) {

        echo "---- step_event received ----\n";
        var_dump($data);

        try {
            $handler->handle($socket, (array) $data);
        } catch (\Throwable $e) {

            echo "STEP EVENT ERROR: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";

            $socket->emit('server_error', [
                'status'  => false,
                'message' => 'Internal server error'
            ]);
        }
    });

    /**
     * Disconnect event
     */
    $socket->on('disconnect', function () {
        echo "Client disconnected\n";
    });
});

/**
 * Run Workerman
 */
Worker::runAll();