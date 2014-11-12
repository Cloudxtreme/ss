dnscap : main.o
	g++ -g -o dnscap main.o -lpcap

dnscap.o : main.cpp
	gcc -g -c main.cpp

clean:
	rm -rf *.o dnscap

