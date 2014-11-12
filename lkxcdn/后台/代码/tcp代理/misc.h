#pragma once
#ifdef WIN32
#include <WinSock2.h>
#include <process.h>
#include <Windows.h>
#else
#include <unistd.h>
#include <string.h>
#include <stdlib.h>
#include <netdb.h>
#include <fcntl.h>
#include <netinet/in.h>
#include <unistd.h>
#include <arpa/inet.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <net/if.h>
#include <net/if_arp.h>
#include <sys/ioctl.h>
#include <sys/resource.h>
#include <time.h>
#include <signal.h>
#include <errno.h>
#include <sys/wait.h>
#include <sys/time.h>
#include <pthread.h>
#endif
#include "hash.h"
#include <stdio.h>

static int misc_os_base_init()
{
#ifdef WIN32
	WORD wVersionRequested;
	WSADATA wsaData;
	int err;
	wVersionRequested = MAKEWORD( 2, 2 );
	err = WSAStartup( wVersionRequested, &wsaData );
	if ( err != 0 ) {
		return 1;
	}
	if ( LOBYTE( wsaData.wVersion ) != 2 || HIBYTE( wsaData.wVersion ) != 2 ) 
	{
		WSACleanup( );
		return 1;
	}
#else
#endif
	return 0;
}

static int misc_set_fd_noblock(int fd)
{
#ifdef WIN32	
	unsigned long param = 1;
	if(SOCKET_ERROR==ioctlsocket(fd, FIONBIO, &param)) {
		return 1;
	}
	return 0;      
#else
	int flags = fcntl(fd, F_GETFL, 0);
	int ret = fcntl(fd, F_SETFL, flags|O_NONBLOCK);
	return ret;
#endif
}

static int misc_close_socket(int nHandle)
{
#ifdef WIN32
    return closesocket(nHandle);
#else
	return close(nHandle);
#endif
}

static int misc_get_error()
{
#ifdef WIN32
	return WSAGetLastError();
#else
	return errno;
#endif
}

static int misc_would_block()
{
#ifdef WIN32
	int ret = WSAGetLastError();
	return ret == WSAEWOULDBLOCK;
#else
	printf("misc_would_block %d \n", errno);
	return errno == EAGAIN || errno == EINTR || errno == EINPROGRESS;
#endif
}

static int my_strpos(const char *buff, int size, const char *match)
{
	int i = 0;
	size_t match_size = strlen(match);
	for( i = 0; i < size; i++ ) {
		if( buff[i] == match[0] && size - i >= match_size ) {
			if( ! memcmp(&buff[i], match, match_size) ) {
				return i;
			}
		}
	}
	return -1;
}

static int my_nocase_strpos(const char *buff, int size, const char *match)
{
	int i = 0, j = 0;
	size_t match_size = strlen(match);

	if( match_size > size ) { return -1; }

	for( i = 0; i <= size - match_size; i++ ) {
		for( j = 0; j < match_size; j++ ) {
			if( tolower(buff[i+j]) != tolower(match[j]) ) {
				break;
			}
		}
		if( j == match_size ) {
			return i;
		}
	}
	return -1;
}

static int get_line(const char *buff, int size, char *out_line, int out_size)
{
	int pos;
	pos = my_strpos(buff, size, HTTP_HEADER_LINE_END);
	if( pos < 0 ) { return pos; }
	if( pos > out_size ) { return -1; }
	memcpy(out_line, buff, pos);
	return pos;
}

static int get_http_header(const char *buff, int size, hash_table_t *hash_table)
{
	char key[MIN_LINE_LEN + 1] = {0};
	char value[MAX_LINE_LEN + 1] = {0};
	int buff_idx = 0;
	int pos, bpos, lpos;

	pos = my_strpos(buff + buff_idx, size - buff_idx, HTTP_HEADER_LINE_END);
	if( pos < 0 ) { return 0; }

	bpos = my_strpos(buff + buff_idx, pos, HTTP_HEADER_LINE_SPACE);
	if( bpos < 0 ) { return 0; }

	while( pos > 0 && bpos > 0 && pos < MAX_LINE_LEN ) {

		if( buff_idx == 0 ) {
			//first line
			lpos = my_strpos(buff + buff_idx + bpos + 1, pos - bpos - 1, HTTP_HEADER_LINE_SPACE);
			if( lpos <= 0 ) { return 0; }
			if( bpos >= MIN_LINE_LEN ) { return 0; }

			memcpy(key, buff + buff_idx, bpos);
			key[bpos] = '\0';

			if( lpos >= MAX_LINE_LEN ) { return 0; }
			memcpy(value, buff + buff_idx + bpos + 1, lpos);
			value[lpos] = '\0';

			hash_table_insert(hash_table, "method", (void*)strdup(key));
			hash_table_insert(hash_table, "url", (void*)strdup(value));

		} else {

			if( bpos >= MIN_LINE_LEN ) { return 0; }

			memcpy(key, buff + buff_idx, bpos);
			if( key[bpos - 1] != CHAR_COLON ) { return 0; }
			key[bpos - 1] = '\0';

			if( pos - bpos - 1 >= MAX_LINE_LEN ) { return 0; }
			memcpy(value, buff + buff_idx + bpos + 1, pos - bpos - 1);
			value[pos - bpos - 1] = '\0';
			
			if( ! strcmp(key, HTTP_HEADER_HOST) ) {
				//check for Host: www.test.com:1234
				lpos = my_strpos(value, pos - bpos, HTTP_HEADER_LINE_COLON);
				if( lpos > 0 ) {
					hash_table_insert(hash_table, HTTP_HEADER_HOST_PORT, (void*)strdup(&value[lpos+1]));
					value[lpos] = '\0';
				}
			}

			hash_table_insert(hash_table, key, (void*)strdup(value));
		}

		//printf("[%s] => [%s] \n", key, value);

		//next line
		buff_idx += (pos + 2);
		pos = my_strpos(buff + buff_idx, size - buff_idx, HTTP_HEADER_LINE_END);
		if( pos < 0 ) { break; }

		bpos = my_strpos(buff + buff_idx, pos, HTTP_HEADER_LINE_SPACE);
	}

	return 1;
/*
	char key[1024+1] = {0}, value[4*1024+1] = {0};
	int line_size = size, is_first_line = 1, i, idx = 0, pos = 0;
		
	pos = my_strpos(buff, size, HTTP_HEADER_LINE_END);
	while( pos > 0 ) {
		
		pos += idx;

		key[0] = value[0] = '\0';
		line_size = pos - idx;
				
		//get key
		for( i = 0; i < 1024 && idx < pos; i++, idx++ ) {
			key[i] = buff[idx];
			if( key[i] == ' ' ) {
				if( i > 1 && key[i - 1] == ':' ) { i--; }
				key[i] = '\0'; break; 
			}
		}
		idx++;
		//get value
		for( i = 0; i < 4*1024 && idx < pos; i++, idx++ ) {
			value[i] = buff[idx];
			if( is_first_line && value[i] == ' ' ) { value[i] = '\0'; break; }
		}
		value[i] = '\0';

		if( is_first_line ) {
			//printf("first line \n");
			is_first_line = 0;
			hash_table_insert(hash_table, "method", (void*)strdup(key));
			hash_table_insert(hash_table, "url", (void*)strdup(value));
		} else {
			if( ! strcmp(key, HTTP_HEADER_HOST) ) {
				//check for Host: www.test.com:1234
				int ii;
				for( ii = 0; ii < strlen(value); ii++ ) {
					if( value[ii] == ':' ) {
						//save port
						hash_table_insert(hash_table, HTTP_HEADER_HOST_PORT, (void*)strdup(&value[ii+1]));
						//truncate :1234
						value[ii] = '\0';
						break;
					}
				}
			}
			hash_table_insert(hash_table, key, (void*)strdup(value));
		}
		//printf("[%s] => [%s] \n", key, value);

		pos += 2;
		idx = pos;

		pos = my_strpos(buff + pos, size - pos, HTTP_HEADER_LINE_END);
	}

	return 1;
*/
}

static int get_full_http_head(const char *buff, int size)
{
	int i;
	for( i = 0; i < size; i++ ) {
		if( buff[i] == '\r' && buff[i+1] == '\n' &&
			buff[i+2] == '\r' && buff[i+3] == '\n' && i+3 <= size ) {
				return 1;
		}
	}
	return 0;
}

static int misc_atoi(const char *value)
{
	//ÔÝÊ±²»´íÉ¶ÅÐ¶Ï
	return atoi(value);
}

static void misc_msleep(int msec)
{
#ifdef WIN32
	Sleep(msec);
#else
	usleep(msec * 1000);
#endif
}


