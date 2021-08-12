<?php

  $port = 9050;
   
    // create a streaming socket, of type TCP/IP
    $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
   
    // set the option to reuse the port
    socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
   
    // "bind" the socket to the address to "localhost", on port $port
    // so this means that all connections on this port are now our resposibility to send/recv data, disconnect, etc..
    socket_bind($sock, 0, $port);
   
    // start listen for connections
    socket_listen($sock);

    // create a list of all the clients that will be connected to us..
    // add the listening socket to this list
    $clients = array($sock);
   
    while (true) {
        // create a copy, so $clients doesn't get modified by socket_select()
        $read = $clients;
       
        // get a list of all the clients that have data to be read from
        // if there are no clients with data, go to next iteration
        if (socket_select($read, $write = NULL, $except = NULL, 0) < 1)
            continue;
       
        // check if there is a client trying to connect
        if (in_array($sock, $read)) {
            // accept the client, and add him to the $clients array
            $clients[] = $newsock = socket_accept($sock);
           
            // send the client a welcome message
            socket_write($newsock, "no noobs, but ill make an exception :)\n".
            "There are ".(count($clients) - 1)." client(s) connected to the server\n");
           
            socket_getpeername($newsock, $ip);
            echo "New client connected: {$ip}\n";
           
            // remove the listening socket from the clients-with-data array
            $key = array_search($sock, $read);
            unset($read[$key]);
        }
       
        // loop through all the clients that have data to read from
        foreach ($read as $read_sock) {
            // read until newline or 1024 bytes
            // socket_read while show errors when the client is disconnected, so silence the error messages
            $data = @socket_read($read_sock, 1024, PHP_NORMAL_READ);
           
            // check if the client is disconnected
            if ($data === false) {
                // remove client for $clients array
                $key = array_search($read_sock, $clients);
                unset($clients[$key]);
                echo "client disconnected.\n";
                // continue to the next client to read from, if any
                continue;
            }
           
            // trim off the trailing/beginning white spaces
            $data = trim($data);
           
            // check if there is any data after trimming off the spaces
            if (!empty($data)) {
           
                // send this to all the clients in the $clients array (except the first one, which is a listening socket)
                foreach ($clients as $send_sock) {
               
                    // if its the listening sock or the client that we got the message from, go to the next one in the list
                    if ($send_sock == $sock || $send_sock == $read_sock)
                        continue;
                   
                    // write the message to the client -- add a newline character to the end of the message
                    socket_write($send_sock, $data."\n");
                   
                } // end of broadcast foreach
               
            }
           
        } // end of reading foreach
    }

    // close the listening socket
    socket_close($sock);



//-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    /**
  * Listens for requests and forks on each connection
  */

$__server_listening = true;

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();
declare(ticks = 1);

become_daemon();

/* nobody/nogroup, change to your host's uid/gid of the non-priv user */
change_identity(65534, 65534);

/* handle signals */
pcntl_signal(SIGTERM, 'sig_handler');
pcntl_signal(SIGINT, 'sig_handler');
pcntl_signal(SIGCHLD, 'sig_handler');

/* change this to your own host / port */
server_loop("127.0.0.1", 1234);

/**
  * Change the identity to a non-priv user
  */
function change_identity( $uid, $gid )
{
    if( !posix_setgid( $gid ) )
    {
        print "Unable to setgid to " . $gid . "!\n";
        exit;
    }

    if( !posix_setuid( $uid ) )
    {
        print "Unable to setuid to " . $uid . "!\n";
        exit;
    }
}

/**
  * Creates a server socket and listens for incoming client connections
  * @param string $address The address to listen on
  * @param int $port The port to listen on
  */
function server_loop($address, $port)
{
    GLOBAL $__server_listening;

    if(($sock = socket_create(AF_INET, SOCK_STREAM, 0)) < 0)
    {
        echo "failed to create socket: ".socket_strerror($sock)."\n";
        exit();
    }

    if(($ret = socket_bind($sock, $address, $port)) < 0)
    {
        echo "failed to bind socket: ".socket_strerror($ret)."\n";
        exit();
    }

    if( ( $ret = socket_listen( $sock, 0 ) ) < 0 )
    {
        echo "failed to listen to socket: ".socket_strerror($ret)."\n";
        exit();
    }

    socket_set_nonblock($sock);
   
    echo "waiting for clients to connect\n";

    while ($__server_listening)
    {
        $connection = @socket_accept($sock);
        if ($connection === false)
        {
            usleep(100);
        }elseif ($connection > 0)
        {
            handle_client($sock, $connection);
        }else
        {
            echo "error: ".socket_strerror($connection);
            die;
        }
    }
}

/**
  * Signal handler
  */
function sig_handler($sig)
{
    switch($sig)
    {
        case SIGTERM:
        case SIGINT:
            exit();
        break;

        case SIGCHLD:
            pcntl_waitpid(-1, $status);
        break;
    }
}

/**
  * Handle a new client connection
  */
function handle_client($ssock, $csock)
{
    GLOBAL $__server_listening;

    $pid = pcntl_fork();

    if ($pid == -1)
    {
        /* fork failed */
        echo "fork failure!\n";
        die;
    }elseif ($pid == 0)
    {
        /* child process */
        $__server_listening = false;
        socket_close($ssock);
        interact($csock);
        socket_close($csock);
    }else
    {
        socket_close($csock);
    }
}

function interact($socket)
{
    /* TALK TO YOUR CLIENT */
}

/**
  * Become a daemon by forking and closing the parent
  */
function become_daemon()
{
    $pid = pcntl_fork();
   
    if ($pid == -1)
    {
        /* fork failed */
        echo "fork failure!\n";
        exit();
    }elseif ($pid)
    {
        /* close the parent */
        exit();
    }else
    {
        /* child becomes our daemon */
        posix_setsid();
        chdir('/');
        umask(0);
        return posix_getpid();

    }
} 






?>