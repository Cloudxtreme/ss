#!/bin/sh

demon=`date -d -3month +%Y-%m`
detab="${demon}_gen"

MYSQL_CMD_WEB="/usr/bin/mysql -uroot -prjkj@rjkj --default-character-set=utf8 cdn_web_log_general"
sql="drop table \`${detab}\`;"

echo $sql | ${MYSQL_CMD_WEB}
