#include "http_proxy.h"
#include "hash.h"
#include "misc.h"

int main(int argc, char **argv)
{
	misc_os_base_init();

	g_http_proxy = create_http_proxy();

	http_proxy_init(g_http_proxy);

	http_proxy_start(g_http_proxy);

	getchar();

	http_proxy_stop(g_http_proxy);

	return 0;
}

