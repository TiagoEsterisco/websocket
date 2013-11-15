<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;


// For the CLASS
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;


// GLOBAL FETCH Dependencies
require './vendor/autoload.php';



// CLASS ( should be in a diferent file )
class Chat implements MessageComponentInterface {
    protected $clients;


    public function __construct() {
        $this->clients = array();
        $this->client_id = array();

    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients[$conn->resourceId] = $conn;
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {

        // Message
        $msg_object = json_decode($msg);
        $msg        = $msg_object->{'msg'};


        // Depending on the type of MSG we can do several actions.
        // In this case we dont send msg to no one just store user id with is server resource Id
        if($msg_object->{'type'}==0) {

            // Store online users to our object.
            $this->client_id[$msg_object->{'id'}] =  $from->resourceId;

        } else {

            // send to the Id selected by user
            // we just get the server resource ID
            $to     = $this->client_id[$msg_object->{'destiny'}];
            $client = $this->clients[$to];
            // Send Msg
            $client->send($msg);
        }

        // $numRecv = count($this->clients) - 1;

        // Just debug information
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        // foreach ($this->clients as $key => $client) {
        //     if ($from !== $client) {
        //         // The sender is not the receiver, send to each client connected
        //         $client->send($msg);
        //     }
        // }


    }



    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        unset($this->clients[$conn->resourceId]);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}





// SERVR
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
            )
        ),
    8080
    );

$server->run();