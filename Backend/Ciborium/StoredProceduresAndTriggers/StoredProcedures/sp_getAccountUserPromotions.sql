DELIMITER //
DROP PROCEDURE IF EXISTS sp_getAccountUserPromotions//
CREATE PROCEDURE sp_getAccountUserPromotions(IN inAccountUserId INT)
	BEGIN
		SELECT p.*
    FROM Promotion p
    JOIN AccountUserToPromotion autp ON p.PromotionId = autp.PromotionID
    WHERE autp.AccountUserId = inAccountUserId AND autp.DateApplied IS NULL AND p.IsActive = 1;

	END //
DELIMITER ;