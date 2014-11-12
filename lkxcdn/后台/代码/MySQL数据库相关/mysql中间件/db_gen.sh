#!/bin/sh

mysql_cmd="/usr/bin/mysql -uroot -prjkj@2009#8 proxy"
proxy_log_path=/opt/db_mgr/log/proxy

yesday=`date -d yesterday +%Y-%m-%d`
today=`date +%Y-%m-%d`


/bin/sh /opt/db_mgr/db_proxy.sh


ls ${proxy_log_path} \
| while read proxy_port; do

	db_proxy_log_path="${proxy_log_path}/${proxy_port}"

	yesday_proxy_log_file="${db_proxy_log_path}/${yesday}.log"
	today_proxy_log_file="${db_proxy_log_path}/${today}.log"

	if [ -f ${yesday_proxy_log_file} ]; then
		yesday_last_log_id=`tail -n 1 ${yesday_proxy_log_file} | awk '{print $2}'`
		echo "insert into gen_log(gen_proxy_port, gen_date, proxy_log_file, last_proxy_log_id) \
			values('${proxy_port}', '${yesday}', '${yesday_proxy_log_file}', ${yesday_last_log_id}) \
			on duplicate key update last_proxy_log_id=${yesday_last_log_id};"\
		| ${mysql_cmd}
	fi
	if [ -f ${today_proxy_log_file} ]; then
		today_last_log_id=`tail -n 1 ${today_proxy_log_file} | awk '{print $2}'`
		echo "insert into gen_log(gen_proxy_port, gen_date, proxy_log_file, last_proxy_log_id) \
			values('${proxy_port}', '${today}', '${today_proxy_log_file}', ${today_last_log_id}) \
			on duplicate key update last_proxy_log_id=${today_last_log_id}"\
		| ${mysql_cmd}
	fi

done

/usr/bin/php /opt/db_mgr/db_gen.php
