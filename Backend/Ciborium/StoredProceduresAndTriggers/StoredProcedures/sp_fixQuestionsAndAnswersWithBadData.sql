--To see all SPs in db,
-- SELECT ROUTINE_NAME, ROUTINE_SCHEMA, ROUTINE_DEFINITION
-- FROM information_schema.routines
-- WHERE Routine_Type = 'Procedure'

DELIMITER //
DROP PROCEDURE IF EXISTS sp_fixQuestionsAndAnswersWithBadData//
CREATE PROCEDURE sp_fixQuestionsAndAnswersWithBadData()
	BEGIN
		UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'’','\''), Explanation = REPLACE(Explanation,'’','\'');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'…','...'), Explanation = REPLACE(Explanation,'…','...');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'“','"'), Explanation = REPLACE(Explanation,'“','"');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,' ',''), Explanation = REPLACE(Explanation,' ','');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'•',''), Explanation = REPLACE(Explanation,'•','');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'–','-'), Explanation = REPLACE(Explanation,'–','-');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'÷','/'), Explanation = REPLACE(Explanation,'÷','/');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'“','"'), Explanation = REPLACE(Explanation,'“','"');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'”','"'), Explanation = REPLACE(Explanation,'”','"');

    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'’','\'');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'…','...');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'“','"');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,' ','');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'•','');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'–','-');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'÷','/');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'“','"');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'”','"');

	END //
DELIMITER ;