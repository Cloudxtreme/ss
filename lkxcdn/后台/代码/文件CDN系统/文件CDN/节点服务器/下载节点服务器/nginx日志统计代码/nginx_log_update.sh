#!/bin/sh
# $1 config file
# $2 remote db ip

date=`date -d '-1 day' +%Y-%m-%d`
deldate=`date -d '-3 day' +%Y-%m-%d`
clearsh=/tmp/cleardb.sh

echo $date

echo "#!/bin/sh" > $clearsh

cat $1 | while read line
do
	clientdb=`echo $line | awk '{print $1}'`
	echo $clientdb

	strlen=${#line}
	if [ $strlen -eq 0 ]
	then
		continue
	fi

	#drop old table
	cmd="/usr/bin/mysql -uroot -prjkj@rjkj -e \"use $clientdb; drop table if exists \\\`$deldate\\\`;\""
	echo $cmd >> $clearsh

	mysqldump -u root -prjkj@rjkj --skip-add-drop-table $clientdb $date > /opt/nginx_tools/$date.sql
	sed 's/CREATE TABLE/CREATE TABLE IF NOT EXISTS/g' /opt/nginx_tools/$date.sql > /opt/nginx_tools/new-$date.sql
	mysql -u cdn -h $2 -pcdncdncdn $clientdb < /opt/nginx_tools/new-$date.sql 
done

rm -rf /opt/nginx_tools/$date.sql
rm -rf /opt/nginx_tools/new-$date.sql

sh $clearsh



