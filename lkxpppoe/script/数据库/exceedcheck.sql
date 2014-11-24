drop procedure if exists exceedcheck;
DELIMITER //
create procedure exceedcheck(user varchar(100))
begin
update `userdate` set `exceed`='true' where `username`=user and `exceed`='false' and (`begin`>CURRENT_TIMESTAMP or `end`<CURRENT_TIMESTAMP);
update `userdate` set `exceed`='false' where `username`=user and `exceed`='true' and (`begin`<CURRENT_TIMESTAMP and `end`>CURRENT_TIMESTAMP);
end
//
DELIMITER ;
