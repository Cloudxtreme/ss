#!/bin/sh

deday=`date -d -61day +%Y-%m-%d`
detab="${deday}_gen"

MYSQL_CMD_WEB="/usr/bin/mysql -uroot -prjkj@rjkj --default-character-set=utf8 cdn_web_log_general"
sql="drop table \`${detab}\`;"

echo $sql | ${MYSQL_CMD_WEB}


deday=`date -d -1day +%Y-%m-%d`
detab="${deday}_ip"
sql="drop table \`${detab}\`;"
echo $sql | ${MYSQL_CMD_WEB}

detab="${deday}_ref"
sql="drop table \`${detab}\`;"
echo $sql | ${MYSQL_CMD_WEB}

detab="${deday}_ref_target"
sql="drop table \`${detab}\`;"
echo $sql | ${MYSQL_CMD_WEB}
