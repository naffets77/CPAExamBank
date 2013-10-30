DELIMITER //
DROP PROCEDURE IF EXISTS sp_fixQuestionsAndAnswersWithBadData//
CREATE PROCEDURE sp_fixQuestionsAndAnswersWithBadData()
	BEGIN
		UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'<p></p>',''), Explanation = REPLACE(Explanation,'<p></p>','');
		UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'’','\''), Explanation = REPLACE(Explanation,'’','\'');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'…','...'), Explanation = REPLACE(Explanation,'…','...');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'“','"'), Explanation = REPLACE(Explanation,'“','"');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,' ',''), Explanation = REPLACE(Explanation,' ','');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'•',''), Explanation = REPLACE(Explanation,'•','');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'–','-'), Explanation = REPLACE(Explanation,'–','-');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'÷','/'), Explanation = REPLACE(Explanation,'÷','/');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'“','"'), Explanation = REPLACE(Explanation,'“','"');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'”','"'), Explanation = REPLACE(Explanation,'”','"');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'\r',''), Explanation = REPLACE(Explanation,'\r','');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'\n',''), Explanation = REPLACE(Explanation,'\n','');
    UPDATE `Question` SET DisplayText = REPLACE(DisplayText,'—','-'), Explanation = REPLACE(Explanation,'—','-');

    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'<p></p>','');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'’','\'');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'…','...');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'“','"');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,' ','');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'•','');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'–','-');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'÷','/');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'“','"');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'”','"');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'\r','');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'\n','');
    UPDATE `QuestionToAnswers` SET DisplayText = REPLACE(DisplayText,'—','-');

	END //
DELIMITER ;