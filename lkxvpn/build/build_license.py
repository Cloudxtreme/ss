#!/bin/env python
import time
import zmq
import MySQLdb
import hashlib
import os
import sys
from M2Crypto import EVP, RSA, util


password = sys.argv[1]
outfile = sys.argv[2]
info = sys.argv[3]
sha1 = EVP.MessageDigest('sha1')
sha1.update(info)
dgst=sha1.final()

key_str = file("/opt/skynet/license/eflypro-pri.key","rb").read()
#priv = RSA.load_key('./license/eflypro-pri.key')
priv = RSA.load_key_string(key_str, lambda *args:password)
license = priv.sign(dgst, "sha1")
output = open(outfile, 'wb')
output.write(license)
output.close( )
sys.exit(1)
