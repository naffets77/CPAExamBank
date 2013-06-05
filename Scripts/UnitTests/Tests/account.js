

asyncTest("Valid Login is Successful", function () {

    expect(1);

    var postData = {
        email : "demo_account@cpaexambank.com",
        password: "e368b9938746fa090d6afd3628355133",
        service: "account",
        call : "login"
    }


    $.post("/PHP/services.php", postData, function (data) {

        if (data.Reason == "" && data.Account != null) {
            ok(true, "Passed");
        }
        else {
            ok(false, "Failed");
        }
        start();

    }, 'json');

});


asyncTest("Invalid Login is Not Successfull", function () {

    expect(1);

    var postData = {
        email: "demo_account@cpaexambank.com",
        password: "e368b9938746fa090d6afd3628355131",
        service: "account",
        call: "login"
    }


    $.post("/PHP/services.php", postData, function (data) {

        if (data.Reason == "User name/Password combination did not match." && data.Account == null) {
            ok(true, "Passed");
        }
        else {
            ok(false, "Failed");
        }
        start();

    },'json');

});
