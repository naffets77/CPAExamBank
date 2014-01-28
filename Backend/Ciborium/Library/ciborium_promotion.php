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
            'Reason' => "",
            'Status' => enum_PromotionToUserStatus::Unknown,
            'PromotionId' => 0
        );

        $returnArray['PromotionId'] = promotion::verifyPromotionExistsByCode($inPromotionCode);
        if($returnArray['PromotionId']){
            $activeCheckResult = ciborium_promotion::validateActivePromotion($returnArray['PromotionId'], $inCaller);

            if($activeCheckResult['Result']){
                $returnArray['Status'] = ciborium_promotion::checkPromotionStatusForUser($returnArray['PromotionId'], $inAccountUserId, $inCaller)['Status'];
                switch($returnArray['Status']){
                    case enum_PromotionToUserStatus::Applied:
                        $returnArray['Reason'] = "Promo code was already applied to this account.";
                        break;
                    case enum_PromotionToUserStatus::Unredeemed;
                        //$insertID = promotion::insertAccountUserToPromotion($promotionId, $inAccountUserId, $inCaller);
                        $returnArray['Result'] = 1;
                        $returnArray['Reason'] = "Promo code is valid and has not been redeemed by this account yet.";
                        break;
                    case enum_PromotionToUserStatus::Redeemed:
                        $returnArray['Result'] = 1;
                        $returnArray['Reason'] = "Promo code is redeemed and ready for application by this account.";
                        break;
                    default:
                        $returnArray['Reason'] = "Promo code is not valid for this account.";
                        break;
                }
            }
            else{
                $returnArray['Reason'] = $activeCheckResult['Reason'];
            }
        }
        else{
            $returnArray['Reason'] = "Promo code was not found.";
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

                //Checking status of promotion
                $promotionStatus = self::checkStatusOfPromotion($promotion)['Status'];

                if($promotionStatus == enum_PromotionStatus::Active){
                    $returnArray['Result'] = 1;
                    $returnArray['Reason'] = ciborium_promotion::buildPromotionResultMessage_Public($promotion, $nonExpiringPromotion);
                }
                elseif($promotionStatus == enum_PromotionStatus::Inactive){
                    $returnArray['Reason'] = "Promotion code is not active yet.";
                }
                elseif($promotionStatus == enum_PromotionStatus::Expired){
                    $returnArray['Reason'] = "Promotion code is expired.";
                }
                elseif($promotionStatus == enum_PromotionStatus::Redeemed){
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

    /**
     * @param $inPromotionId
     * @param $inAccountUserId
     * @param $inCaller
     * @return array
     */
    public static function checkPromotionStatusForUser($inPromotionId, $inAccountUserId, $inCaller){
        $returnArray = array(
            'Status' => enum_PromotionToUserStatus::Unredeemed,
            'AccountUserToPromotion' => null
        );

        $promoToUserObjects = promotion::getAccountUserToPromotion($inPromotionId, $inAccountUserId, $inCaller);
        if(count($promoToUserObjects) > 0){
            $returnArray['AccountUserToPromotion'] = $promoToUserObjects[0];
            if($returnArray['AccountUserToPromotion']->DateApplied != null){
                $returnArray['Status'] = enum_PromotionToUserStatus::Applied;
            }
            else{
                $returnArray['Status'] = enum_PromotionToUserStatus::Redeemed;
            }
        }

        return $returnArray;
    }

    public static function checkStatusOfPromotion($inPromotion){
        $returnArray = array(
            'Status' => enum_PromotionToUserStatus::Unredeemed
        );
        $promotion = $inPromotion;
        $currentPHPTime = time();
        $maxRedemptionsReached = ($promotion->MaxRedemptions > 0 && $promotion->TimesRedeemed >= $promotion->MaxRedemptions) ? true : false;
        $promotionExpired = ($promotion->DateExpiration != null && $currentPHPTime >= util_datetime::getDateTimeToPHPTime($promotion->DateExpiration)) ? true : false;
        $promotionActivated = $currentPHPTime >= util_datetime::getDateTimeToPHPTime($promotion->DateActivation) ? true : false;

        if($promotionActivated && !$promotionExpired && !$maxRedemptionsReached){
            $returnArray['Status'] = enum_PromotionStatus::Active;
        }
        elseif(!$promotionActivated){
            $returnArray['Status'] = enum_PromotionStatus::Inactive;
        }
        elseif($promotionExpired){
            $returnArray['Status'] = enum_PromotionStatus::Expired;
        }
        elseif($maxRedemptionsReached){
            $returnArray['Status'] = enum_PromotionStatus::Redeemed;
        }
        else{
            $returnArray['Status'] = enum_PromotionStatus::Inactive;
            $ErrorMessage = "Promotion code validation failed and is not accounted for in business logic. PromotionID (".$promotion->PromotionId.").";
            util_errorlogging::LogGeneralError(3, $ErrorMessage, __METHOD__, __FILE__);
        }

        return $returnArray;
    }

    public static function redeemPromotionForUser($inPromotionId, $inAccountUserToPromotionId, $inCaller){
        promotion::applyAccountUserToPromotion($inAccountUserToPromotionId, $inCaller);
        promotion::redeemPromotion($inPromotionId, $inCaller);
    }
    /**
     * @param $inPromotionId
     * @param $inAccountUserId
     * @param $inCaller
     * @return string
     */
    public static function createPromotionStatusForUser($inPromotionId, $inAccountUserId, $inCaller){
        return promotion::insertAccountUserToPromotion($inPromotionId, $inAccountUserId, $inCaller);
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