insert into programn(name,pid,command,status) values("synuser","0","nohup php /etc/raddb/syn-user-on-radius/synuser.php > /var/log/syn.log &","off");
insert into programn(name,pid,command,status) values("logoutcheck","0","nohup php /etc/raddb/syn-user-on-radius/logoutcheck.php > /var/log/logoutcheck.log &","off");