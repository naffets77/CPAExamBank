<?php
require_once(realpath(__DIR__)."/config.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_enums.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(ciborium_configuration::$environment_librarypath."/validate.php");
include_once(ciborium_configuration::$environment_librarypath."/database.php");
include_once(ciborium_configuration::$environment_librarypath."/question.php");

class enum_SectionType{

    //See SectionType table
    //Current as of 6/23/2013
    const FAR = 1;
    const AUD = 2;
    const BEC = 3;
    const REG = 4;
    const All = 5;
}

class enum_QuestionType{

    //See QuestionType table
    //Current as of 5/22/2013
    const MultipleChoice = 1;
    const TaskBased = 2; //not in use yet
    const All = 3;

}

class enum_LicenseTransactionType{

    //See LicenseTransactionType table
    //Current as of 6/27/2013
    const Assigned = 1;
    const Subscribed = 2;
    const Cancelled = 3;
    const Suspended = 4;
    const Expired = 5;
    const Deactivated = 6;
    const Renewed = 7;
    const Reinstated = 8;
    const Changed = 9;
}

class enum_SubscriptionType{

    //See LicenseTransactionType table
    //Current as of 6/27/2013
    const Free = 1;
    const FAR = 2;
    const AUD = 3;
    const BEC = 4;
    const REG = 5;
    const FAR_AUD = 6;
    const FAR_BEC = 7;
    const FAR_REG= 8;
    const AUD_BEC = 9;
    const AUD_REG = 10;
    const BEC_REG = 11;
    const FAR_AUD_BEC = 12;
    const FAR_AUD_REG = 13;
    const FAR_AUD_BEC_REG = 14;
    const Perpetual = 15; //not public


}

class enum_TestModeType{

    //See TestModeType table
    //Current as of 7/1/2013
    const Practice = 1;
    const TestSimulation = 2;

}

class enum_LogType{
    const Blocker = 1;
    const Critical = 2;
    const Normal = 3;
    const Warning = 4;
    const Ignore = 5;
}

class enum_PracticeNumberOfQuestions{
    const FreeLimit = 5;
    const Ten = 10;
    const Twenty = 20;
    const Thirty = 30;
    const Forty = 40;
    const Fifty = 50;
    const Sixty = 60;
    const Seventy = 70;
    const TestLength = 72;
    const Eighty = 80;
    const Ninety = 90;
}

class enum_SubscriptionTerm{
    const Monthly = 1;
    //const Annually = 2;
}

class enum_QuestionHistoryResult{
    const Correct = 1;
    const Incorrect = 2;
    //const Skipped = 3;
    const All = 4;
}

class enum_QuestionHistoryOrderBy{
    const Correct = 1;
    const Incorrect = 2;
    const Section = 3;
    const QuestionId = 4;
    const AverageTimeSpent = 5;
}

class enum_EmailTemplates{
    const Test_NonHTML = "_Test-NonHTMLEmail.html";
    const Test_HTML = "_Test-HTMLEmail.html";
    const NewUserRegistered = "RegistrationCompleted.html";
    const PasswordReset = "PasswordReset.html";
    const ContactUs_Company = "ContactUs_Company.html";
}

class enum_StripeUnitTests{
    const test_CreateToken = 1;
    const test_RetrieveToken = 2;
    const test_CreateNewCustomer = 3;
    const test_OneTimeCharge = 4;
    const test_NewSubscriptionCharge = 5;
    const test_CardDeclined = 6;
    const test_RemoveCreditCard = 7;
    const test_AddCreditCard = 8;
}

class enum_ResponseCodes{
    const Successful = 1;
    const InvalidInput = 2;
    const LoginExpired = 3;
    const ErrorThrown = 4;
    const Incomplete = 5;
    const InProgress = 6;
}
?>