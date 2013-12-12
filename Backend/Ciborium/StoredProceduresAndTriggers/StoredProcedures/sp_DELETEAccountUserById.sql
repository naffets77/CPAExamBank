DELIMITER //
DROP PROCEDURE IF EXISTS sp_DELETEAccountUserById//
CREATE PROCEDURE sp_DELETEAccountUserById(IN inAccountUserId INT)
	BEGIN
		DELETE FROM `LicenseTransactionHistory` WHERE `LicenseId` IN (SELECT LicenseId FROM License WHERE AccountUserId = inAccountUserId);
		DELETE FROM `LicenseToSectionType` WHERE `LicenseId` IN (SELECT LicenseId FROM License WHERE AccountUserId = inAccountUserId);
		DELETE FROM `License` WHERE `AccountUserId` = inAccountUserId;
    DELETE FROM `AccountUserToPromotion` WHERE `AccountUserId` = inAccountUserId;
		DELETE FROM `AccountUserHistory` WHERE `AccountUserId` = inAccountUserId;
		DELETE FROM `AccountUserQuestionHistory` WHERE `AccountUserId` = inAccountUserId;
		DELETE FROM `AccountUserSettings` WHERE `AccountUserId` = inAccountUserId;
		DELETE FROM `AccountUser` WHERE `AccountUserId` = inAccountUserId;
	END //
DELIMITER ;