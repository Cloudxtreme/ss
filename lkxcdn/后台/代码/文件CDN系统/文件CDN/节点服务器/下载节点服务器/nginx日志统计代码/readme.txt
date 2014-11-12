0 02 * * * /opt/nginx_tools/nginx_day_log_cron.sh 183.60.46.168 > /dev/null 2>&1
0 03 * * * /opt/nginx_tools/nginx_log_update.sh /opt/nginx_tools/config filestats.cdn.efly.cc > /dev/null 2>&1