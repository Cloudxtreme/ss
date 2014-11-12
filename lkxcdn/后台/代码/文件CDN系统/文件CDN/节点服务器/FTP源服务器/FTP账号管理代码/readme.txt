#ftp
-A RH-Firewall-1-INPUT -m state -m tcp -p tcp --dport 21 --state NEW -j ACCEPT
-A RH-Firewall-1-INPUT -m state -m tcp -p tcp --dport 2200:2300 --state NEW -j ACCEPT
