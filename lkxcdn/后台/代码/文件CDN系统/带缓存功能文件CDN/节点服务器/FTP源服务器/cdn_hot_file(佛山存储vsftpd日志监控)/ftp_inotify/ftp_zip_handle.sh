#!/bin/sh

MYSQL_CMD="/usr/bin/mysql -h cdnmgr.efly.cc -uroot -prjkj@rjkj --default-character-set=utf8 cdn_file"

owner=$1
zfile=$2
zfdir=${zfile%/*}

unzip -ou -d $zfdir $zfile | awk '{print $2;fflush()}' \
| while read file; do

	if [ -d $file ]; then
		`/bin/chown -R $owner:$owner $file`

	else if [ "$file" != "$zfile" -a -f $file ]; then

		`/bin/chown $owner:$owner $file`
		filesize=`ls -l $file | awk '{print $5}'`
		lastmodify=`stat $file | grep Modify | cut -d "." -f 1 | cut -d " " -f 2,3`

		echo "insert into file_list(filename,filesize,owner,lastmodify,extract,type,status) values \
		('$file',$filesize,'$owner','$lastmodify','$zfile','push','ready') \
		on duplicate key update \
		filename='$file',filesize=$filesize,lastmodify='$lastmodify',opera_time=opera_time+1,type='push',percent=0,status='ready';" \
		| ${MYSQL_CMD}
	
		#/usr/bin/php /opt/cdn_hot_file/ftp_inotify/update2_file_push.php $file $filesize $owner $lastmodify
	fi
	fi

done
