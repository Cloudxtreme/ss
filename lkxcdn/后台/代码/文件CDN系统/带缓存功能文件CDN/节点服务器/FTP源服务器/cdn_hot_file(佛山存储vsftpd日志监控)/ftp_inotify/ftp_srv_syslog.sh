#!/bin/sh

FTP_HOME_PATH="/var/ftp/pub/"
FTP_LOG_FILE="/var/log/xferlog"
SYS_LOG_FILE="/var/log/vsftpd.log"
MYSQL_CMD="/usr/bin/mysql -h cdnmgr.efly.cc -uroot -prjkj@rjkj --default-character-set=utf8 cdn_file"


tailf $SYS_LOG_FILE | awk '{if($7=="OK" && $5~"vsftpd" && ($8=="UPLOAD:" || $8=="DELETE:" || $8=="RENAME:")){print $6,$8,$11,$12;fflush()}}' \
| while read owner optype key val; do

	owner=${owner#[}
	owner=${owner%]}

	if [ "$optype" == "DELETE:" ]; then
		file=$key
		len=${#file}
		filesize=$val
		file=${file:1:len-2}
		file="$FTP_HOME_PATH$owner$file"

		#echo "DELETE -- $file"

		echo "update file_list set percent=0,type='delete',status='ready' where filename='$file';" \
		| ${MYSQL_CMD}

		#echo "update file_push set status='delete' where filename='$file';" \
		#| ${MYSQL_CMD}

	else if [ "$optype" == "UPLOAD:" ]; then
		file=$key
		len=${#file}
		#filesize=$val
		file=${file:1:len-3}
		file="$FTP_HOME_PATH$owner$file"
		filesize=`ls -l $file | awk '{print $5}'`
		filemd5=`md5sum $file | awk '{print $1}'`
		if [ -f $file ]; then

			lastmodify=`stat $file | grep Modify | cut -d "." -f 1 | cut -d " " -f 2,3`
		
			#echo "UPLOAD -- $file $filesize $owner $lastmodify"

			echo "insert into file_list(filename,filesize,md5,md5_source,owner,lastmodify,type,percent,status) values \
			('$file',$filesize,'$filemd5','$filemd5','$owner','$lastmodify','push',0,'ready') \
			on duplicate key update \
			filename='$file',filesize=$filesize,md5='$filemd5',md5_source='$filemd5',lastmodify='$lastmodify',opera_time=opera_time+1,type='push',percent=0,status='ready',extract='';" \
			| ${MYSQL_CMD}
	
			#/usr/bin/php /opt/cdn_hot_file/ftp_inotify/update2_file_push.php $file $filesize $owner $lastmodify

			declare -l filetmp=$file
			if [[ "$filetmp" =~ ".zip.unzip" ]]; then
				echo "extract file : $file"
				/opt/cdn_hot_file/ftp_inotify/ftp_zip_handle.sh $owner $file &
			fi
	
		fi
	else
		namebe=$key
		nameaf=$val
		
		len=${#namebe}
		namebe=${namebe:1:len-1}
                namebe="$FTP_HOME_PATH$owner$namebe"

		len=${#nameaf}
		nameaf=${nameaf:0:len-1}
                nameaf="$FTP_HOME_PATH$owner$nameaf"

                if [ -f $nameaf ]; then

                        lastmodify=`stat $nameaf | grep Modify | cut -d "." -f 1 | cut -d " " -f 2,3`

                        #echo "RENAME -- $namebe $nameaf $owner $lastmodify"

			echo "update file_list set filename='$nameaf',lastmodify='$lastmodify',percent=0,status='ready' where filename='$namebe';" \
                	| ${MYSQL_CMD}
		fi


	fi
	fi
done
