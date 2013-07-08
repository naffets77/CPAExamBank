



$.COR.services.getQuestionHistoryMetrics = function () {

    var ph = new $.COR.Utilities.PostHandler({
        service: "question", call: "getAccountUserQuestionHistory",
        params: {
            AccountUserId: $.COR.account.user.AccountUserId,
            QuestionAmount: 50,
            SectionTypeId: $("#my-review-section-type").val()
        },
        success: function (data) {
            $(thisElement).removeClass("disabled");
            self.BuildQuestionHistory(data.QuestionHistory);
        }
    });

}


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



$.COR.services.register = function (email, password, sections, callback) {

    if ($.COR.account.offline == false) {
        var ph = new $.COR.Utilities.PostHandler({
            service: "account", call: "registerNewUser",
            params: {
                email: email,
                password: $.COR.MD5(password),
                sections: JSON.stringify(sections)
            },
            success: function (data) {

                callback();

            }
        });

        ph.submitPost();
    }
    else {
        alert("Registration Not Available In Offline Mode");
    }


};


