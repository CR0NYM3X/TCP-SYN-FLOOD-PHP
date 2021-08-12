s=0
msg="holas"
for i in range(0, len(msg), 2):
        w = (ord(msg[i]) << 8) + (ord(msg[i+1]) )
        #print(msg[i+1])
        print(msg[i])
        s = s + w

