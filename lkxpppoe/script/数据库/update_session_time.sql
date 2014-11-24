drop procedure if exists update_session_time;
DELIMITER //
create procedure update_session_time(user varchar(100))
begin
call exceedcheck(user);
end;
//
DELIMITER ;
