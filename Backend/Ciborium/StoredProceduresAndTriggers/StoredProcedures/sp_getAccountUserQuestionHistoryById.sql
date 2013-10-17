DELIMITER //
DROP PROCEDURE IF EXISTS sp_getAccountUserQuestionHistoryById//
CREATE PROCEDURE sp_getAccountUserQuestionHistoryById(IN inAccountUserId INT)
	BEGIN
		CREATE TEMPORARY TABLE temp_table1 AS (SELECT QuestionId, TimeSpentOnQuestion, TestModeTypeId, QuestionsToAnswersId, WasAnsweredCorrectly, WasSkipped, DateCreated
			FROM AccountUserQuestionHistory
			WHERE `AccountUserId` = inAccountUserId
				AND `IsActive` = 1
				AND `IsIgnored` = 0);

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

		SELECT * FROM temp_table2;
	END //
DELIMITER ;