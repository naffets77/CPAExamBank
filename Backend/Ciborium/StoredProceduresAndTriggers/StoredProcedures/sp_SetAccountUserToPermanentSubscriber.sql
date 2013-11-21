DELIMITER //
DROP PROCEDURE IF EXISTS sp_SetAccountUserToPermanentSubscriber//
CREATE PROCEDURE sp_SetAccountUserToPermanentSubscriber(IN inAccountUserID INT)
	BEGIN
    DECLARE myLicenseID INT;
    DECLARE i INT;
    DECLARE n INT;
    SET myLicenseID = (SELECT LicenseID FROM License WHERE AccountUserID = inAccountUserID LIMIT 1);

    CREATE TEMPORARY TABLE temp_table1 AS (SELECT * FROM LicenseToSectionType WHERE `LicenseId` = myLicenseID);
    CREATE TEMPORARY TABLE temp_table2 AS (SELECT SectionTypeID FROM SectionType WHERE `SectionTypeID` NOT IN (5));
    CREATE TEMPORARY TABLE temp_table3 AS (SELECT * FROM LicenseToSectionType WHERE `LicenseID` > 5000);

    SELECT COUNT(*) FROM temp_table2 INTO n;
    SET i = 0;
    WHILE i< n
    DO
      SET @SectionTypeId = (SELECT SectionTypeID FROM temp_table2 LIMIT i, 1);
      SET @SubscriptionExists = (SELECT IF( EXISTS(SELECT * FROM temp_table1 WHERE SectionTypeID = @SectionTypeID), 1, 0));
      IF @SubscriptionExists = 1 THEN
          UPDATE LicenseToSectionType
          SET DateExpiration = '2038-01-01', DateCancellation = NULL, LastModifiedBy = 'sp_SetAccountUserToPermanentSubscriber'
          WHERE SectionTypeID = @SectionTypeId AND LicenseID = myLicenseID;
        ELSE
          INSERT INTO temp_table3 SELECT * FROM temp_table1 WHERE SectionTypeID = @SectionTypeId;
      END IF;

      SET i = i + 1;
    END WHILE;

    SELECT * FROM temp_table2;
    SELECT * FROM temp_table3;
	END //
DELIMITER ;
