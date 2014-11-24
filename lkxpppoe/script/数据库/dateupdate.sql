DROP TRIGGER IF EXISTS dateupdate;
DELIMITER //
CREATE TRIGGER dateupdate before update on userdate for each row
begin
IF NEW.begin<NOW() AND NEW.end>NOW() THEN       
set NEW.exceed='false';
END IF;
end
//
DELIMITER ;
