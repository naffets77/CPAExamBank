--To see all SPs in db,
-- SELECT ROUTINE_NAME, ROUTINE_SCHEMA, ROUTINE_DEFINITION
-- FROM information_schema.routines
-- WHERE Routine_Type = 'Procedure'

DELIMITER //
DROP PROCEDURE IF EXISTS sp_getQuestionsAndAnswersWithBadData//
CREATE PROCEDURE sp_getQuestionsAndAnswersWithBadData()
	BEGIN
		SELECT * FROM `Question` WHERE displaytext like '%’%' OR Explanation LIKE '%’%';
    SELECT * FROM `QuestionToAnswers` WHERE displaytext like '%’%';

    SELECT * FROM `Question` WHERE displaytext like '%…%' OR Explanation LIKE '%…%';
    SELECT * FROM `QuestionToAnswers` WHERE displaytext like '%…%';

    SELECT * FROM `Question` WHERE displaytext like '%“%' OR Explanation LIKE '%“%';
    SELECT * FROM `QuestionToAnswers` WHERE displaytext like '%“%';

    SELECT * FROM `Question` WHERE displaytext like '% %' OR Explanation LIKE '% %';
    SELECT * FROM `QuestionToAnswers` WHERE displaytext like '% %';

    SELECT * FROM `Question` WHERE displaytext like '%•%' OR Explanation LIKE '%•%';
    SELECT * FROM `QuestionToAnswers` WHERE displaytext like '%•%';

    SELECT * FROM `Question` WHERE displaytext like '%–%' OR Explanation LIKE '%–%';
    SELECT * FROM `QuestionToAnswers` WHERE displaytext like '%–%';

    SELECT * FROM `Question` WHERE displaytext like '%÷%' OR Explanation LIKE '%÷%';
    SELECT * FROM `QuestionToAnswers` WHERE displaytext like '%÷%';

    SELECT * FROM `Question` WHERE displaytext like '%“%' OR Explanation LIKE '%“%';
    SELECT * FROM `QuestionToAnswers` WHERE displaytext like '%“%';

    SELECT * FROM `Question` WHERE displaytext like '%”%' OR Explanation LIKE '%”%';
    SELECT * FROM `QuestionToAnswers` WHERE displaytext like '%”%';

	END //
DELIMITER ;