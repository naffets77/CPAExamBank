DELIMITER //
DROP PROCEDURE IF EXISTS sp_applyFreeSubscriptionTimeBySectionType//
CREATE PROCEDURE sp_applyFreeSubscriptionTimeBySectionType(IN inLicenseId INT, IN inSectionTypeId INT, IN inNewExpirationDate DATETIME)
BEGIN
	DECLARE myLicenseToSectionTypeID INT;
	SET myLicenseToSectionTypeID = (SELECT IF( EXISTS (SELECT LicenseToSectionTypeId FROM LicenseToSectionType WHERE LicenseId = inLicenseId AND SectionTypeId = inSectionTypeId)), 1, 0);
	
	IF myLicenseToSectionTypeID = 1 THEN
		SET myLicenseToSectionTypeID = (SELECT LicenseToSectionTypeId FROM LicenseToSectionType WHERE LicenseId = inLicenseId AND SectionTypeId = inSectionTypeId);
	END IF;


	IF @myLicenseToSectionTypeID > 0 THEN
		UPDATE LicenseToSectionType
		SET DateExpiration = inNewExpirationDate, DateCancellation = NULL, LastModifiedBy = 'sp_applyFreeSubscriptionTimeBySectionType'
		WHERE LicenseToSectionTypeId = myLicenseToSectionTypeID;
	ELSE
		INSERT INTO LicenseToSectionType (LicenseId, SectionTypeId, DateSubscribed, DateExpiration, DateCancellation, LastModifiedBy, DateCreated, CreatedBy)
		VALUES (inLicenseId, inSectionTypeId, NOW(), inNewExpirationDate, NULL, 'sp_applyFreeSubscriptionTimeBySectionType', NOW(), 'sp_applyFreeSubscriptionTimeBySectionType');
	END IF;
END //
DELIMITER ;
