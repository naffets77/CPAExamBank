DELIMITER //
DROP PROCEDURE IF EXISTS sp_DELETEAccountUserQuestionHistoryById//
CREATE PROCEDURE sp_DELETEAccountUserQuestionHistoryById(IN inAccountUserId INT)
	BEGIN
    DELETE FROM `AccountUserQuestionHistory` WHERE `AccountUserId` = inAccountUserId;
	END //
DELIMITER ;