DELIMITER //
DROP PROCEDURE IF EXISTS sp_getAccountUserQuestionHistoryMetricsWithFiltersById//
CREATE PROCEDURE sp_getAccountUserQuestionHistoryMetricsWithFiltersById(IN inAccountUserId INT, IN inSectionTypeId INT, IN inResultId INT, IN inOrderById INT)
	BEGIN
		DECLARE n INT;
		DECLARE i INT;

		IF inSectionTypeId != 5
		THEN
		  CREATE TEMPORARY TABLE EligibleQuestionIdTable AS (SELECT QuestionId FROM Question WHERE IsApprovedForUse = 1 AND IsActive = 1 AND SectionTypeId = inSectionTypeId);
		ELSE
      CREATE TEMPORARY TABLE EligibleQuestionIdTable AS (SELECT QuestionId FROM Question WHERE IsApprovedForUse = 1 AND IsActive = 1);
		END IF;

    IF inResultId != 4
		THEN
		    IF inResultId = 1
		    THEN
		      CREATE TEMPORARY TABLE temp_table1 AS (SELECT *
		        FROM AccountUserQuestionHistory
		        WHERE AccountUserId = inAccountUserId
		          AND IsActive = 1 AND IsIgnored = 0
		          AND QuestionId IN (SELECT QuestionId FROM EligibleQuestionIdTable)
		          AND WasAnsweredCorrectly = 1);
		    ELSEIF inResultId = 2
		    THEN
		      CREATE TEMPORARY TABLE temp_table1 AS (SELECT *
		        FROM AccountUserQuestionHistory
		        WHERE AccountUserId = inAccountUserId
		          AND IsActive = 1 AND IsIgnored = 0
		          AND QuestionId IN (SELECT QuestionId FROM EligibleQuestionIdTable)
		          AND WasAnsweredCorrectly = 0 AND WasSkipped = 0);
		    ELSEIF inResultId = 3
		    THEN
		      CREATE TEMPORARY TABLE temp_table1 AS (SELECT *
		        FROM AccountUserQuestionHistory
		        WHERE AccountUserId = inAccountUserId
		          AND IsActive = 1 AND IsIgnored = 0
		          AND QuestionId IN (SELECT QuestionId FROM EligibleQuestionIdTable)
		          AND WasAnsweredCorrectly = 0 AND WasSkipped = 1);
		    ELSE
		      CREATE TEMPORARY TABLE temp_table1 AS (SELECT *
		        FROM AccountUserQuestionHistory
		        WHERE AccountUserId = inAccountUserId
		          AND IsActive = 1 AND IsIgnored = 0
		          AND QuestionId IN (SELECT QuestionId FROM EligibleQuestionIdTable)
		          AND WasAnsweredCorrectly IN (0,1));
		    END IF;
		ELSE
      CREATE TEMPORARY TABLE temp_table1 AS (SELECT *
         FROM AccountUserQuestionHistory
         WHERE AccountUserId = inAccountUserId
               AND IsActive = 1 AND IsIgnored = 0
               AND QuestionId IN (SELECT QuestionId FROM EligibleQuestionIdTable)
               AND WasAnsweredCorrectly IN (0,1));
		END IF;

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

    IF inOrderById = 1
      THEN
        SELECT * FROM temp_table2 ORDER BY TimesCorrect DESC;
    ELSEIF inOrderById = 2
      THEN
        SELECT * FROM temp_table2 ORDER BY TimesIncorrect DESC;
    ELSEIF inOrderById = 3
      THEN
        SELECT * FROM temp_table2 ORDER BY SectionType ASC;
    ELSEIF inOrderById = 4
      THEN
        SELECT * FROM temp_table2 ORDER BY QuestionId ASC;
    ELSEIF inOrderById = 5
      THEN
        SELECT * FROM temp_table2 ORDER BY AverageTimePerQuestion DESC;
    ELSEIF inOrderById = 6
      THEN
        SELECT * FROM temp_table2 ORDER BY TimesAnswered ASC;
    ELSE
      SELECT * FROM temp_table2;
    END IF;

	END //
DELIMITER ;