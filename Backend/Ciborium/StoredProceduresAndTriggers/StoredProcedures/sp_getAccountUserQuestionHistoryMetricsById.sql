DELIMITER //
DROP PROCEDURE IF EXISTS sp_getAccountUserQuestionHistoryMetricsById//
CREATE PROCEDURE sp_getAccountUserQuestionHistoryMetricsById(IN inAccountUserId INT)
	BEGIN
		DECLARE n INT;
		DECLARE i INT;
		CREATE TEMPORARY TABLE temp_table1 AS (SELECT * FROM AccountUserQuestionHistory WHERE `AccountUserId` = inAccountUserId AND `IsActive` = 1 AND `IsIgnored` = 0);

		CREATE TEMPORARY TABLE temp_table2 AS (
		SELECT
		auqh.QuestionId,
		st.SectionType,
		0 AS TimesCorrect,
		0 AS TimesIncorrect,
    0 AS TimesAnswered,
		0 AS AverageTimePerQuestion,
		auqh.IsActive
		FROM temp_table1 auqh
		JOIN Question q on auqh.QuestionId = q.QuestionId
		JOIN SectionType st on q.SectionTypeId = st.SectionTypeId
		GROUP BY QuestionId
		);


		SELECT COUNT(*) FROM temp_table2 INTO n;
		SET i = 0;
		WHILE i< n
			DO
				SET @QuestionId = (SELECT QuestionId FROM temp_table2 LIMIT i, 1);
				SET @TimesCorrect = (SELECT count(*)
					FROM temp_table1
					WHERE WasAnsweredCorrectly = 1
					AND IsActive = 1
					AND WasSkipped = 0
					AND QuestionId = @QuestionId
					GROUP BY QuestionId);

				SET @TimesIncorrect = (SELECT count(*)
					FROM temp_table1
					WHERE WasAnsweredCorrectly = 0
					AND IsActive = 1
					AND WasSkipped IN (0,1)
					AND QuestionId = @QuestionId
					GROUP BY QuestionId);

        SET @TimesAnswered = (SELECT sum(@TimesCorrect + @TimesIncorrect));

				SET @AvgTimePerQuestion = (SELECT ROUND(AVG(TimeSpentOnQuestion)) FROM temp_table1 WHERE QuestionId = @QuestionId);

    UPDATE temp_table2 SET TimesCorrect = @TimesCorrect, TimesIncorrect = @TimesIncorrect, TimesAnswered = @TimesAnswered, AverageTimePerQuestion = @AvgTimePerQuestion WHERE QuestionId = @QuestionId;

				SET i = i + 1;
		END WHILE;



		SELECT * FROM temp_table2;

	END //
DELIMITER ;