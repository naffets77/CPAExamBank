DELIMITER //
DROP PROCEDURE IF EXISTS sp_DELETEQuestionById//
CREATE PROCEDURE sp_DELETEQuestionById(IN inQuestionId INT)
	BEGIN
		DELETE FROM `QuestionHistory` WHERE `QuestionId` = inQuestionId;
		DELETE FROM `AccountUserQuestionHistory` WHERE `QuestionId` = inQuestionId;
		DELETE FROM `QuestionToAnswers` WHERE `QuestionId` = inQuestionId;
		DELETE FROM `Question` WHERE `QuestionId` = inQuestionId;
	END //
DELIMITER ;