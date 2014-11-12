#!/bin/sh

CURPATH=`pwd`
FTP_PATH="/var/ftp/pub/"
FTP_HOME_PATH="/var/ftp/pub"
FTP_LOG_FILE="/var/log/xferlog"

MYSQL_PATH="/usr"
MYSQL_HOST="localhost"
MYSQL_USER="root"
MYSQL_PASS="rjkj@rjkj"
MYSQL_DB="cdn_hot_file"

MYSQL_CMD="${MYSQL_PATH}/bin/mysql -h${MYSQL_HOST} -u${MYSQL_USER} -p${MYSQL_PASS} --default-character-set=utf8 ${MYSQL_DB}"

/usr/local/bin/inotifywait -mrq --timefmt '20%y-%m-%d %H:%M:%S' --format '%T %w%f %e' -e delete ${FTP_HOME_PATH} \
| while read date time file evt; do

		#FILE_DELETE
		#echo "delete from file_list where filename='$file';" \
		echo "update file_list set status='delete' where filename='$file';" \
		| ${MYSQL_CMD}

		#echo "delete from file_push where filename='$file';" \
		echo "update file_push set status='delete' where filename='$file';" \
		| ${MYSQL_CMD}

done
