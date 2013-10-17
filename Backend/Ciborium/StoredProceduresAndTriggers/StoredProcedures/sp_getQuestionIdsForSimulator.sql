DELIMITER //
DROP PROCEDURE IF EXISTS sp_getQuestionIdsForSimulator//
CREATE PROCEDURE sp_getQuestionIdsForSimulator(IN inAccountUserId INT, IN inTestModeTypeId INT, IN inSectionTypeId INT, IN inNumberOfQuestions INT, IN inPercentRight INT, IN inPercentWrong INT)
	BEGIN

    DECLARE perc_right DOUBLE;
    DECLARE perc_wrong DOUBLE;
    DECLARE perc_new DOUBLE;

    DECLARE amountToReturn_right INT;
    DECLARE amountToReturn_wrong INT;
    DECLARE amountToReturn_new INT;

    DECLARE amountActual_right INT;
    DECLARE amountActual_wrong INT;

    SET perc_right = inPercentRight / 100;
    SET perc_wrong = inPercentWrong / 100;
    SET perc_new = 1 - perc_right - perc_wrong;

    SET amountToReturn_right = FLOOR(inNumberOfQuestions * perc_right);
    SET amountToReturn_wrong = FLOOR(inNumberOfQuestions * perc_wrong);
    SET amountToReturn_new = inNumberOfQuestions - amountToReturn_right - amountToReturn_wrong;

    /* leverage get question history SP call sp_getAccountUserQuestionHistoryMetricsWithFiltersById(3, 5, 4, 3); */
    IF inTestModeTypeId = 1
      THEN
        CREATE TEMPORARY TABLE QuestionHistoryTable AS (call sp_getAccountUserQuestionHistoryMetricsWithFiltersById(3, 5, 4, 3));
      ELSE
        CREATE TEMPORARY TABLE QuestionHistoryTable AS (call sp_getAccountUserQuestionHistoryMetricsWithFiltersById(3, 5, 4, 3));
    END IF;

    SET amountActual_right = (SELECT count(*) FROM QuestionHistoryTable WHERE WasAnsweredCorrectly = 1);
    SET amountActual_wrong = (SELECT count(*) FROM QuestionHistoryTable WHERE WasAnsweredCorrectly = 0);


    /* Check to see that variables are correct */
    SELECT * FROM QuestionHistoryTable;

    /* Sample call: CALL sp_getQuestionIdsForSimulator(3, 1, 10, 30, 20); */
	END //
DELIMITER ;