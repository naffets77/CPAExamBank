

asyncTest("Valid Login is Successful", function () {

    expect(1);

    var postData = {
        email : "demo_account@cpaexambank.com",
        password: "e368b9938746fa090d6afd3628355133",
        service: "account",
        call : "login"
    }


    $.post("/PHP/services.php", postData, function (data) {

        if (data.reason == "" && data.Account != null) {
            ok(true, "Passed");
        }
        else {
            ok(false, "Failed");
        }
        start();

    });

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

        if (data.reason == "Invalid Password" && data.Account == undefined) {
            ok(true, "Passed");
        }
        else {
            ok(false, "Failed");
        }
        start();

    });

});
