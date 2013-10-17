DELIMITER //
DROP PROCEDURE IF EXISTS sp_CopyQuestionAndAnswersById//
CREATE PROCEDURE sp_CopyQuestionAndAnswersById(IN inQuestionId INT)
	BEGIN
		DECLARE i INT;
		DECLARE newQuestionId INT;

    CREATE TEMPORARY TABLE temp_table1 AS (SELECT *
			FROM Question
			WHERE QuestionId = inQuestionId);

		CREATE TEMPORARY TABLE temp_table2 AS (SELECT *
			FROM QuestionToAnswers
			WHERE QuestionId = inQuestionId);

    UPDATE temp_table1 SET QuestionId = DEFAULT, QuestionClientId = '', IsApprovedForUse = 0, IsActive = 0, IsDeprecated = 0, LastModifiedBy = 'sp_CopyQuestionAndAnswersById', CreatedBy = 'sp_CopyQuestionAndAnswersById', DateCreated = NOW();

    INSERT INTO Question SELECT * FROM temp_table1;
    SET newQuestionId = LAST_INSERT_ID();

    UPDATE temp_table2 SET QuestionToAnswersId = DEFAULT, QuestionId = newQuestionId, LastModifiedBy = 'sp_CopyQuestionAndAnswersById', CreatedBy = 'sp_CopyQuestionAndAnswersById', DateCreated = NOW();

    INSERT INTO QuestionToAnswers SELECT * FROM temp_table2;

    SELECT newQuestionId;
	END //
DELIMITER ;