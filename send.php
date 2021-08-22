<?php
# TCP SYN FLOOR PHP


// Convierte a 16bit el numero que ingreses siempre y cuando se a menor de 16bits, que sea menor que 32767 decimal si se ingresa un numero que sea de 16 bits se invertira el procesos y se convertira el numero a 8 bits
function htons($num)
{
    $bin=decbin($num);
  //  if (strlen($bin)>=16)
    //    return $num;

    $restante= 16- strlen($bin);
    $cerosFaltantes='';

    for ($i=1; $i <= $restante ; $i++) { 
        $cerosFaltantes.='0';
    }

    $bin = $cerosFaltantes.$bin;
    return bindec(substr($bin, 8).substr($bin, 0,8));

}


function checksum($msg)
{
	$s= 0;
	
	   # loop taking 2 characters at a time
	for ($i=0; $i < strlen($msg); $i+=2) { 
		//echo $msg[$i]."\n";
		$w = (ord($msg[$i]) <<  8 ) + (ord($msg[$i+1]) );
		$s = $s + $w;
	}

	$s = ($s>>16) + ($s & 0xffff); // (Hexadecimal)0xffff = (Decimal)65535 = 16bits( 11111111 11111111 )
	$s = ~$s & 0xffff;   // aplica operacion not y and

	return $s;
}






# Crear un socket raw

$socket = socket_create(AF_INET, SOCK_RAW, getprotobyname("tcp"));
socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 0));


#decirle al kernel que no ponga encabezados, ya que lo estamos proporcionando
// s.setsockopt(socket.IPPROTO_IP, socket.IP_HDRINCL, 1)


# now start constructing the packet
$packet = '';
$source_ip = '192.168.1.94';
$dest_ip = "8.8.8.8";//gethostbyname("www.google.com");



# ip header fields https://www.educba.com/ipv4-header-format/
$ihl = 5;  //0x5 Internet Header Length. The Internet header length is 20 bytes. 
$version = 4;  //0x4 (4= ipv4) (6=ipv6)
$tos = 0;   /// 0x0 Type of Service: 8 bits 0000(icmp)  proporcionar funciones relacionadas con la calidad del servicio
$tot_len = 20 + 20;   #0x28 Total Length - tamaño de todo el datagrama minimo 20 bytes y, como máximo, puede ser de 65 535 bytes.
#self.flags = 0x0
$id = 54321;  #Id of this packet dentificar los fragmentos de un datagrama IP de forma única
$frag_off = 0;  //0x0 especificar el desplazamiento de un fragmento con respecto al inicio del datagrama
$ttl = 255; // Time to live : son los saltos: los saltos son cada ves que pasa por un router si pasa por 3 router son 3 saltos

$protocol = getprotobyname("tcp"); //  0x6 (1= icmt) (6=TCP) (17=UDP)
$check = 10;  #0x0 python seems to correctly fill the checksum-- se utiliza para comprobar si hay errores en el encabezado.
$saddr = inet_pton($source_ip);  #Spoof the source ip address if you want to inet_pton
$daddr = inet_pton($dest_ip);  //  ip2long : esta funcion convierte la ipv4 en un numero de 16bits
$ihl_version = ($version << 4) + $ihl;  // esta operacion = 69 
// self.f_fo = (self.flags << 13) + self.fragment_offset


# the ! in the pack format string means network order
$ip_header= pack("CCnnnCCnA*A*", $ihl_version, $tos, $tot_len, $id, $frag_off, $ttl, $protocol, $check, $saddr, $daddr); // 0b8ebfc08ae58a9a3fae3dc671312e82


# tcp header fields
$source = 1234;   # source port
$dest = 80;   # destination port
$seq = 0;
$ack_seq = 0;
$doff = 5 ;   # 0x5 4 bit field, size of tcp header, 5 * 4 = 20 bytes
#tcp flags
$fin = 0;
$syn = 1;
$rst = 0;
$psh = 0;
$ack = 0;
$urg = 0; 
$window =   htons(5840);   //socket.htons (5840) ; Esta funcion Converte un valor a 16 bits, (5840)=[ 10110 11010000 ] se cuenta de derecha a izquierda 8 bits el restante que es 10110 se se agregan 0 al lado izquierdo hasta completar 8 bits y queda asi: 00010110 ahora estos 8 bits se mueven a la derecha y quedaria asi [11010000 00010110] y si lo convertimos adecimal el resultado es este:  53270
$check = 0; // 0x0
$urg_ptr = 0; // 0x0
 
$offset_res = ($doff << 4) + 0;
$tcp_flags = $fin + ($syn << 1) + ($rst << 2) + ($psh <<3) + ($ack << 4) + ($urg << 5);


# the ! in the pack format string means network order
$tcp_header = pack("nnNNCCnnn" , $source, $dest, $seq, $ack_seq, $offset_res, $tcp_flags,  $window, $check, $urg_ptr); // 3033b35378af352978f3b64f023ed971 


# pseudo header fields
$source_address =inet_pton( $source_ip );
$dest_address =inet_pton($dest_ip);
$placeholder = 0;
$protocol = getprotobyname("tcp");
$tcp_length = strlen($tcp_header);
 

$psh = pack("A*A*CCn" , $source_address , $dest_address , $placeholder , $protocol , $tcp_length); //#94db381c95dab8a5f9ada3c0920971b6 - 4s4sBBH
$psh = $psh . $tcp_header; // 2d9597b9b53c8d08e2da9efaee81664c
$tcp_checksum = checksum($psh); 
 

# make the tcp header again and fill the correct checksum
$tcp_header = pack('nnNNCCnnn' , $source, $dest, $seq, $ack_seq, $offset_res, $tcp_flags,  $window, $tcp_checksum , $urg_ptr); // 79bad02a3f320a6e1cf6302f73ff3734
 
# final full packet - syn packets dont have any data
$packet = $ip_header . $tcp_header; // e166adbb79a5e8bd5701d508fe912bf3
 

#pon esto en un bucle si quieres inundar el objetivo
/* connect to socket */
socket_sendto($socket, $packet, strlen($packet), 0, $dest_ip, 0); // el cerro es el flag el que dice que no necesita fragmentar y el ultimo 0 es el puerto  especificado no tiene ningún efecto

//socket_connect($socket, $dest_ip, null);
//socket_send($socket , $packet, 40 , 0  );


socket_close($socket);






/*


Link donde pase el codigo python a php de la creacion de un  tcp syn:
https://gist.github.com/fffaraz/57144833c6ef8bd9d453

#instalacion WSL2: https://terminaldelinux.com/terminal/wsl/instalacion-configuracion-wsl/#convertir-distro-wsl1-a-wsl2

#https://inc0x0.com/tcp-ip-packets-introduction/tcp-ip-packets-4-creating-a-syn-port-scanner/

//http://www.tecnodelinglesalcastellano.com/2011/07/encapsulado-y-formato-de-datagramas-ip.html


ejemplos:
https://www.redeszone.net/tutoriales/seguridad/hping3-manipular-paquetes-tcp-ip-ataques/
https://www.um.es/docencia/barzana/DIVULGACION/INFORMATICA/Introduccion_a_TCPIP.pdf



creacion de paquete
https://inc0x0.com/tcp-ip-packets-introduction/tcp-ip-packets-4-creating-a-syn-port-scanner/

sniffer python
https://www.uv.mx/personal/angelperez/files/2018/10/sniffers_texto.pdf

// Codigo Asccii https://elcodigoascii.com.ar/
//Las computadoras únicamente entienden números. El código ASCII es un método de traducción de letras y símbolos a números como ‘a=97’ o‘/=47’ .2​ 





****** OPERADORES BITS ************
https://www.php.net/manual/es/language.operators.bitwise.php
Funcion 	Descripcion
ord()		Convierte Caracter a Ascci
<< 			Realiza un desplazamiento a la izquierda bit a bit. Desplaza los bits del operando de la izquierda a la izquierda tantos bits como especifique el operador de la derecha
2 << 3  convierte el 2 en binario = 10 y agrega 3 "0" a la derecha resultado = 10 000 | 2 << 3 = 16 (Binario: 00000010 << 00000011 = 00001000)
>>			(bin){011} 110 11 = (dec)123 >> 3 [ Se mueven los primeros 3 bit a la derecha, remplazando 110 por los primeros 3 bits y asi sucesivamente ] 0000 1111
&(and)			Realiza bit a bit la operación AND en los operandos 2 & 3 = 2 (Binario: 10 & 11 = 10)
~(not)			Realiza bit a bit la operación NOT bit a bit. Invierte cada bit en el operando Eje: (dec)~3 = [ (bin)0011 + (bit)1 ] = -4   | practicamente solo le suma 1 bit


15 << 2 = 15 * (2 * 2) = 60
15 << 3 = 15 * (2 * 2 * 2) = 120
15 << 5 = 15 * (2 * 2 * 2 * 2 * 2) = 480
Para 15 >> 2 = (15/2) / 2 = 7/2 = 3 (use valores mínimos si el resultado está en decimales). Del mismo modo 35 >> 3 = (((35/2) / 2) / 2 = (17/2) / 2 = 8/2 = 4.

IPv4 Header Format Component
Version.  4bits (0100) B
Internet Header Length. 4bits B
Type of Service.	8bits B
Explicit Congestion Notification.  ECN 
Total Length.     16 bits H
Identification.	  16 bits H
Flags.	3 bits  B
Fragment Offset  13 bits H
Time to live. 8bits B
Protocol. 8bits B
A checksum of header. 16bits H
Source Address. 32bits I
Destination Address. 32bits
Options.  
data

Banderas: la bandera en un encabezado IPv4 es un campo de tres bits que se usa para controlar e identificar fragmentos. Las siguientes pueden ser sus posibles configuraciones:
Bit 0: está reservado y debe ponerse a cero
Bit 1: DF o no fragmentar
Bit 2: MF o más fragmentos.



char # (8)bits 
El tipo entero char ocupa en la memoria 1 byte (8 bits) y permite representar en el sistema numérico binario 2^8 valores = 256. El tipo char puede contener los valores positivos, igual que negativos. El rango de valores es de -128 a 127.

uchar #	
El tipo entero uchar también ocupa en la memoria 1 byte, igual que el tipo char, pero a diferencia de él, uchar está destinado únicamente para los valores positivos. El valor mínimo es igual a cero, el valor máximo es igual a 255. La primera letra u del nombre uchar es la abreviatura de la palabra unsigned (sin signo).

short #
El tipo entero short tiene el tamaño de 2 bytes (16 bits), permite representar la multitud de valores igual a 2 elevado a 16: 2^16 = 65 536. Puesto que el tipo short es con signos y contiene los valores tanto positivos, como negativos, el rango de valores se oscila entre -32 768 y 32 767.

ushort #
El tipo ushort es el tipo short sin signos, también tiene el tamaño de 2 bytes. El valor mínimo es igual a cero, el valor máximo es igual a 65 535.

int #
El tipo entero int tiene el tamaño de 4 bytes (32 bits). El valor mínimo es de —2 147 483 648, el valor máximo es de 2 147 483 647.

uint #
El tipo entero sin signos uint ocupa en la memoria 4 bytes y permite representar los valores de números enteros de 0 a 4 294 967 295.

long #
El tipo entero long tiene el tamaño de 8 bytes (64 bits). El valor mínimo es de —9 223 372 036 854 775 808, el valor máximo es de 9 223 372 036 854 775 807.

ulong #
El tipo entero ulong también ocupa 8 bytes y permite almacenar valores de 0 a 18 446 744 073 709 551 615.





 TCP hay 6 flags   SYN, ACK, RST, PSH, URG y FIN.

Protocol Version (four bits): The first four bits. This represents the current IP protocol.
• Header Length (four bits): The length of the IP header is represented in 32-bit words. Since
this field is four bits, the maximum header length allowed is 60 bytes. Usually the value is 5,
which means five 32-bit words: 5 * 4 = 20 bytes.

• Type of Service (eight bits): The first three bits are precedence bits, the next four bits represent
the type of service, and the last bit is left unused.

• Total Length (16 bits): This represents the total IP datagram length in bytes. This a 16-bit field.
The maximum size of the IP datagram is 65,535 bytes.

• Flags (three bits): The second bit represents the Don't Fragment bit. When this bit is set, the
IP datagram is never fragmented. The third bit represents the More Fragment bit. If this bit is
set, then it represents a fragmented IP datagram that has more fragments after it.

• Time To Live (eight bits): This value represents the number of hops that the IP datagram will
go through before being discarded.

• Protocol (eight bits): This represents the transport layer protocol that handed over data to the IP
layer.

• Header Checksum (16 bits): This field helps to check the integrity of an IP datagram.
• Source and destination IP (32 bits each): These fields store the source and destination address,
respectively






*/




 













?>