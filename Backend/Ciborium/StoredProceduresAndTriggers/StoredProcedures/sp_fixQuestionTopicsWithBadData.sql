DELIMITER //
DROP PROCEDURE IF EXISTS sp_fixQuestionsAndAnswersWithBadData//
CREATE PROCEDURE sp_fixQuestionTopicsWithBadData()
	BEGIN
		UPDATE `QuestionTopic` SET Area = REPLACE(Area,'<p></p>',''), `Group` = REPLACE(`Group`,'<p></p>',''), Topic = REPLACE(Topic,'<p></p>','');
		UPDATE `QuestionTopic` SET Area = REPLACE(Area,'’','\''), `Group` = REPLACE(`Group`,'’','\''), Topic = REPLACE(Topic,'’','\'');
		UPDATE `QuestionTopic` SET Area = REPLACE(Area,'…','...'), `Group` = REPLACE(`Group`,'…','...'), Topic = REPLACE(Topic,'…','...');
		UPDATE `QuestionTopic` SET Area = REPLACE(Area,'“','"'), `Group` = REPLACE(`Group`,'“','"'), Topic = REPLACE(Topic,'“','"');
		UPDATE `QuestionTopic` SET Area = REPLACE(Area,' ',''), `Group` = REPLACE(`Group`,' ',''), Topic = REPLACE(Topic,' ','');
		UPDATE `QuestionTopic` SET Area = REPLACE(Area,'•',''), `Group` = REPLACE(`Group`,'•',''), Topic = REPLACE(Topic,'•','');
		UPDATE `QuestionTopic` SET Area = REPLACE(Area,'–','-'), `Group` = REPLACE(`Group`,'–','-'), Topic = REPLACE(Topic,'–','-');
		UPDATE `QuestionTopic` SET Area = REPLACE(Area,'÷','/'), `Group` = REPLACE(`Group`,'÷','/'), Topic = REPLACE(Topic,'÷','/');
		UPDATE `QuestionTopic` SET Area = REPLACE(Area,'“','"'), `Group` = REPLACE(`Group`,'“','"'), Topic = REPLACE(Topic,'“','"');
		UPDATE `QuestionTopic` SET Area = REPLACE(Area,'”','"'), `Group` = REPLACE(`Group`,'”','"'), Topic = REPLACE(Topic,'”','"');
		UPDATE `QuestionTopic` SET Area = REPLACE(Area,'\r',''), `Group` = REPLACE(`Group`,'\r',''), Topic = REPLACE(Topic,'\r','');
		UPDATE `QuestionTopic` SET Area = REPLACE(Area,'\n',''), `Group` = REPLACE(`Group`,'\n',''), Topic = REPLACE(Topic,'\n','');
		UPDATE `QuestionTopic` SET Area = REPLACE(Area,'—','-'), `Group` = REPLACE(`Group`,'—','-'), Topic = REPLACE(Topic,'—','-');
	END //
DELIMITER ;