#!/bin/sh

FTP_HOME_PATH="/var/ftp/pub/"
FTP_LOG_FILE="/var/log/xferlog"
MYSQL_CMD="/usr/bin/mysql -uroot -prjkj@rjkj --default-character-set=utf8 cdn_hot_file"


tailf $FTP_LOG_FILE | awk '{if($12=="i"){print $9,$8,$14;fflush()}}' \
| while read file filesize owner; do

	file="$FTP_HOME_PATH$owner$file"
	if [ -f $file ]; then
		lastmodify=`stat $file | grep Modify | cut -d "." -f 1 | cut -d " " -f 2,3`
	
		#echo $file $filesize $owner $lastmodify

		echo "insert into file_list(filename,filesize,owner,lastmodify,status) values \
		('$file',$filesize,'$owner','$lastmodify','finish_check') \
		on duplicate key update \
		filesize=$filesize,lastmodify='$lastmodify';" \
		| ${MYSQL_CMD}
	
		/usr/bin/php /opt/cdn_hot_file/ftp_inotify/update2_file_push.php $file $filesize $owner $lastmodify

		declare -l filetmp=$file
		if [[ "$filetmp" =~ ".zip.unzip" ]]; then
			echo "extract file : $file"
			/opt/cdn_hot_file/ftp_inotify/ftp_zip_handle.sh $owner $file &
		fi
	
	fi
done
