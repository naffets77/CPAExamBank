DELIMITER //
DROP PROCEDURE IF EXISTS sp_getAccountUserQuestionHistoryWithFiltersById//
CREATE PROCEDURE sp_getAccountUserQuestionHistoryWithFiltersById(IN inAccountUserId INT, IN inSectionTypeId INT, IN inResultId INT, IN inOrderById INT)
	BEGIN

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
		      CREATE TEMPORARY TABLE temp_table1 AS (SELECT QuestionId, TimeSpentOnQuestion, TestModeTypeId, QuestionsToAnswersId, WasAnsweredCorrectly, WasSkipped, DateCreated
		        FROM AccountUserQuestionHistory
		        WHERE AccountUserId = inAccountUserId
		          AND IsActive = 1 AND IsIgnored = 0
		          AND QuestionId IN (SELECT QuestionId FROM EligibleQuestionIdTable)
		          AND WasAnsweredCorrectly = 1);
		    ELSEIF inResultId = 2
		    THEN
		      CREATE TEMPORARY TABLE temp_table1 AS (SELECT QuestionId, TimeSpentOnQuestion, TestModeTypeId, QuestionsToAnswersId, WasAnsweredCorrectly, WasSkipped, DateCreated
		        FROM AccountUserQuestionHistory
		        WHERE AccountUserId = inAccountUserId
		          AND IsActive = 1 AND IsIgnored = 0
		          AND QuestionId IN (SELECT QuestionId FROM EligibleQuestionIdTable)
		          AND WasAnsweredCorrectly = 0 AND WasSkipped = 0);
		    ELSEIF inResultId = 3
		    THEN
		      CREATE TEMPORARY TABLE temp_table1 AS (SELECT QuestionId, TimeSpentOnQuestion, TestModeTypeId, QuestionsToAnswersId, WasAnsweredCorrectly, WasSkipped, DateCreated
		        FROM AccountUserQuestionHistory
		        WHERE AccountUserId = inAccountUserId
		          AND IsActive = 1 AND IsIgnored = 0
		          AND QuestionId IN (SELECT QuestionId FROM EligibleQuestionIdTable)
		          AND WasAnsweredCorrectly = 0 AND WasSkipped = 1);
		    ELSE
		      CREATE TEMPORARY TABLE temp_table1 AS (SELECT QuestionId, TimeSpentOnQuestion, TestModeTypeId, QuestionsToAnswersId, WasAnsweredCorrectly, WasSkipped, DateCreated
		        FROM AccountUserQuestionHistory
		        WHERE AccountUserId = inAccountUserId
		          AND IsActive = 1 AND IsIgnored = 0
		          AND QuestionId IN (SELECT QuestionId FROM EligibleQuestionIdTable)
		          AND WasAnsweredCorrectly IN (0,1));
		    END IF;
		ELSE
      CREATE TEMPORARY TABLE temp_table1 AS (SELECT QuestionId, TimeSpentOnQuestion, TestModeTypeId, QuestionsToAnswersId, WasAnsweredCorrectly, WasSkipped, DateCreated
         FROM AccountUserQuestionHistory
         WHERE AccountUserId = inAccountUserId
               AND IsActive = 1 AND IsIgnored = 0
               AND QuestionId IN (SELECT QuestionId FROM EligibleQuestionIdTable)
               AND WasAnsweredCorrectly IN (0,1));
		END IF;


		CREATE TEMPORARY TABLE temp_table2 AS (
		SELECT
		auqh.QuestionId,
		tmt.DisplayName AS SimulationMode,
		st.SectionType AS TestSection,
		q.DisplayText AS Question,
		auqh.QuestionsToAnswersId,
		CASE
			WHEN auqh.WasAnsweredCorrectly = 1 THEN 'Yes'
			ELSE 'No'
		END AS Correct,
		CASE
			WHEN auqh.WasSkipped = 1 THEN 'Yes'
			ELSE 'No'
		END AS Skipped,
		auqh.TimeSpentOnQuestion,
		auqh.DateCreated AS SimultationDate
		FROM temp_table1 auqh
		JOIN Question q on auqh.QuestionId = q.QuestionId
		JOIN SectionType st on q.SectionTypeId = st.SectionTypeId
		JOIN TestModeType tmt on auqh.TestModeTypeId = tmt.TestModeTypeId
		JOIN QuestionToAnswers qta on auqh.QuestionsToAnswersId = qta.QuestionToAnswersId
		);

    IF inOrderById = 1
      THEN
        SELECT * FROM temp_table2 ORDER BY Correct DESC;
    ELSEIF inOrderById = 2
      THEN
        SELECT * FROM temp_table2 ORDER BY Correct ASC;
    ELSEIF inOrderById = 3
      THEN
        SELECT * FROM temp_table2 ORDER BY TestSection ASC;
    ELSEIF inOrderById = 4
      THEN
        SELECT * FROM temp_table2 ORDER BY QuestionId ASC;
    ELSEIF inOrderById = 5
      THEN
        SELECT * FROM temp_table2 ORDER BY TimeSpentOnQuestion ASC;
    ELSE
      SELECT * FROM temp_table2;
    END IF;

	END //
DELIMITER ;