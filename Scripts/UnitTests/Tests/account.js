

asyncTest("Login", function () {

    expect(1);

    var postData = {
        email : "demo_account@cpaexambank.com",
        password: "e368b9938746fa090d6afd3628355133",
        service: "account",
        call : "login"
    }


    $.post("/PHP/services.php", postData, function (data) {

        ok(true, "Passed and ready to resume!");
        start();

    });

});
