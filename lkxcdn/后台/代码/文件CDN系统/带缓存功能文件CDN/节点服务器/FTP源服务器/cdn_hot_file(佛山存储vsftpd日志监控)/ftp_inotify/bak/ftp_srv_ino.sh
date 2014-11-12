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

MYSQL_CMD="${MYSQL_PATH}/bin/mysql -h${MYSQL_HOST} -u${MYSQL_USER} -p${MYSQL_PASS} ${MYSQL_DB}"

/usr/local/bin/inotifywait -mrq --timefmt '20%y-%m-%d %H:%M:%S' --format '%T %w%f %e' -e create,delete,close_write ${FTP_HOME_PATH} \
| while read date time file evt; do
	if [ "$evt" = "CREATE" ]; then
		#FILE_CREATE
		echo "insert into file_list(filename) values('$file');" \
		| ${MYSQL_CMD}
		
		/usr/bin/php /opt/cdn_hot_file/ftp_inotify/update_file_push.php $file
	
	else if [ "$evt" = "DELETE" ]; then
		#FILE_DELETE
		echo "delete from file_list where filename='$file';" \
		| ${MYSQL_CMD}

		echo "delete from file_push where filename='$file';" \
		| ${MYSQL_CMD}

	else
		#FILE_MODIFY
		filesize=`ls -l $file | awk '{print $5}'`
		owner=`echo ${file#*$FTP_PATH} | cut -d "/" -f 1`
		lastmodify=`stat $file | grep Modify | cut -d "." -f 1 | cut -d " " -f 2,3`

		echo "update file_list set filesize=$filesize,owner='$owner',lastmodify='$lastmodify',status='finish_check' where filename='$file' and lastmodify<'$lastmodify';" \
		| ${MYSQL_CMD}
		echo "update file_push set filesize=$filesize,owner='$owner',lastmodify='$lastmodify',status='ready' where filename='$file' and lastmodify<'$lastmodify';" \
		| ${MYSQL_CMD}
	
	fi
	fi
done
