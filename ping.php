<?php

#ejemplos de socket option :
#http://php.net/manual/es/function.socket-set-option.php
#http://php.net/manual/es/function.socket-get-option.php

#Informacion de los socket:http://php.net/manual/es/function.socket-create.php

//display_errors ('On'); ini_set ('error_reporting', 'E_ALL');

function ping($host) 
{
    $package = "\x08\x00\x19\x2f\x00\x00\x00\x00\x70\x69\x6e\x67";

    /* create the socket, the last '1' denotes ICMP */   
    $socket = socket_create(AF_INET, SOCK_RAW, 1);
   
    /* set socket receive timeout to 1 second */
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 0));
   
    /* connect to socket */
    socket_connect($socket, $host, null);
   
   


    /* record start time */
    list($start_usec, $start_sec) = explode(" ", microtime());
    $start_time = ((float) $start_usec + (float) $start_sec);
   
    socket_send($socket, $package, strlen($package), 0);
   



    if(($re=socket_read($socket, 2048, PHP_BINARY_READ))==true) {
        list($end_usec, $end_sec) = explode(" ", microtime());
        $end_time = ((float) $end_usec + (float) $end_sec);
   
        $total_time = $end_time - $start_time;
        $total_time=$total_time;
       print_r(utf8_decode($re))."----------------";
        return $total_time;
    } else {
        return false;
    }
   
    socket_close($socket);
}

ping("8.8.8.8");

function ping2($host, $timeout = 1) {
                /* ICMP ping packet with a pre-calculated checksum */
                $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
                $socket  = socket_create(AF_INET, SOCK_RAW, 1);
                socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
                socket_connect($socket, $host, null);

                $ts = microtime(true);
                socket_send($socket, $package, strLen($package), 0);
                if(@socket_read($socket, 255))
                        $result = microtime(true) - $ts;
                else    $result = false;
                socket_close($socket);

                return $result;
        }





/*
$num=ping3("192.168.0.233",1);

$num=$num+0.05;
echo number_format($num,3);´*/









/*

/// start ping.inc.php ///

$g_icmp_error = "No Error";

// timeout in ms
function ping($host, $timeout)
{
        $port = 0;
        $datasize = 64;
        global $g_icmp_error;
        $g_icmp_error = "No Error";
        $ident = array(ord('J'), ord('C'));
        $seq   = array(rand(0, 255), rand(0, 255));

     $packet = '';
     $packet .= chr(8); // type = 8 : request
     $packet .= chr(0); // code = 0

     $packet .= chr(0); // checksum init
     $packet .= chr(0); // checksum init

        $packet .= chr($ident[0]); // identifier
        $packet .= chr($ident[1]); // identifier

        $packet .= chr($seq[0]); // seq
        $packet .= chr($seq[1]); // seq

        for ($i = 0; $i < $datasize; $i++)
                $packet .= chr(0);

        $chk = icmpChecksum($packet);

        $packet[2] = $chk[0]; // checksum init
        $packet[3] = $chk[1]; // checksum init

        $sock = socket_create(AF_INET, SOCK_RAW,  getprotobyname('icmp'));
        $time_start = microtime();
    socket_sendto($sock, $packet, strlen($packet), 0, $host, $port);
   

    $read   = array($sock);
        $write  = NULL;
        $except = NULL;

        $select = socket_select($read, $write, $except, 0, $timeout * 1000);
        if ($select === NULL)
        {
                $g_icmp_error = "Select Error";
                socket_close($sock);
                return -1;
        }
        elseif ($select === 0)
        {
                $g_icmp_error = "Timeout";
                socket_close($sock);
                return -1;
        }

    $recv = '';
    $time_stop = microtime();
    socket_recvfrom($sock, $recv, 65535, 0, $host, $port);
        $recv = unpack('C*', $recv);
       
        if ($recv[10] !== 1) // ICMP proto = 1
        {
                $g_icmp_error = "Not ICMP packet";
                socket_close($sock);
                return -1;
        }

        if ($recv[21] !== 0) // ICMP response = 0
        {
                $g_icmp_error = "Not ICMP response";
                socket_close($sock);
                return -1;
        }

        if ($ident[0] !== $recv[25] || $ident[1] !== $recv[26])
        {
                $g_icmp_error = "Bad identification number";
                socket_close($sock);
                return -1;
        }
       
        if ($seq[0] !== $recv[27] || $seq[1] !== $recv[28])
        {
                $g_icmp_error = "Bad sequence number";
                socket_close($sock);
                return -1;
        }

        $ms = ($time_stop - $time_start) * 1000;
       
        if ($ms < 0)
        {
                $g_icmp_error = "Response too long";
                $ms = -1;
        }

        socket_close($sock);

        return $ms;
}

function icmpChecksum($data)
{
        $bit = unpack('n*', $data);
        $sum = array_sum($bit);

        if (strlen($data) % 2) {
                $temp = unpack('C*', $data[strlen($data) - 1]);
                $sum += $temp[1];
        }

        $sum = ($sum >> 16) + ($sum & 0xffff);
        $sum += ($sum >> 16);

        return pack('n*', ~$sum);
}

function getLastIcmpError()
{
        global $g_icmp_error;
        return $g_icmp_error;
}
/// end ping.inc.php ///



*/


?>