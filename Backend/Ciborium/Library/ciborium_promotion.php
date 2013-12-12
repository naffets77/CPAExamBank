<?php
require_once(realpath(__DIR__)."/config.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_enums.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(ciborium_configuration::$environment_librarypath."/validate.php");
include_once(ciborium_configuration::$environment_librarypath."/database.php");
include_once(ciborium_configuration::$environment_librarypath."/account.php");
include_once(ciborium_configuration::$environment_librarypath."/promotion.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_email.php");

class ciborium_promotion{


    public static function validatePromotionCodeForUser($inPromotionCode, $inAccountUserId, $inCaller){
        $returnArray = array(
            'Result' => 0,
            'Reason' => ""
        );

        $result = promotion::validateActivePromotionByCode($inPromotionCode, $inCaller);
        if($result['Result']){
            //TODO: create function to check AU promo table
            $returnArray['Reason'] = "Promo code was valid, but check for user not implemented yet.";
        }
        else{
            return $result;
        }

        return $returnArray;
    }

    /**
     * @param $inPromotionCode
     * @param $inCaller
     * @return array
     */
    public static function validateActivePromotionByCode($inPromotionCode, $inCaller){
        $returnArray = array(
            'Result' => 0,
            'Reason' => ""
        );

        $promotionId = promotion::verifyPromotionExistsByCode($inPromotionCode);
        if($promotionId){
            return ciborium_promotion::validateActivePromotion($promotionId, $inCaller);
        }
        else{
            $returnArray['Reason'] = "Promotion code not found.";
        }

        return $returnArray;
    }

    /**
     * @param $inPromotionId
     * @param $inCaller
     * @return array
     */
    public static function validateActivePromotion($inPromotionId, $inCaller){
        $returnArray = array(
            'Result' => 0,
            'Reason' => ""
        );

        $promotionId = promotion::verifyPromotionExistsById($inPromotionId);
        if($promotionId){
            $promotion = promotion::getPromotionById($inPromotionId)[0];

            //validate
            if($promotion->IsActive){

                //Non-expiring promotion
                $nonExpiringPromotion = $promotion->DateExpiration == null && $promotion->MaxRedemptions == -1 ? true : false;
                if($nonExpiringPromotion){
                    $returnArray['Result'] = 1;
                    $returnArray['Reason'] = ciborium_promotion::buildPromotionResultMessage_Public($promotion, $nonExpiringPromotion);
                    return $returnArray;
                }

                $currentPHPTime = time();
                $maxRedemptionsReached = $promotion->MaxRedemptions > 0 && ($promotion->TimesRedeemed >= $promotion->MaxRedemptions) ? true : false;
                $promotionExpired = $promotion->DateExpiration != null && ($currentPHPTime >= util_datetime::getDateTimeToPHPTime($promotion->DateExpiration)) ? true : false;
                $promotionActivated = $currentPHPTime >= util_datetime::getDateTimeToPHPTime($promotion->DateActivation) ? true : false;

                if($promotionActivated && !$promotionExpired && !$maxRedemptionsReached){
                    $returnArray['Result'] = 1;
                    $returnArray['Reason'] = ciborium_promotion::buildPromotionResultMessage_Public($promotion, $nonExpiringPromotion);
                }
                elseif(!$promotionActivated){
                    $returnArray['Reason'] = "Promotion code is not active yet.";
                }
                elseif($promotionExpired){
                    $returnArray['Reason'] = "Promotion code is expired.";
                }
                elseif($maxRedemptionsReached){
                    $returnArray['Reason'] = "Promotion code maximum redemptions reached.";
                }
                else{
                    $returnArray['Reason'] = "Promotion code not found.";
                    $ErrorMessage = "Promotion code validation failed and is not accounted for in business logic. PromotionID (".$inPromotionId."). Called by ".$inCaller;
                    util_errorlogging::LogGeneralError(3, $ErrorMessage, __METHOD__, __FILE__);
                }
            }
            else{
                $returnArray['Reason'] = "Promotion code not found.";
            }
        }
        else{
            $returnArray['Reason'] = "Promotion code not found.";
        }

        return $returnArray;
    }

    //TODO: add PST etc to expiration time
    public static function buildPromotionResultMessage_Public($inPromotion, $inIsNonExpiringPromotion){
        $returnString = "";

        switch($inPromotion->PromotionTypeId){
            case enum_PromotionType::PercentOff_OneTime:
                $returnString = "Promotion code ".$inPromotion->PromotionCode." is good for ".$inPromotion->PromotionValue."% off your first month of subscription. "; //TODO: fix this for when we really use one time
                if(!$inIsNonExpiringPromotion){
                    $returnString .= "Code expires ";
                    $returnString .= $inPromotion->DateExpiration != null ? "on ".util_datetime::getDateTimeToDateWithTime($inPromotion->DateExpiration)." " : "";
                    if($inPromotion->DateExpiration != null){
                        $returnString .= $inPromotion->MaxRedemptions != -1 ? "or when maximum redemptions are reached. " : ".";
                    }
                    else{
                        $returnString .= "when maximum redemptions are reached. ";
                    }
                }
                break;
            case enum_PromotionType::PercentOff_Monthly:
                $returnString = "Promotion code ".$inPromotion->PromotionCode." is good for ".$inPromotion->PromotionValue."% off per month for ".$inPromotion->PromotionDuration." month(s) of subscription. ";
                if(!$inIsNonExpiringPromotion){
                    $returnString .= "Code expires ";
                    $returnString .= $inPromotion->DateExpiration != null ? "on ".util_datetime::getDateTimeToDateWithTime($inPromotion->DateExpiration)." " : "";
                    if($inPromotion->DateExpiration != null){
                        $returnString .= $inPromotion->MaxRedemptions != -1 ? "or when maximum redemptions are reached. " : ".";
                    }
                    else{
                        $returnString .= "when maximum redemptions are reached. ";
                    }
                }
                break;
            case enum_PromotionType::AmountOff_OneTime:
                $returnString = "Promotion code ".$inPromotion->PromotionCode." is good for $".$inPromotion->PromotionValue." off your first month of subscription. "; //TODO: fix this for when we really use one time
                if(!$inIsNonExpiringPromotion){
                    $returnString .= "Code expires ";
                    $returnString .= $inPromotion->DateExpiration != null ? "on ".util_datetime::getDateTimeToDateWithTime($inPromotion->DateExpiration)." " : "";
                    if($inPromotion->DateExpiration != null){
                        $returnString .= $inPromotion->MaxRedemptions != -1 ? "or when maximum redemptions are reached. " : ".";
                    }
                    else{
                        $returnString .= "when maximum redemptions are reached. ";
                    }
                }
                break;
            case enum_PromotionType::AmountOff_Monthly:
                $returnString = "Promotion code ".$inPromotion->PromotionCode." is good for $".$inPromotion->PromotionValue." off per month for ".$inPromotion->PromotionDuration." month(s) of subscription. ";
                if(!$inIsNonExpiringPromotion){
                    $returnString .= "Code expires ";
                    $returnString .= $inPromotion->DateExpiration != null ? "on ".util_datetime::getDateTimeToDateWithTime($inPromotion->DateExpiration)." " : "";
                    if($inPromotion->DateExpiration != null){
                        $returnString .= $inPromotion->MaxRedemptions != -1 ? "or when maximum redemptions are reached. " : ".";
                    }
                    else{
                        $returnString .= "when maximum redemptions are reached. ";
                    }
                }
                break;
            default:
                break;
        }

        return $returnString;
    }
}

?>