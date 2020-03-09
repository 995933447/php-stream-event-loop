<?php
/*
 * Simple echo server based on libevent's connection listener.
 *
 * Usage:
 * 1) In one terminal window run:
 *
 * $ php listener.php 9881
 *
 * 2) In another terminal window open up connection, e.g.:
 *
 * $ nc 127.0.0.1 9881
 *
 * 3) start typing. The server should repeat the input.
 */

class MyListenerConnection {
    private $bev, $base;

    public function __destruct() {
        $this->bev->free();
    }

    public function __construct($base, $fd) {
        $this->base = $base;
       
        $this->bev = new EventBufferEvent($base, $fd, EventBufferEvent::OPT_CLOSE_ON_FREE);

        $this->bev->setCallbacks(array($this, "echoReadCallback"), NULL,
            array($this, "echoEventCallback"), NULL);

        if (!$this->bev->enable(Event::READ)) {
            echo "Failed to enable READ\n";
            return;
        }
    }

    public function echoReadCallback($bev, $ctx) {
        // Copy all the data from the input buffer to the output buffer
        
        // Variant #1
        echo "Data:" . $bev->getInput()->pullup(-1) . PHP_EOL;
        $bev->write("HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nHi");
        /* Variant #2 */
        /*
        $input    = $bev->getInput();
        $output = $bev->getOutput();
        $output->addBuffer($input);
        */
    }

    public function echoEventCallback($bev, $events, $ctx) {
        if ($events & EventBufferEvent::ERROR) {
            echo "Error from bufferevent\n";
        }

        if ($events & (EventBufferEvent::EOF | EventBufferEvent::ERROR)) {
            //$bev->free();
            $this->__destruct();
        }
    }
}

class MyListener {
    public $base,
        $listener,
        $socket;
    private $conn = array();

    public function __destruct() {
        foreach ($this->conn as &$c) $c = NULL;
    }

    public function __construct($port) {
        $this->base = new EventBase();
        if (!$this->base) {
            echo "Couldn't open event base";
            exit(1);
        }

        // Variant #1
        /*
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!socket_bind($this->socket, '0.0.0.0', $port)) {
            echo "Unable to bind socket\n";
            exit(1);
        }
        $this->listener = new EventListener($this->base,
            array($this, "acceptConnCallback"), $this->base,
            EventListener::OPT_CLOSE_ON_FREE | EventListener::OPT_REUSEABLE,
            -1, $this->socket);
         */

        // Variant #2
         $this->listener = new EventListener($this->base,
             array($this, "acceptConnCallback"), $this->base,
             EventListener::OPT_CLOSE_ON_FREE | EventListener::OPT_REUSEABLE, -1,
             "0.0.0.0:$port");

        if (!$this->listener) {
            echo "Couldn't create listener";
            exit(1);
        }

        $this->listener->setErrorCallback(array($this, "accept_error_cb"));
    }

    public function dispatch() {
        $this->base->dispatch();
    }

    // This callback is invoked when there is data to read on $bev
    public function acceptConnCallback($listener, $fd, $address, $ctx) {
        // We got a new connection! Set up a bufferevent for it. */
        $base = $this->base;
        $this->conn[] = new MyListenerConnection($base, $fd);
    }

    public function accept_error_cb($listener, $ctx) {
        $base = $this->base;

        fprintf(STDERR, "Got an error %d (%s) on the listener. "
            ."Shutting down.\n",
            EventUtil::getLastSocketErrno(),
            EventUtil::getLastSocketError());

        $base->exit(NULL);
    }
}

$port = 9808;

if ($argc > 1) {
    $port = (int) $argv[1];
}
if ($port <= 0 || $port > 65535) {
    exit("Invalid port");
}

$l = new MyListener($port);
$l->dispatch();
?>