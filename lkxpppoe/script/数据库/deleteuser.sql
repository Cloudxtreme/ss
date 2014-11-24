drop PROCEDURE if exists deleteuser;
DELIMITER //
create procedure deleteuser(user varchar(60),timestamp varchar(60))
begin
DECLARE newusername varchar(60);
select CONCAT(user,'-',timestamp) into newusername;

delete from radcheck where username=user;
delete from radusergroup where username=user;
delete from userinfo where username=user;
delete from radreply where username=user;
delete from userdate where username=user;
update radacct set username=newusername where username=user;
end
//
DELIMITER ;
