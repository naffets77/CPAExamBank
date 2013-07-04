


$.COR.services.login = function (email, password, successCallback, failcallback) {

    var COR = $.COR;

    if (COR.account.offline == false) {
        var ph = new $.COR.Utilities.PostHandler({
            service: "account", call: "login",
            params: { email: email, password: $.COR.MD5(password) },
            success: function (data) {

                if (data.Account != null) {
                    COR.account.setup(data, successCallback);
                }
                else {
                    failcallback(data.LoginFailedReason);
                }

            }
        });

        ph.submitPost();
    }
    else {

        // 
        var data = {
            Account: {
                AccountUserId: "0",
                ContactEmail: "offlineuser@pubty.com",
                LoginName: "offlineuser@pubty.com"
            },
            Licenses: {
                Active: "1"
            },
            UserSettings: {
                ShowNewUserTour: "false"
            }
        };


        COR.account.setup(data, successCallback);
    }
}



$.COR.services.register = function (email, password, sections) {

    if ($.COR.account.offline == false) {
        var ph = new $.COR.Utilities.PostHandler({
            service: "account", call: "registerNewUser",
            params: {
                email: email,
                password: $.COR.MD5(password),
                sections: sections
            },
            success: function (data) {

                if (data.Account != null) {
                    //COR.account.setup(data, successCallback);
                    console.log("Registration Success");
                }
                else {
                    //failcallback(data.LoginFailedReason);
                    console.log("Registration Failed:" + data.LoginFailedReason);
                }

            }
        });

        ph.submitPost();
    }
    else {
        alert("Registration Not Available In Offline Mode");
    }


};