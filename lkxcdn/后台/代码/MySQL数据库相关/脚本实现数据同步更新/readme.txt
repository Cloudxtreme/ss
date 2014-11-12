暂时先用PHP脚本定时检查数据库表格是否发生修改，如果发生修改则更新从数据库，
替换MySQL的恶心主从备份机制

启动run脚本

run_main.sh 

#!/bin/sh

#先进入程序根目录，这样记录上次表格的日期数据文件data会建立在此文件夹下
cd /opt/dns_db_sync

/usr/bin/php /opt/dns_db_sync/main.php