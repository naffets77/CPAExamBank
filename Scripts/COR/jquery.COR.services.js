


// Promotion Code

$.COR.services.checkPromoCode = function (options, successCallback, failCallback) {

    var ph = new $.COR.Utilities.PostHandler({
        service: "promotion", call: "validatePromotionCode",
        params: {
            promoCode: options.promoCode
        },
        success: function (data) {
            if (typeof successCallback == 'function') {
                successCallback(data);
            }
        }
    });

    ph.submitPost();

}

// Question Services

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




// Subscriber Services

$.COR.services.createSubscription = function (token, successCallback) {

    if ($.COR.account.offline == false) {

        var ph = new $.COR.Utilities.PostHandler({
            service: "stripe", call: "createNewSubscriber",
            params: {
                stripeToken: token
            },
            success: function (data) {
                successCallback(data);
            }
        });

        ph.submitPost();


    }
    else {

    }

}

$.COR.services.chargeSubscription = function (subscription, promotionCode, successCallback) {

    if ($.COR.account.offline == false) {

        var ph = new $.COR.Utilities.PostHandler({
            service: "stripe", call: "chargeSubscription",
            params: {
                moduleSelection: subscription,
                promoCode : promotionCode
            },
            success: function (data) {
                successCallback(data);
            }
        });

        ph.submitPost();


    }
    else {

    }

}

$.COR.services.removeCreditCard = function (options, successCallback) {

    var ph = new $.COR.Utilities.PostHandler({
        service: "stripe", call: "removeCreditCard",
        params: {},
        success: function (data) {
            successCallback(data);
        }
    });

    ph.submitPost();



}

$.COR.services.changeCreditCard = function (options, successCallback) {

    if ($.COR.account.offline == false) {

        var ph = new $.COR.Utilities.PostHandler({
            service: "stripe", call: "updateNewCreditCard",
            params: {
                stripeToken: options.token
            },
            success: function (data) {
                successCallback(data);
            }
        });

        ph.submitPost();
    }
}

$.COR.services.addCreditCard = function (options, successCallback) {
    if ($.COR.account.offline == false) {

        var ph = new $.COR.Utilities.PostHandler({
            service: "stripe", call: "addCreditCard",
            params: {
                stripeToken: options.token
            },
            success: function (data) {
                successCallback(data);
            }
        });

        ph.submitPost();
    }
}


// Account Services

$.COR.services.login = function (email, password, successCallback, failcallback) {

    var COR = $.COR;

    if (COR.account.offline == false) {
        var ph = new $.COR.Utilities.PostHandler({
            service: "account", call: "login",
            params: { email: email, password: $.COR.MD5(password) },
            success: function (data) {

                if (data.Account != null) {
                    // this isn't generic, the call to account should be in the success callback too much of passing the success callback around!
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
            Subscriptions: {
                FAR: {
                    CancellationDate: null,
                    CurrentSubscriptionDate: "2013-09-08 22:24:14",
                    ExpirationDate: "2013-10-08 22:24:14",
                    FirstSubscribedDate: "2013-09-08 22:24:14",
                }
            },

            UserSettings: {
                ShowNewUserTour: "false"
            },
            PromotionCodes : null
        };


        COR.account.setup(data, successCallback);
    }
}

$.COR.services.register = function (email, password, sections, refSource, promoCode, callback) {

    if ($.COR.account.offline == false) {


        var passwordMD5 = $.COR.Utilities.getURLParameter("register") != null && $.COR.Utilities.getURLParameter("p").length > 0 ?
            password : $.COR.MD5(password);

        var ph = new $.COR.Utilities.PostHandler({
            service: "account", call: "registerNewUser",
            params: {
                email: email,
                password: passwordMD5,
                promoCode: promoCode,
                sections: JSON.stringify(sections),
                referralSource : refSource
            },
            success: function (data) {

                callback(data);

            }
        });

        ph.submitPost();
    }
    else {
        alert("Registration Not Available In Offline Mode");
    }


};

$.COR.services.updatePassword = function (options, successCallback) {

    var ph = new $.COR.Utilities.PostHandler({
        service: "account", call: "updatePassword",
        params: { password: options.password, newPassword: options.newPassword, confirmPassword: options.newPassword},
        success: function (data) {

            successCallback(data);

        }
    });

    ph.submitPost();

}

$.COR.services.sendResetEmail = function (options, successCallback, failedCallback) {

    var ph = new $.COR.Utilities.PostHandler({
        service: "account", call: "sendResetPasswordEmail",
        params: {
            email: options.email
        },
        success: function (data) {
            successCallback();
        },
        error: function () {
            failedCallback();
        }
    });

    ph.submitPost();
}

$.COR.services.validatePasswordResetLink = function (options, successCallback) {

    var ph = new $.COR.Utilities.PostHandler({
        service: "account", call: "loginFromResetURL",
        params: {
            email: options.email,
            hashkey: options.hash
        },
        success: function (data) {
            successCallback(data);
        }
    });

    ph.submitPost();



}

$.COR.services.resetPassword = function (options, successCallback) {

    var ph = new $.COR.Utilities.PostHandler({
        service: "account", call: "resetPassword",
        params: { newPassword: options.password, confirmPassword: options.password, Hash: options.hash },
        success: function (data) {

            successCallback(data);

        }
    });

    ph.submitPost();

}

