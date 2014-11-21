
CFLAGS = -g -O2 -Wall -Wextra -I./ -I../inc -fstack-protector-all
LDFLAGS = -L../lib -L../efio -L../efnet -L../efext -L/usr/local/lib
LDFLAGS+= -lefext -lefio -lefnet -lpthread -lpfring -lpcap
#LDFLAGS+= ../lib/libpfring.a ../lib/libpcap.a

io_gen:
	gcc -o io_gen io_gen.c pkt_gen.c $(CFLAGS) $(LDFLAGS)
efvpnd:
	gcc -o efvpnd efvpnd.c conf.c $(CFLAGS) $(LDFLAGS)
efvpn:
	gcc -o efvpn efvpn.c $(CFLAGS) $(LDFLAGS)
server_conf:
	gcc -o server_conf server_conf.c conf.c $(CFLAGS) $(LDFLAGS)

efproxy:
	gcc -o efproxy efproxy.c conf.c $(CFLAGS) $(LDFLAGS)
eftest:
	gcc -o eftest eftest.c $(CFLAGS) $(LDFLAGS)

all:
	#gcc -o efvpnd efvpnd.c conf.c $(CFLAGS) $(LDFLAGS)
	#gcc -o efvpn efvpn.c $(CFLAGS) $(LDFLAGS)
	#gcc -o server_conf server_conf.c conf.c $(CFLAGS) $(LDFLAGS)
	#gcc -o eftrackd eftrackd.c conf.c $(CFLAGS) $(LDFLAGS)
	#gcc -o eftrack eftrack.c conf.c $(CFLAGS) $(LDFLAGS)
	gcc -o eftest eftest.c $(CFLAGS) $(LDFLAGS)
	#gcc -o efproxy efproxy.c conf.c $(CFLAGS) $(LDFLAGS)
	gcc -o efdetectd efdetector.c conf.c $(CFLAGS) $(LDFLAGS)
	gcc -o efdetect efdetect.c conf.c $(CFLAGS) $(LDFLAGS)

install:
	mkdir -p '$(DESTDIR)/etc/efvpn'
	mkdir -p '$(DESTDIR)/usr/sbin/'
	mkdir -p '$(DESTDIR)/usr/local/etc/'
	mkdir -p '$(DESTDIR)/etc/init.d/'
	cp ./efvpnd '$(DESTDIR)/usr/sbin/'
	cp ./efvpn '$(DESTDIR)/usr/sbin/'
	cp ./server_conf '$(DESTDIR)/usr/sbin/'
	cp ./efvpn.dev '$(DESTDIR)/etc/efvpn/dev'
	cp ./efvpn.run '$(DESTDIR)/etc/efvpn/run'
	cp ./eflypro-pub.key '$(DESTDIR)/etc/efvpn/key'
	cp ./dhcpd.conf '$(DESTDIR)/usr/local/etc/'
	cp ./service '$(DESTDIR)/etc/init.d/efvpnd'
	#/bin/sh ./efvpn.init

clean:
	rm -rf io_gen
	rm -rf efvpnd
	rm -rf efvpn
	rm -rf server_conf
	rm -rf *.o
	rm -rf eftrackd
	rm -rf eftrack
	rm -rf efproxy
	rm -rf efdetectd
	rm -rf efdetect
