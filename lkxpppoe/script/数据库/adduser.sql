DROP PROCEDURE IF EXISTS `radius`.`adduser`;
DELIMITER //
CREATE PROCEDURE `radius`.`adduser`(user varchar(60),password varchar(60))
BEGIN
	
insert into `radcheck` values(null,user,'User-Password',':=',password);
insert into `userinfo`(username,server) values(user,"off");
insert into `radusergroup` values(user,"testgroup",1);

END
//
DELIMITER ;
