#!/bin/sh

function compress_portrate()
{
        MYSQL_CMD="/usr/bin/mysql -uroot -prjkj@rjkj cdn_portrate_stats"

	DB="cdn_portrate_stats"

        now=$1

        source_tablename="$now"
        tablename="$now-gb5m"

        c_sql="create table if not exists \`$tablename\`(\
                id int(11) primary key auto_increment,\
                hostname char(100),\
                outrate int(11),\
                inrate int(11),\
                \`time\` time)ENGINE=MyISAM DEFAULT CHARSET=utf8;"

        de_sql="delete from \`$tablename\`;"

        i_sql="insert into \`$tablename\`(hostname,outrate,inrate,\`time\`)\
                select hostname,sum(outrate),sum(inrate),concat(cast(hour(time) as char),':',cast(minute(time) div 5 \052 5 as char),':00')\
                from \`$source_tablename\` group by hostname,hour(time),minute(time) div 5;"

	dr_sql="drop table \`$source_tablename\`;"

	a_sql="alter table \`$tablename\` rename to \`$source_tablename\`;"

	#echo "drop table \`$tablename\`;"
	echo $c_sql
        echo $de_sql
        echo -e $i_sql
	echo $dr_sql
	echo $a_sql
}

function compress_traffic()
{
        MYSQL_CMD="/usr/bin/mysql -uroot -prjkj@rjkj cdn_client_traffic"

        DB="cdn_client_traffic"

	now=$1

        source_tablename="$now"
        tablename="$now-gb1d"

        c_sql="create table if not exists \`$tablename\`(\
                id int(11) primary key auto_increment,\
                hostname char(100),\
                traffic bigint)ENGINE=MyISAM DEFAULT CHARSET=utf8;"

        de_sql="delete from \`$tablename\`;"

        i_sql="insert into \`$tablename\`(hostname,traffic)\
                select hostname,sum(traffic)\
                from \`$source_tablename\` group by hostname;"

        dr_sql="drop table \`$source_tablename\`;"

        a_sql="alter table \`$tablename\` rename to \`$source_tablename\`;"

        #echo "drop table \`$tablename\`;"
	echo $c_sql
        echo $de_sql
        echo -e $i_sql
        echo $dr_sql
        echo $a_sql
}


if [[ -z $1 ]]; then
	echo "please input month!"
	exit 0
fi

if [[ -z $2 ]]; then
	echo "please input compress type!"
	exit 0
fi

month=$1
t_month=$1
(( t_month += 1))
type=$2

case $type in
	portrate)
	;;
	traffic)
	;;
	*)
		echo "unknow compress type!please input portrate or traffic"
		exit 0
	;;
esac

date=`date +%Y-%m-%d -d " 2012/$month/01 -1 day"`
lastday=`date +%Y-%m-%d -d " 2012/$t_month/01 last day"`

i=0
while([ "$date" \< "$lastday" ])
do
	date=`date +%Y-%m-%d -d " 2012/$month/01 $i day"`
	case $type in   
        	portrate)
			compress_portrate $date
        	;;      
        	traffic)
			compress_traffic $date
        	;;
        	*)
        	;;      
	esac
	i=$(($i+1))
done
exit 1
