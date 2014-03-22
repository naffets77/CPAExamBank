

$.COR.account = {
    offline: false,
    user: null,
    hash: null,
    licenses: null,
    stripePublicKey: null,
    subscriptions: null,
    userSettings: null,
    simulator: {
        options: {
            mode: null,
            category: null,
            questionCount: null,
            strategy: null
        },
        live: false,
        questions: null,
        questionIndex: null,
        completed: false,
        currentQuestionTimer: 0,
        questionTimerIntervalId: null
    }
};



$.COR.account.setup = function (data, successCallback) {

    var self = this;
    var cacheInvalidator = $.COR.DisableCache ? "?num=" + Math.floor(Math.random() * 11000) : "";

    // Set Account Hash For Validating Requests

    $('body').append("<input id='account-hash' type='hidden' value='" + data.Hash + "'></input>");


    $.get("/HTMLPartials/Account.html" + cacheInvalidator, function (loggedinPageHTML) {

        $("#body").append(loggedinPageHTML);

        self.setUserData(data);
        self.setupEvents();
        self.initUser();

        $("#contact-us-email").val(self.user.LoginName);

        // Force login to take 1500ms

        setTimeout(function () {
            // Show proper UI
            self.showDefaultPage();


            successCallback();
        }, 1500);


    });
}

$.COR.account.setupEvents = function () {

    var self = this;


    // Navigation

    $("#user-account-navigation li").on("click", function () {

        if ($(this).hasClass("current")) { return; }

        // Nav LI UI Swapping
        $("#user-account-navigation li").removeClass("current");
        $(this).addClass("current");

        // Show/Hide Content
        $(".account-content").addClass("hidden");
        $("#account-content_" + $(this).attr("id").split("_")[1]).removeClass("hidden");

    });


    // Logout
    $("#header-logout-container").on("click", function () {

        // Put this all back later
        //// Clear Account User & stop polling and things like that?
        //self.user = null;

        //// Swap Logout with Login UI
        //$("#header-logout-container").hide();
        //$("#header-login-container").show();

        //$("#contact-us-email").val("");

        //location.hash = "";

        //// TODO: Shold post a logout here to kill the session

        window.location = "/";

    });

    // Simulator
    $("#practice-options [name='practice-category']").on("change", function () {

        $(".subscribe-message").hide();

        if ($(this).parents("tr").hasClass("trial")) {
            $("#practice-question-count").append("<option id='practice-question-trial-amount' value='25'>25</option>");
            $("#practice-question-count").val(25);
            $("#practice-question-count").attr("disabled", "disabled");
            $(this).parents("tr").first().find(".subscribe-message").css("display", "block");
        }
        else {
            $("#practice-question-trial-amount").remove();

            $("#practice-question-count").val(20);
            $("#practice-question-count").removeAttr("disabled");
        }

    });


    /* ----- Settings Management ---- */

    $("#js-content-wrapper-my-info .nav li").on('click', function () {

        if ($(this).hasClass('active')) { return; }
        $("#js-content-wrapper-my-info .nav li").removeClass('active');
        $(this).addClass('active');


        var id = $(this).attr("accountsettings");

        $(".account-settings-section").hide();

        $("#account-settings-" + id).fadeIn();

    });

    $("#account-settings-update-email").on("click", function (e) {
        e.preventDefault();
        if ($(this).hasClass("disabled")) { return; }


        var email = $("#account-settings-username").val();

        if ($.COR.validateForm($(this).parents("form"))) {

            var self = this;

            $(this).html("Saving...").addClass("disabled");

            var ph = new $.COR.Utilities.PostHandler({
                service: "account", call: "updateLoginEmail",
                params: { email: email, hash: self.hash },
                success: function (data) {

                    $.COR.Utilities.cycleButton(self, "Saved", "Update");
                    $(self).removeClass("disabled");
                }
            });

            ph.submitPost();

        }

    });

    $("#account-settings-update-password-button").on("click", function (e) {
        e.preventDefault();

        if ($.COR.validateForm($(this).parents("form")) && $(this).hasClass("disabled") == false) {

            var self = this;

            $(this).html("Saving...").addClass("disabled");
            var oldPassword = $.COR.MD5($("#account-settings-old-password").val());
            var newPassword = $.COR.MD5($("#account-settings-new-password").val());


            $.COR.services.updatePassword(
                { password: oldPassword, newPassword: newPassword },
                function (data) {

                    $("#account-settings-old-password").val("");
                    $("#account-settings-new-password").val("")
                    $("#account-settings-new-password-again").val("")
                    $("#account-settings-current-password").val(newPassword);

                    $.COR.account.user.LoginPassword = newPassword;
                    $.COR.Utilities.cycleButton(self, "Saved", "Update");
                    $(self).removeClass("disabled");
                });

        }

    });

    $("#account-change-credit-card").on("click", function () {

        $.COR.Utilities.FullScreenOverlay.loadExternal("/HTMLPartials/Account/ChangeCreditCard.html", "medium", false, function () {

            $("#update-subscription-holder .credit-card-info").show();
            $("#update-subscription-holder .amount-charged").html("$" + self.getSubscriptionTotal());

            if ($.COR.debug == true) {
                $('#card-number').val("4242424242424242");
                $('#card-cvc').val("333");
                $('#card-expiry-month').val("12");
                $('#card-expiry-year').val("2013");
            }

            $(".remove-credit-card-info").on('click', function () {

                $("#update-subscription-holder .credit-card-info").hide();

                $(".js-overlay-close").hide();
                $("#update-subscription-holder .processing").fadeIn();

                $.COR.services.removeCreditCard({}, function (data) {

                    if (data.Result == 0) {
                        $(".js-overlay-close").show();
                        $("#update-subscription-holder .processing").hide();

                        $("#update-subscription-holder .error").fadeIn();
                    }
                    else {

                        // Refresh Login
                        $.COR.checkLogin(function (data) {

                            self.setUserData(data);

                            // Use Token to load stuff

                            $(".js-overlay-close").show();
                            $("#update-subscription-holder .processing").hide();
                            $("#update-subscription-holder .credit-card-removed").fadeIn();

                        });
                    }
                });


            });

            $(".update-credit-info").on('click', function () {

                var button = this;

                $("#update-subscription-holder .credit-card-info").hide();

                if ($(this).hasClass('disabled')) { return; }
                $(this).addClass('disabled');

                // reset
                $("#credit-card-error-message-row").hide();
                $("#credit-card-error-message-row td").html("");



                $(".js-overlay-close").hide();
                $("#update-subscription-holder .processing").fadeIn();

                Stripe.setPublishableKey(self.stripePublicKey);

                Stripe.card.createToken({
                    number: $('#card-number').val(),
                    cvc: $('#card-cvc').val(),
                    exp_month: $('#card-expiry-month').val(),
                    exp_year: $('#card-expiry-year').val()
                }, function (status, response) {

                    if (response.error) {

                        $("#update-subscription-holder .processing").hide();
                        $("#update-subscription-holder .credit-card-info").show();
                        $(".js-overlay-close").show();
                        $(button).removeClass('disabled');

                        $("#credit-card-error-message-row").show();
                        $("#credit-card-error-message-row td").html(response.error.message);

                    } else {

                        // token contains id, last4, and card type
                        var token = response['id'];

                        // Update Credit Card
                        $.COR.services.changeCreditCard({ token: token }, function (data) {

                            if (data.Result == 0) {

                                $(".js-overlay-close").show();
                                $("#update-subscription-holder .processing").hide();
                                $("#update-subscription-holder .error").fadeIn();
                            }
                            else {

                                // Refresh Login
                                $.COR.checkLogin(function (data) {

                                    self.setUserData(data);

                                    // Use Token to load stuff
                                    $(".js-overlay-close").show();
                                    $("#update-subscription-holder .processing").hide();
                                    $("#update-subscription-holder .credit-card-updated").fadeIn();

                                });
                            }

                        });

                    }

                });
            });



        });

    });

    $("#account-update-subscription").on("click", function () {

        $(".account-update-subscription-error-message").hide();

        var subscriptionAmount = self.getSubscriptionTotal();

        // Validations
        var validates = true;
        // Check if there was no change

        // If no change and no subs checked
        if (subscriptionAmount == 0 && self.subscriptions.length == 0) {
            $("#account-update-no-subscriptions-selected").show();
            validates = false;
        }

        if (!validates) return; // exit if we didn't validate

        // otherwise show subscription update popup
        $.COR.Utilities.FullScreenOverlay.loadExternal("/HTMLPartials/Account/UpdateSubscription.html", "medium", false, function () {

            $("#update-subscription-holder .amount-charged").html("$" + self.getSubscriptionTotal());

            $.COR.log("Loaded Subscription Update");

            // We gotta figure out which we're showing -- update or get CC info

            if (self.subscriptions.length == 0 || self.licenses.StripeCreditCardId == "") {


                if ($.COR.debug == true) {
                    $('#card-number').val("4242424242424242");
                    $('#card-cvc').val("333");
                    $('#card-expiry-month').val("12");
                    $('#card-expiry-year').val("2013");
                }

                $(".save-credit-info").html("Start Subscription");

                $("#update-subscription-holder .credit-card-info").show();

                $("#update-subscription-holder .save-credit-info").on('click', function () {

                    var button = this;

                    if ($(this).hasClass('disabled')) { return; }
                    $(this).addClass('disabled');

                    // reset
                    $("#credit-card-error-message-row").hide();
                    $("#credit-card-error-message-row td").html("");



                    $("#update-subscription-holder .credit-card-info").hide();

                    $(".js-overlay-close").hide();
                    $("#update-subscription-holder .processing").show();

                    Stripe.setPublishableKey(self.stripePublicKey);

                    Stripe.card.createToken({
                        number: $('#card-number').val(),
                        cvc: $('#card-cvc').val(),
                        exp_month: $('#card-expiry-month').val(),
                        exp_year: $('#card-expiry-year').val()
                    }, function (status, response) {

                        if (response.error) {

                            $("#update-subscription-holder .processing").hide();
                            $("#update-subscription-holder .credit-card-info").show();
                            $(".js-overlay-close").show();
                            $(button).removeClass('disabled');

                            $("#credit-card-error-message-row").show();
                            $("#credit-card-error-message-row td").html(response.error.message);
                        } else {

                            // token contains id, last4, and card type
                            var token = response['id'];


                            var successFunction = function () {
                                // Refresh Login
                                $.COR.checkLogin(function (data) {

                                    var subscriptions = self.getSubscriptionsForServer();
                                    var promotionCode = $.COR.account.promotionCode ? $.COR.account.promotionCode.PromotionCode : null;

                                    $.COR.services.chargeSubscription(subscriptions, promotionCode, function () {

                                        $.COR.checkLogin(function (data) {

                                            self.setUserData(data);

                                            // Use Token to load stuff
                                            $(".js-overlay-close").show();
                                            $("#update-subscription-holder .processing").hide();
                                            $("#update-subscription-holder .subscription-completed").show();

                                        });

                                        // Google Adwords Conversion tracking
                                        $(body).append('<img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/976659708/?value=' + self.getSubscriptionTotal() + '&amp;label=h-nZCNzS1QcQ_Mna0QM&amp;guid=ON&amp;script=0"/>')


                                    });

                                });

                            }

                            if (self.licenses.StripeCustomerId == "") {

                                // Create Customer
                                $.COR.services.createSubscription(token, function () {
                                    successFunction();
                                });
                            }
                            else {
                                // Customer is already created, just need to update credit card
                                $.COR.services.addCreditCard({ token: token }, function (data) {
                                    successFunction();
                                });
                            }

                        }

                    });

                });
            }
            else {

                $("#update-subscription-holder .update-subscription").show();



                $("#update-subscription-holder .update-plan").on('click', function () {

                    if ($(this).hasClass('disabled')) { return; }
                    $(this).addClass('disabled');


                    $("#update-subscription-holder .update-subscription").hide();

                    // Show Processing
                    $(".js-overlay-close").hide();
                    $("#update-subscription-holder .processing").show();

                    var subscriptions = self.getSubscriptionsForServer();

                    var promotionCode = $.COR.account.promotionCode ? $.COR.account.promotionCode.PromotionCode : null;

                    $.COR.services.chargeSubscription(subscriptions, promotionCode, function () {

                        $.COR.checkLogin(function (data) {

                            self.setUserData(data);

                            // Use Token to load stuff
                            $(".js-overlay-close").show();
                            $("#update-subscription-holder .processing").hide();
                            $("#update-subscription-holder .subscription-completed").show();

                        });

                    });

                });
            }







        });

    });


    /* ----- Question Review ----- */

    $("#my-review-view-history").on("click", function () {

        var thisElement = this;

        if ($(this).hasClass("disabled")) { return; }

        $(this).addClass("disabled");
        $("#review-results tbody").html("");

        // Fix UI
        $("#review-results").hide();
        $("#review-messages .review-messages-content").hide();
        $("#review-messages-no-results").show();

        var ph = new $.COR.Utilities.PostHandler({
            service: "question", call: "getAccountUserQuestionHistory",
            params: {
                Filters: JSON.stringify({
                    "SectionTypeId": $("#my-review-section-type").val(),
                    "ResultId": $("#my-review-result-type").val(),
                    "OrderById": $("#my-review-order-by").val()
                })
            },
            success: function (data) {
                $(thisElement).removeClass("disabled");
                self.BuildQuestionHistory(data);

                setTimeout(function () {
                    $("#review-messages-no-results").hide();
                    $("#review-results").show();
                }, 1000);

            }
        });

        ph.submitPost();


    });


    /* ----- Simulator ----- */
    $(window).bind('beforeunload', function () {

        if ($.COR.account.simulator.live == true) {
            return 'If you leave now you will lose your progress. If you want to save your progress exit the simulator before you leave this page.';
        }
    });

    // Call setup on any other events that are sub of the account object


    /* ----- Tooltips ------*/

    $('.tooltip').powerTip({
        followMouse: true
        //placement:'ne'
    });



    if (self.offline != true) {
        var ph = new $.COR.Utilities.PostHandler({
            service: "question", call: "getAccountUserQuestionHistory",
            params: {
                Filters: JSON.stringify({
                    "SectionTypeId": 5,
                    "ResultId": 4,
                    "OrderById": 1
                })
            },
            success: function (data) {

                self.initReviewGrid(data);

                setTimeout(function () {
                    $("#review-messages-no-results").hide();
                    $("#review-results").show();
                }, 1000);

            }
        });

        ph.submitPost();
    }

};








// Helper Functions


$.COR.account.initUser = function () {

    // handle if user first time flag is true
    if ($.COR.account.user.IsRegistrationInfoObtained == "0") {
        this.showNewAccountPopup();
    }

    if (this.user.IsAdmin == "1") {
        $("#practice-question-count").append("<option value='-1'>All</option>");
    }
}

$.COR.account.showNewAccountPopup = function () {


    // show the overlay
    $("#popup-overlay").show();

    // show the popup (should default with swirly and loading)
    $("#popup-container").show();

    //show default loading popup
    $("#new-account-popup_0").show();


    $.get("/HTMLPartials/Account/NewInstructorAccountPopup.html", function (data) {

        $.COR.Utilities.PopupHandler.init(data, function () {

            // TODO: Could loop through popup content div's looking for forms and dynamically build them rather than hardcoding them like this... 
            // Could even build it into the popup handler ... //serialize, whould auto serialize all of the forms in the popup and return the result!
            var form1 = $("#popup-content_2 form").serialize();
            var form2 = "&" + $("#popup-content_3 form").serialize();
            var form3 = "&" + $("#popup-content_4 form").serialize();


            var postData = new Object();

            $("#popup-content-holder form input, #popup-content-holder form select").each(function (index, element) {

                var name = $(element).attr("name");
                var value = $(element).attr("value");

                switch (name) {
                    case 'registration-password':
                        postData[name] = $.COR.MD5(value);
                        break;
                    case 'password-again':
                        break;
                    default:
                        postData[name] = value;
                        break;
                }
            });

            postData['UID'] = $.COR.account.user.AccountUserId;



            var ph = new $.COR.postHandler({
                service: "account", call: "completeTeacherRegistration",
                params: postData,
                success: function (data) {
                    window.location.reload()
                }
            });

            ph.submitPost();

        });

    });

}

$.COR.account.setUserData = function (data) {
    this.user = data.Account;
    this.hash = data.Hash;
    this.licenses = data.Licenses;
    this.subscriptions = data.Subscriptions;
    this.settings = data.UserSettings;
    this.stripePublicKey = data.StripePublicKey;
    this.promotionCode = data.PromotionCodes !== null && data.PromotionCodes.length > 0 ? data.PromotionCodes[0] : null;

    $("#account-settings-username").val(this.user.LoginName);
    $("#account-settings-current-password").val(this.user.LoginPassword);
    $("#contact-us-email").val(this.user.LoginName);

    /*
        The data.Subscriptions object coverts a little odd from PHP. If it's empty then it's an array and has a length, 
        if it's not empty then it's an object w/out a length. If the length property is undefined then we know we have 
        something to loop through. There's probably a better way to do this...
    */

    // default everything to false
    $("#subscription-options input").prop('checked', false);
    $("#practice-options [name='practice-category']").parents("tr").addClass("trial");

    var AUDEnabled = false;

    if (data.Subscriptions.length === undefined) {
        for (var subscription in data.Subscriptions) {

            var subname = subscription.toLowerCase();
            var tr = $("#account_subscription_check-" + subname).parents('tr');
            var date = new Date(data.Subscriptions[subscription].ExpirationDate.split(" ")[0]);

            $(tr).find('.date').html($.COR.Utilities.formatDate(date));

            var NotExpired = new Date(data.Subscriptions[subscription].ExpirationDate.split(" ")[0]) > new Date();

            if (data.Subscriptions[subscription].CancellationDate == null && NotExpired && data.Licenses.StripeCreditCardId != "") {

                $("#account_subscription_check-" + subname).prop('checked', true);
                $("#practice-category_" + subname).parents('tr').removeClass("trial");

                $(tr).find('.status').html("Active");

                if (subname == "aud") {
                    AUDEnabled = true;
                }

            }

                // It hasn't expired but it's not active
            else if (NotExpired) {
                $("#account_subscription_check-" + subname).prop('checked', false);
                $("#practice-category_" + subname).parents('tr').removeClass("trial");
                $(tr).find('.status').html("Expires On");


                if (subname == "aud") {
                    AUDEnabled = true;
                }

            }

            else {
                $("#account_subscription_check-" + subname).prop('checked', false);
                $(tr).find('.status').html("Expired");
                $(tr).find('.date').html("-");
            }
        }
    }



    // setup the default selected section when studying (AUD)
    if (!AUDEnabled) {
        $("#practice-question-count").append("<option id='practice-question-trial-amount' value='25'>25</option>");
        $("#practice-question-count").val(25);
        $("#practice-question-count").attr("disabled", "disabled");
        $("#practice-category_aud").parents("tr").first().find(".subscribe-message").css('display', 'block');
    }
    else {
        $("#practice-question-count").val(20);
        $("#practice-question-count").removeAttr("disabled");
    }

    // setup the credit card information if it's available

    if (data.Licenses.StripeCreditCardId != "") {
        $("#subscription-credit-card-last-four").html("****-****-****-" + data.Licenses.CC_LastFour);
        $("#credit-card-on-file").show();
    }
    else {
        $("#credit-card-on-file").hide();
    }


    if ($.COR.account.promotionCode != null) {
        // setup the promotion if exists
        var promotionType = $.COR.account.promotionCode.Type; // 'percent-off';
        var promotionAmount = $.COR.account.promotionCode.Amount; // .75;

        switch (promotionType) {
            case "Percent Off":

                $("#subscription-options .amount").each(function () {
                    var base = $.COR.baseAmount;
                    var promoAmount = base - base * (promotionAmount / 100);
                    $(this).html("$" + promoAmount);
                });

                $("#subscription-promotion-coupon .promotion-amount").html(promotionAmount).parent().show();

                // Show Promotion Banner If Subscription is not active
                if ($.COR.account.subscriptions.length == 0) {
                    $("#promotion-holder .promotion-amount").html(promotionAmount);

                    $("#promotion-coupon").show();
                    $("#promotion-holder").slideDown();
                }

                break;

            default:
                $("#subscription-promotion-coupon").hide();
        }
    }


}

$.COR.account.showDefaultPage = function () {

    $.COR.pageSwap($.COR.getCurrentDisplayedId(), "js-content-wrapper-study");

    $("#header-navigation li").removeClass('current');
    $("#header-navigation_study").addClass('current');
}

$.COR.account.initReviewGrid = function (data) {

    if (this.offline == true) {
        data = $.parseJSON(offlineQuestionHistoryData);
    }


    var quesitonHistoryData = data.QuestionHistoryReturns;

    // need to massage the data a bit to get it into the right format for now... 
    var kendoData = [];

    for (var i = 0; i < quesitonHistoryData.length; i++) {

        var historyData = quesitonHistoryData[i];

        if (historyData.Metrics[0].IsActive == '1') {

            kendoData.push({
                QuestionId: historyData.Metrics[0].QuestionId,
                Section: historyData.Metrics[0].SectionType,
                Correct: historyData.Metrics[0].TimesCorrect,
                Incorrect: historyData.Metrics[0].TimesIncorrect,
                Answered: historyData.Metrics[0].TimesAnswered,
                AvgTimeSpent: historyData.Metrics[0].AverageTimePerQuestion

            });
        }

    }





    $("#kg-review-table").kendoGrid({
        dataSource: {
            data: kendoData,
            schema: {
                model: {
                    fields: {
                        Section: { type: "string" },
                        Correct: { type: "number" },
                        Incorrect: { type: "number" },
                        AvgTimeSpent: { type: "number" },
                    }
                }
            },
            pageSize: 10
        },
        change: function () {

            var selectedCells = this.select();

            // pass question id
            $.COR.account.ShowQuestionHistory($($(selectedCells).find("td")[0]).html());

        },
        selectable:"row",
        sortable: true,
        pageable: {
            refresh: true,
            pageSizes: true,
            buttonCount: 5
        },
        columns: [
            {
                field: "QuestionId",
                title: "Question Id",
                width: 50
            },
            {
                field: "Section",
                title: "Section",
                width: 140
            }, {
                field: "Correct",
                title: "Correct",
                width: 50
            }, {
                field: "Incorrect",
                title: "Incorrect",
                width: 50
            },
            {
                field: "Answered",
                title: "Times Answered",
                width: 100
            },
            {
                field: "AvgTimeSpent",
                title: "Avg. Time Spent",
                width: 110
            }]
    });

}

// Subscription Helpers

$.COR.account.getSubscriptionsForServer = function () {
    return JSON.stringify({
        "AUD": $("#account_subscription_check-aud").prop('checked') ? 1 : 0,
        "FAR": $("#account_subscription_check-far").prop('checked') ? 1 : 0,
        "BEC": $("#account_subscription_check-bec").prop('checked') ? 1 : 0,
        "REG": $("#account_subscription_check-reg").prop('checked') ? 1 : 0
    });
}

$.COR.account.getSubscriptionTotal = function () {

    var subscriptionAmount = 0;
    $('#subscription-options .amount').each(function (index, element) {
        if ($(element).parents('tr').find('.squaredTwo input').is(':checked')) {
            subscriptionAmount += parseInt($(element).html().replace("$", ""));
        }
    });

    return subscriptionAmount;

}

// Question History Helpers

$.COR.account.BuildQuestionHistory = function (QuestionResponse) {




    var len = QuestionResponse.length;

    for (var i = 0; i < len; i++) {

        var response = QuestionResponse[i];


        // Build Question Answers
        var questionAnswers = "";
        for (var j = 0; j < response.QuestionResponse[0].Answers.length; j++) {
            var correctClass = j == response.QuestionResponse[0].CorrectAnswerIndex ? "class='correct'" : "";

            questionAnswers += "<li " + correctClass + " >" + response.QuestionResponse[0].Answers[j].DisplayText + "</li>";
        }

        // Build Question History

        var questionHistory = "";

        for (var j = 0; j < response.Summary.length; j++) {
            var summary = response.Summary[j];

            // Figure out the index of the answer
            var questionIndex = "-"; // default set for skipped;
            if (summary.QuestionsToAnswersId != 0) {
                for (var k = 0; k < response.QuestionResponse[0].Answers.length; k++) {
                    if (response.QuestionResponse[0].Answers[k].QuestionToAnswersId == summary.QuestionsToAnswersId) {
                        questionIndex = k;
                        break;
                    }
                }
            }


            questionHistory += "<tr>" +
                                            //"<td>" + questionIndex + "</td>"+
                                            "<td>" + summary.Correct + "</td>" +
                                            "<td>" + summary.TimeSpentOnQuestion + "</td>" +
                                            "<td>" + summary.SimulationMode + "</td>" +
                                            "<td>" + summary.SimultationDate + "</td>" +
                                        "</tr>";
        }


        var questionRow =
            "<tr>" +
                "<td>" + response.Metrics[0].QuestionId + "</td>" +
                "<td>" + response.Metrics[0].SectionType + "</td>" +
                "<td>" + response.Metrics[0].TimesCorrect + "</td>" +
                "<td>" + response.Metrics[0].TimesIncorrect + "</td>" +
                "<td>" + response.Metrics[0].AverageTimePerQuestion + " s</td>" +
                "<td>" + response.Metrics[0].IsActive + "</td>" +
                "<td><span class='link more-info'>More</span></td>" +
            "</tr>" +
            "<tr class='my-info-question-data-row'>" +
                    "<td colspan ='7'>" +
                        "<div class='my-info-question-data'>" +
                            "<div class='my-info-question-holder'>" +
                                "<div class='header bold'>Question</div>" +
                                "<div class='my-info-question-text'>" + response.QuestionResponse[0].Question + "</div>" +
                                "<ol class='my-info-question-answers'>" + questionAnswers + "</ol>" +
                                "<p class='my-info-explanation-text'>" +
                                    "<span class='bold'>Explanation:</span><br /><span>" + response.QuestionResponse[0].Explanation + "</span>" +
                                "</p>" +
                            "</div>" +
                            "<div class='my-info-question-history'>" +
                                "<div class='my-info-question-history-header'>Your Summary</div>" +
                                "<table>" +
                                    "<thead>" +
                                        "<tr>" +
                                            //"<td>Selected Answer</td>" +
                                            "<td>Correct</td>" +
                                            "<td>Seconds Taken</td>" +
                                            "<td>Mode</td>" +
                                            "<td>Date</td>" +
                                        "</tr>" +
                                    "</thead>" +
                                    "<tbody>" + questionHistory + "</tbody>" +
                                "</table>" +
                            "</div>" +
                        "</div>" +
                    "</td>" +
                "</tr> ";

        $("#review-results > tbody").append(questionRow);

    }

    $("#review-results .more-info").on("click", function () {

        if ($(this).html() == "More") {
            $(this).parents('tr').next().find('.my-info-question-data').slideDown();
            $(this).html('Less');
        } else {
            $(this).parents('tr').next().find('.my-info-question-data').slideUp();
            $(this).html('More');
        }
    });

}

$.COR.account.ShowQuestionHistory = function (QuestionId) {


    var data = $.parseJSON(offlineQuestionHistoryData); // get this from cached object when logging in or something... 
    var quesitonHistoryData = data.QuestionHistoryReturns;


    var options = {
        answers: "",
        question: null,
        explanation: null,
        history: ""
    };



    // we gotta go through the cache find the quesiton id and build everything

    var foundQuestion = null;

    for (var i = 0; i < quesitonHistoryData.length; i++) {

        var historyData = quesitonHistoryData[i];

        if (historyData.Metrics[0].QuestionId == QuestionId) {

            foundQuestion = historyData;
            break;
        }
    }


    options.question = foundQuestion.QuestionResponse[0].Question;
    options.explanation = foundQuestion.QuestionResponse[0].Explanation;

    for (var j = 0; j < foundQuestion.QuestionResponse[0].Answers.length; j++) {
        var correctClass = j == foundQuestion.QuestionResponse[0].CorrectAnswerIndex ? "class='correct'" : "";

        options.answers += "<li " + correctClass + " >" + historyData.QuestionResponse[0].Answers[j].DisplayText + "</li>";
    }

    for (var j = 0; j < foundQuestion.Summary.length; j++) {
        var summary = foundQuestion.Summary[j];

        // Figure out the index of the answer
        var questionIndex = "-"; // default set for skipped;
        if (foundQuestion.QuestionsToAnswersId != 0) {
            for (var k = 0; k < foundQuestion.QuestionResponse[0].Answers.length; k++) {
                if (foundQuestion.QuestionResponse[0].Answers[k].QuestionToAnswersId == summary.QuestionsToAnswersId) {
                    questionIndex = k;
                    break;
                }
            }
        }


        options.history += "<tr>" +
                                        //"<td>" + questionIndex + "</td>"+
                                        "<td>" + summary.Correct + "</td>" +
                                        "<td>" + summary.TimeSpentOnQuestion + " s</td>" +
                                        "<td>" + summary.SimulationMode + "</td>" +
                                        "<td>" + summary.SimultationDate + "</td>" +
                                    "</tr>";
    }



    $.COR.Utilities.FullScreenOverlay.loadLocal("js-overlay-quesiton-history", "medium", false, null, function () {

        $("#full-screen-container .my-info-question-text").html(options.question);
        $("#full-screen-container .my-info-question-answers").html(options.answers);
        $("#full-screen-container .my-info-explanation").html(options.explanation);
        $("#full-screen-container .my-info-history").html(options.history);

        $("#full-screen-container .my-info-question-data").css('visibility','visible');

    });
}

// Question Study Helpers

$.COR.account.startStudy = function () {
    var self = this;

    if ($.COR.account.user == null) {
        $.COR.Utilities.refreshLogin();
        return;
    }


    this.simulator.live = true;
    this.simulator.completed = false;
    this.simulator.questions = null;
    this.simulator.questionIndex = null;

    this.simulator.options.category = $("[name=practice-category]:checked").val();
    this.simulator.options.questionCount = $("#practice-question-count").val();
    this.simulator.options.mode = $("[name=practice-mode]:checked").val();
    this.simulator.options.strategy = $("[name=practice-strategy]:checked").val();

    $.COR.TPrep.showFullScreenOverlay(
        $("#js-overlay-content-loading-questions").html(),
        $("#js-overlay-content-loading-questions").attr("contentSize"),
        function () {
            // Show Explanation for Mode
            if (self.simulator.options.mode == "study") {
                $("#study-question-viewer-study-directions").show();
                $("#study-question-viewer-test-directions").hide();
            }
            else {
                $("#study-question-viewer-study-directions").hide();
                $("#study-question-viewer-test-directions").show();
            }


            self.simulator.questionIndex = 0;

            if (self.offline == true) {


                setTimeout(function () {

                    self.simulator.questions = self.getOfflineQuestions();

                    if ($("#full-screen-container .load-questions").is(":visible")) {
                        setTimeout(function () {
                            self.showSimulator();
                        }, 1000);
                    }

                }, 5000);
            }
            else {

                var ph = new $.COR.Utilities.PostHandler({
                    service: "question", call: "getQuestionsAndAnswers",
                    params: {
                        SectionTypeId: $("input:radio[name=practice-category]:checked").val(),
                        QuestionAmount: $("#practice-question-count").val()
                    },
                    success: function (data) {



                        if (data.QuestionResponses != null) {

                            self.simulator.questions = [{
                                type: "direction",
                                index: 0
                            }];
                            self.simulator.questions = self.simulator.questions.concat(data.QuestionResponses);

                            if ($("#full-screen-container .load-questions").is(":visible")) {
                                setTimeout(function () {
                                    self.showSimulator();
                                }, 1000);
                            }

                        }
                        else {
                            alert('Server Error, please refresh the page and try again');
                        }

                    }
                });

                ph.submitPost();

            }



            $(".start-study").on('click', function () {

                if (self.simulator.questions == null) {
                    $("#full-screen-container .load-questions-explanation-wrapper").hide();
                    $("#full-screen-container .load-questions").show();
                }
                else {
                    self.showSimulator();
                }

            });

            $(".exit-study").on('click', function () {
                $.COR.Utilities.hideFullScreenOverlay();
            });
        }
    );



};

$.COR.account.showSimulator = function () {

    var self = this;

    $.COR.TPrep.showFullScreenOverlay(
        $("#js-overlay-content-study-questions").html(),
        $("#js-overlay-content-loading-questions").attr("contentSize"),
        function () {
            self.initQuestions();
        }
    );

}

$.COR.account.initQuestions = function () {

    var self = this;

    // Should be renamed to setup question, as it sets some defaults as well
    self.setupQuestionFooterNavigation(self.simulator.questions);

    // Local cache for events
    var questions = self.simulator.questions;

    $("#full-screen-container .footer-nav .prev").on('click', function () {
        self.simulator.questionIndex--;



        self.displayStudyQuestion(questions[self.simulator.questionIndex]);


    });

    $("#full-screen-container .footer-nav .next").on('click', function () {
        self.simulator.questionIndex++;

        // we already finished - just show results
        if (self.simulator.questionIndex >= questions.length && self.simulator.completed == true) {

            // Set Results footer navigation to active
            $("#full-screen-container .footer-questions-quicklink-holder .question-quicklink").removeClass('active');

            // Show results navigation
            $("#full-screen-container .footer-questions-quicklink-holder .results").addClass('active')

            $("#full-screen-container .footer-nav .next").addClass('hidden');

            $("#study-question-viewer-question-mc").fadeOut(function () {
                $("#study-question-viewer-results").fadeIn();
            });

        }
        else if (self.simulator.questionIndex == questions.length && self.simulator.completed == false) {
            // Were done show completed screen
            //var result = ("Are you sure you're done?");

            //if test confirm done
            if (self.simulator.options.mode == 'test') {

                var result = confirm("Are you sure you're done?");

                if (result) {
                    self.completeTest();
                }

            }
            else {

                var completed = true;


                var len = self.simulator.questions.length;

                // skip the first guy which is the placeholder for the description 
                for (var i = 1; i < len; i++) {

                    if (self.simulator.questions[i].selectedAnswer == null) {
                        completed = false;
                        break;
                    }
                }

                var showReview = completed ? true : confirm("Not all questions answerd, are you sure you want to continue?");

                if (showReview) {
                    // show completion screen
                    self.completeTest();
                }

            }
        }
        else {

            self.displayStudyQuestion(questions[self.simulator.questionIndex]);
        }

    });

    $("#full-screen-container .footer-questions-quicklink-holder .question-quicklink").on('click', function () {

        if ($(this).hasClass("active")) return;

        var index = $(this).attr("index");


        if ($(this).hasClass("results")) {

            $("#study-question-viewer-question-mc").fadeOut(function () {
                $("#study-question-viewer-results").fadeIn();
            });
        }
        else {
            self.displayStudyQuestion(questions[index]);
        }

        self.simulator.questionIndex = index;
    });

    $("#full-screen-container .footer-questions-quicklink-holder .flag").on('click', function (e) {

        $(this).toggleClass("set");

        e.stopPropagation();

    });

    $("#full-screen-container .study-question-viewer-exit").on('click', function () {
        self.exitSimulator();
    });

    $("#full-screen-container .exit-simulator-view-results").on('click', function () {
        $("#study-question-viewer-exit-uncompleted").hide();
        $("#study-question-viewer-footer").show();
        self.completeTest();
    });

    $("#full-screen-container .exit-simulator-exit").on('click', function () {
        self.simulator.live = false;
        $("#full-screen-container #study-questions-viewer-wrapper").unbind().remove();
        $.COR.TPrep.hideFullScreenOverlay();
        location.hash = "account/study";
    });

    $("#full-screen-container .exit-simulator-go-back").on('click', function () {

        $("#study-question-viewer-exit-uncompleted").hide();
        $("#study-question-viewer-footer").show();

        if (self.simulator.questionIndex == 0) {
            $("#study-question-viewer-directions").fadeIn();
        }
        else {
            $("#study-question-viewer-question-mc").fadeIn();
        }
    });

    // Make sure visible
    $("#full-screen-container #study-question-viewer-directions").show();
    $("#full-screen-container #study-question-viewer-footer").show();

}

// Builds the dynamic footer UI
$.COR.account.setupQuestionFooterNavigation = function (questions) {

    $("#full-screen-container .footer-questions-quicklink-holder div").each(function (index, element) {
        if ($(element).hasClass('directions')) return;
        $(element).remove();
    });

    var len = questions.length;

    for (var i = 0; i < len; i++) {

        if (questions[i].Question != undefined) {

            var html = "<div class='question-quicklink' index='" + i + "'>" + i + " <div class='flag'></div></div>";
            $("#full-screen-container .footer-questions-quicklink-holder").append(html);

            questions[i].index = i;
            questions[i].type = 'question';
            questions[i].timeTaken = 0;
            questions[i].selectedAnswer = -1;
        }
    }

    // Insert Results quicklink
    $("#full-screen-container .footer-questions-quicklink-holder").append("<div index='" + (i++) + "' class='results question-quicklink hidden'>Results</div>");


}

// Shows the selected Question UI
$.COR.account.displayStudyQuestion = function (question) {

    var self = this;


    if ($("#full-screen-container  #study-question-viewer-results").is(":visible")) {
        $("#full-screen-container  #study-question-viewer-results").hide();
    }

    if (question.type == "direction") {
        $("#study-question-viewer-question-mc").fadeOut(function () {
            $("#study-question-viewer-directions").fadeIn();
        });

    }
    else {

        if ($("#study-question-viewer-directions").is(":visible")) {
            $("#study-question-viewer-directions").fadeOut(function () {
                self.setStudyQuestionData(question);
                $("#study-question-viewer-question-mc").fadeIn();
            });
        }
        else {
            $("#study-question-viewer-question-mc").fadeOut(function () {
                self.setStudyQuestionData(question);
                $("#study-question-viewer-question-mc").fadeIn();
            });
        }

    }

    // Update Footer UI Navigation

    // Set the selected question as highlighted
    $("#full-screen-container .footer-questions-quicklink-holder .question-quicklink").removeClass("active");
    $(".question-quicklink[index=" + question.index + "]").addClass("active");

    // Show/Hide The Previous Button
    if (this.simulator.questionIndex == 0) {
        $("#study-question-viewer-page .footer-nav .prev").addClass('hidden')
    }
    else {
        if (!$("#full-screen-container #study-question-viewer-page .footer-nav .prev").is(":visible")) {
            $("#full-screen-container #study-question-viewer-page .footer-nav .prev").removeClass('hidden');
        }
    }

    // Show/Hide Next Button
    if (this.simulator.completed == true && $("#full-screen-container .footer-questions-quicklink-holder .results").hasClass('active')) {
        $("#full-screen-container #study-question-viewer-page .footer-nav .next").addClass('hidden');
    }
    else {
        if (!$("#full-screen-container #study-question-viewer-page .footer-nav .next").is(":visible")) {
            $("#full-screen-container #study-question-viewer-page .footer-nav .next").removeClass('hidden');
        }
    }
}

// Sets up the data in the selected Question UI
$.COR.account.setStudyQuestionData = function (question) {

    var self = this;

    if (question.selectedAnswer == -1) {
        question.selectedAnswer = 0;
    }

    // Stop Timer for previous question
    clearInterval(self.simulator.questionTimerIntervalId);


    $("#full-screen-container .answer-explanation").hide();

    $("#full-screen-container .question-content").html(question.Question);
    $("#full-screen-container .answer-explanation-holder").html(question.Explanation);

    $("#full-screen-container .answer-options table").html("");

    for (var i = 0; i < question.Answers.length; i++) {

        var html = "<tr><td class='result-spacer'></td><td><input type='radio' id='study-question-answer-" + i + "' value='" + question.Answers[i].QuestionToAnswersId + "' name='study-question-answer' /></td>";
        html += "<td><label for='study-question-answer-" + i + "'>" + question.Answers[i].DisplayText + "</label></td></tr>";
        $("#full-screen-container .answer-options table").append(html);
    }

    // Question Events

    $("#full-screen-container .answer-options input").on('change', function () {
        self.selectAnswer(question, this);
    });


    if (question.selectedAnswer == 0) {

        // Start Question Timer
        self.simulator.questionTimerIntervalId = setInterval(function () {
            question.timeTaken += 1;
        }, 1000);

    }


    // Check if Study Mode and Question Answered - Disable Question

    if ((question.selectedAnswer != 0 && self.simulator.options.mode == 'study') || self.simulator.completed == true) {

        var SelectedAnswerIndex;
        for (var i = 0; i < question.Answers.length; i++) {
            if (question.selectedAnswer == question.Answers[i].QuestionToAnswersId) {
                SelectedAnswerIndex = i;
            }
        }


        // Set Checked Index
        $($($('#full-screen-container .answer-options table tr')[SelectedAnswerIndex]).find('input')).attr('checked', 'checked');

        // Set Questions Disabled
        $("#full-screen-container .answer-options table input").attr('disabled', 'disabled');

        // Indicate Correct Answer
        $($($('#full-screen-container .answer-options table tr')[question.CorrectAnswerIndex]).children()[0]).addClass('correct');

        // Show Explanation
        $("#full-screen-container .answer-explanation").show();
    }

    if ($.COR.account.user.IsAdmin == "1") {
        $("#full-screen-container .question-meta-data").show();
        $("#study-question-meta-question-id").html(question.QuestionId);
        $("#study-question-meta-client-id").html(question.QuestionClientId);
    }

}

// Handles selecting an answer
$.COR.account.selectAnswer = function (question, selectedInput) {

    // get answer
    var selectedAnswer = $(selectedInput).val();

    // set question answered
    question.selectedAnswer = parseInt(selectedAnswer);

    // update footer UI to indicate question was answered
    $(".question-quicklink[index=" + question.index + "]").addClass("answered");

    if (this.simulator.options.mode == "study") {

        // Indicate Correct Answer
        $($($(selectedInput).parents('tbody').children('tr')[question.CorrectAnswerIndex]).children()[0]).addClass('correct');

        // Show Explanation
        $("#full-screen-container .answer-explanation").show();

        // Disable Inputs
        $("#full-screen-container .answer-options table input").attr('disabled', 'disabled');
    }

}

// Transition from questions to review page

$.COR.account.completeTest = function () {

    var self = this;

    // Stop last questions timer
    clearInterval(self.simulator.questionTimerIntervalId);

    $("#study-question-viewer-question-mc").fadeOut(function () {
        $("#study-question-viewer-results").fadeIn(function () {

            // Set Results footer navigation to active
            $("#full-screen-container .footer-questions-quicklink-holder .question-quicklink").removeClass('active');

            // Show results navigation
            $("#full-screen-container .footer-questions-quicklink-holder .results").addClass('active').show();

            // Hide the next button
            $("#full-screen-container #study-question-viewer-page .footer-nav .next").addClass("hidden");

            // Make sure previous button is showing
            $("#full-screen-container #study-question-viewer-page .footer-nav .prev").removeClass("hidden");

            // Set simulation to completed
            self.simulator.completed = true;

            var correct = 0;
            var incorrect = 0;
            var skipped = 0;

            // loop through questions

            var questions = self.simulator.questions;

            var postQuestions = [];

            // skip first question
            for (var i = 1; i < questions.length; i++) {

                var question = questions[i];

                // Required Format
                //{
                //    "questionId": "3",
                //    "accountUserId": "3",
                //    "timeTaken": 13,
                //    "mode": "1",
                //    "selectedAnswer": "12",
                //    "answeredCorrectly": "0"
                //},

                var selectedAnswer = question.selectedAnswer; //question.selectedAnswer !== undefined ? question.Answers[question.selectedAnswer].QuestionToAnswersId : "0";
                var answeredCorrectly = 0;
                if (selectedAnswer !== 0 && selectedAnswer !== -1) {

                    answeredCorrectly = question.Answers[question.CorrectAnswerIndex].QuestionToAnswersId == question.selectedAnswer ? 1 : 0;
                }

                postQuestions.push({
                    questionId: question.Answers[0].QuestionId,
                    accountUserId: $.COR.account.user.AccountUserId,
                    timeTaken: question.timeTaken,
                    mode: self.simulator.options.mode == 'study' ? 1 : 2,
                    selectedAnswer: selectedAnswer,
                    answeredCorrectly: answeredCorrectly
                });


                if (question.selectedAnswer == 0) {
                    if (self.simulator.options.mode == 'study') {
                        skipped++;
                    }
                    else {
                        incorrect++;
                        $("#full-screen-container .footer-questions-quicklink-holder [index=" + question.index + "]").addClass("incorrect");
                    }
                }
                else {
                    // make all the wrong answers red in the navigation
                    if (answeredCorrectly == 0) {
                        incorrect++;
                        $("#full-screen-container .footer-questions-quicklink-holder [index=" + question.index + "]").addClass("incorrect");
                    }
                    else {
                        correct++
                    }
                }
            }

            $("#full-screen-container .results-num-questions").html(self.simulator.questions.length - 1);
            $("#full-screen-container .results-num-correct").html(correct);
            $("#full-screen-container .results-num-incorrect").html(incorrect);
            $("#full-screen-container .results-num-skipped").html(skipped);


            //Save the questions

            var ph = new $.COR.Utilities.PostHandler({
                service: "question", call: "saveQuestionHistory",
                params: {
                    QuestionHistory: JSON.stringify(postQuestions)
                },
                success: function (data) {

                    console.log("Saved Questions");

                }
            });

            ph.submitPost();

        });
    });
}

// Exit simulator
$.COR.account.exitSimulator = function () {


    if ($("#full-screen-container .study-quesitons-content:visible").attr("id") != "study-question-viewer-results") {
        $(".study-quesitons-content").hide();
        $("#study-question-viewer-footer").hide();
        $("#study-question-viewer-exit-uncompleted").fadeIn();
    }
    else {
        this.simulator.live = false;
        $("#full-screen-container #study-questions-viewer-wrapper").unbind().remove();
        $.COR.TPrep.hideFullScreenOverlay();
        location.hash = "account/study";
    }
}



// Gets a set of questions for offline testing
$.COR.account.getOfflineQuestions = function () {

    return offlineObjects = [
        {
            type: "direction",
            index: 0
        },
        {
            Question: "Question 1",
            Answers: [
                    "Answer 1",
                    "Answer 2",
                    "Answer 3",
                    "Answer 4"
            ],
            answerIndex: 1,
            Explanation: "Answer Explanation"
        },
        {
            Question: "Question 2",
            Answers: [
                    "Answer 1",
                    "Answer 2",
                    "Answer 3",
                    "Answer 4"
            ],
            answerIndex: 1,
            Explanation: "Answer Explanation"
        },
        {
            Question: "Question 3",
            Answers: [
                    "Answer 1",
                    "Answer 2",
                    "Answer 3",
                    "Answer 4"
            ],
            answerIndex: 1,
            Explanation: "Answer Explanation"
        }
    ];


}




var offlineQuestionHistoryData = '{ "Result": 1, "Reason": "", "QuestionHistoryReturns": [{ "Metrics": [{ "QuestionId": "1", "SectionType": "AUD", "TimesCorrect": "1", "TimesIncorrect": "3", "TimesAnswered": "4", "AverageTimePerQuestion": "12", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD1S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>A  service which cannot be performed for a nonissuer attest client is&nbsp;<\/p>", "Answers": [{ "QuestionToAnswersId": "1", "QuestionId": "1", "DisplayText": "Signing of Payroll Checks", "IsAnswerToQuestion": "1" }, { "QuestionToAnswersId": "2", "QuestionId": "1", "DisplayText": "Recording management approved transactions", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "3", "QuestionId": "1", "DisplayText": "Performing data processing services", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "4", "QuestionId": "1", "DisplayText": "Preparation of a balance sheet", "IsAnswerToQuestion": "0" }], "CorrectAnswerIndex": 0, "Explanation": "Management functions impair the independence of CPA\'s. &nbsp;Signing payroll checks is a management function.", "QuestionId": "1", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "1", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>A  service which cannot be performed for a nonissuer attest client is&nbsp;<\/p>", "QuestionsToAnswersId": "1", "Correct": "Yes", "Skipped": "No", "TimeSpentOnQuestion": "19", "SimultationDate": "2013-07-04 09:07:28" }, { "QuestionId": "1", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>A  service which cannot be performed for a nonissuer attest client is&nbsp;<\/p>", "QuestionsToAnswersId": "2", "Correct": "No", "Skipped": "No", "TimeSpentOnQuestion": "25", "SimultationDate": "2013-07-04 09:07:28" }, { "QuestionId": "1", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>A  service which cannot be performed for a nonissuer attest client is&nbsp;<\/p>", "QuestionsToAnswersId": "4", "Correct": "No", "Skipped": "No", "TimeSpentOnQuestion": "2", "SimultationDate": "2013-07-05 04:40:50" }, { "QuestionId": "1", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>A  service which cannot be performed for a nonissuer attest client is&nbsp;<\/p>", "QuestionsToAnswersId": "2", "Correct": "No", "Skipped": "No", "TimeSpentOnQuestion": "2", "SimultationDate": "2013-09-20 00:53:31" }] }, { "Metrics": [{ "QuestionId": "40", "SectionType": "AUD", "TimesCorrect": "1", "TimesIncorrect": "0", "TimesAnswered": "0", "AverageTimePerQuestion": "3", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD16S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>When accountants anticipate third-party reliance on compiled financial statements, which of the following statements is correct?<\/p>", "Answers": [{ "QuestionToAnswersId": "157", "QuestionId": "40", "DisplayText": "Each page of the financial statements should have a restriction such as Restricted for Management Use Only", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "158", "QuestionId": "40", "DisplayText": "An opinion on fairness of financial statement presentations is required.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "159", "QuestionId": "40", "DisplayText": "A compilation report must be issued.", "IsAnswerToQuestion": "1" }, { "QuestionToAnswersId": "160", "QuestionId": "40", "DisplayText": "Omission of note disclosures is unacceptable.", "IsAnswerToQuestion": "0" }], "CorrectAnswerIndex": 2, "Explanation": "<p>Accountants must issue a compilation report when they anticipate third-party reliance. <\/p>", "QuestionId": "40", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "40", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>When accountants anticipate third-party reliance on compiled financial statements, which of the following statements is correct?<\/p>", "QuestionsToAnswersId": "159", "Correct": "Yes", "Skipped": "No", "TimeSpentOnQuestion": "3", "SimultationDate": "2013-09-20 00:53:31" }] }, { "Metrics": [{ "QuestionId": "34", "SectionType": "AUD", "TimesCorrect": "1", "TimesIncorrect": "0", "TimesAnswered": "0", "AverageTimePerQuestion": "2", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD10S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>An auditor investigates a manufacturing entity to determine whether slow-moving, defective and obsolete items included in inventory are identified properly. Which procedure is the auditor least likely to perform?<\/p>", "Answers": [{ "QuestionToAnswersId": "133", "QuestionId": "34", "DisplayText": "Compare inventory balances to anticipated sales volumes.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "134", "QuestionId": "34", "DisplayText": "Test the calculations of standard overhead rates.", "IsAnswerToQuestion": "1" }, { "QuestionToAnswersId": "135", "QuestionId": "34", "DisplayText": "Tour the manufacturing plant or production facility.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "136", "QuestionId": "34", "DisplayText": "Review inventory experience and trends.", "IsAnswerToQuestion": "0" }], "CorrectAnswerIndex": 1, "Explanation": "<p>Testing standard overhead rates might provide only limited help. These rates might be used to arrive at the cost of an item. But, the approach doesn\'t help determine if an item is obsolete<\/p>", "QuestionId": "34", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "34", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>An auditor investigates a manufacturing entity to determine whether slow-moving, defective and obsolete items included in inventory are identified properly. Which procedure is the auditor least likely to perform?<\/p>", "QuestionsToAnswersId": "134", "Correct": "Yes", "Skipped": "No", "TimeSpentOnQuestion": "2", "SimultationDate": "2013-09-20 00:53:31" }] }, { "Metrics": [{ "QuestionId": "32", "SectionType": "AUD", "TimesCorrect": "1", "TimesIncorrect": "0", "TimesAnswered": "0", "AverageTimePerQuestion": "3", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD8S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>Which of the following approaches is the right way for an auditor to test internal controls of a computerized accounting system?<\/p>", "Answers": [{ "QuestionToAnswersId": "125", "QuestionId": "32", "DisplayText": "Auditors don\'t need to test the data. All compliance-related conditions are contained in data test programs.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "126", "QuestionId": "32", "DisplayText": "Auditors don\'t need to create customized tests for each client\'s computer application.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "127", "QuestionId": "32", "DisplayText": "Process data with the client\'s computer and compare the results with the auditor\'s pre-determined results.", "IsAnswerToQuestion": "1" }, { "QuestionToAnswersId": "128", "QuestionId": "32", "DisplayText": "Code data to a dummy subsidiary, so that data can be extracted from the system under actual operating conditions.", "IsAnswerToQuestion": "0" }], "CorrectAnswerIndex": 2, "Explanation": "<p>In this approach the internal controls are tested by using various types of data on the client\'s computer. <\/p>", "QuestionId": "32", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "32", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>Which of the following approaches is the right way for an auditor to test internal controls of a computerized accounting system?<\/p>", "QuestionsToAnswersId": "127", "Correct": "Yes", "Skipped": "No", "TimeSpentOnQuestion": "3", "SimultationDate": "2013-09-20 00:53:31" }] }, { "Metrics": [{ "QuestionId": "38", "SectionType": "AUD", "TimesCorrect": "1", "TimesIncorrect": "0", "TimesAnswered": "0", "AverageTimePerQuestion": "2", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD14S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>According to Statements on Standards for Accounting and Review Service (SSARS), a review engagement is performed on a non- publicly owned company\'s financial statements. Which of the following statement is correct in this scenario?<\/p>", "Answers": [{ "QuestionToAnswersId": "149", "QuestionId": "38", "DisplayText": "Accountants must establish an understanding with their client in an engagement letter.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "150", "QuestionId": "38", "DisplayText": "While performing a review, accountants must obtain an understanding of the client\'s internal controls.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "151", "QuestionId": "38", "DisplayText": "A review provides accountants with a basis for expressing limited assurance on the financial statements.", "IsAnswerToQuestion": "1" }, { "QuestionToAnswersId": "152", "QuestionId": "38", "DisplayText": "A review report contains accountants\' overall opinions of the financial statements.", "IsAnswerToQuestion": "0" }], "CorrectAnswerIndex": 2, "Explanation": "<p>SSARS indicates that the objective of a review is to provide a reasonable basis for expressing limited assurance. That is, no material modifications have been made to financial statements to make them conform with generally accepted accounting principles.<\/p>", "QuestionId": "38", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "38", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>According to Statements on Standards for Accounting and Review Service (SSARS), a review engagement is performed on a non- publicly owned company\'s financial statements. Which of the following statement is correct in this scenario?<\/p>", "QuestionsToAnswersId": "151", "Correct": "Yes", "Skipped": "No", "TimeSpentOnQuestion": "2", "SimultationDate": "2013-09-20 00:53:31" }] }, { "Metrics": [{ "QuestionId": "5", "SectionType": "AUD", "TimesCorrect": "1", "TimesIncorrect": "0", "TimesAnswered": "0", "AverageTimePerQuestion": "2", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD5S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>A factor that is most likely to result in a CPA declining an audit engagement is<\/p>", "Answers": [{ "QuestionToAnswersId": "17", "QuestionId": "5", "DisplayText": "The audit engagement takes place an inconvenient distance from the auditors office", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "18", "QuestionId": "5", "DisplayText": "Inquiry of the company\'s legal counsel is disallowed by management.", "IsAnswerToQuestion": "1" }, { "QuestionToAnswersId": "19", "QuestionId": "5", "DisplayText": "The predecessor auditor has an outstanding balance with the company for a previous engagement", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "20", "QuestionId": "5", "DisplayText": "The company\'s internal auditor of 15 years was hired by the company\'s primary competitor", "IsAnswerToQuestion": "0" }], "CorrectAnswerIndex": 1, "Explanation": "<p>Managements lack of permission regarding the inquiry of legal counsel is always considered a scope limitation. &nbsp;Scope limitations will often result in disclaimer and a CPA generally shouldn\'t accept an engagement if the result is likely to be a disclaimer of opinion.<\/p>", "QuestionId": "5", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "5", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>A factor that is most likely to result in a CPA declining an audit engagement is<\/p>", "QuestionsToAnswersId": "18", "Correct": "Yes", "Skipped": "No", "TimeSpentOnQuestion": "2", "SimultationDate": "2013-09-20 00:53:31" }] }, { "Metrics": [{ "QuestionId": "42", "SectionType": "AUD", "TimesCorrect": "1", "TimesIncorrect": "0", "TimesAnswered": "0", "AverageTimePerQuestion": "2", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD18S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>When sales invoices are traced to shipping documents, the invoices provide evidence that:<\/p>", "Answers": [{ "QuestionToAnswersId": "165", "QuestionId": "42", "DisplayText": "Customers were billed for the shipment.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "166", "QuestionId": "42", "DisplayText": "Billed items were shipped.", "IsAnswerToQuestion": "1" }, { "QuestionToAnswersId": "167", "QuestionId": "42", "DisplayText": "Items shipped appear as debits in the subsidiary accounts receivables ledger.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "168", "QuestionId": "42", "DisplayText": "Items shipped to customers were recorded as receivables.", "IsAnswerToQuestion": "0" }], "CorrectAnswerIndex": 1, "Explanation": "<p>Sales invoices will often serve as bills and by tracing them to shipping documents, the auditor will discover whether those bills are supported by shipments.<\/p>", "QuestionId": "42", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "42", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>When sales invoices are traced to shipping documents, the invoices provide evidence that:<\/p>", "QuestionsToAnswersId": "166", "Correct": "Yes", "Skipped": "No", "TimeSpentOnQuestion": "2", "SimultationDate": "2013-09-20 00:53:31" }] }, { "Metrics": [{ "QuestionId": "2", "SectionType": "AUD", "TimesCorrect": "1", "TimesIncorrect": "0", "TimesAnswered": "0", "AverageTimePerQuestion": "8", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD2S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p> A CPA should make inquiries of the predecessor auditor  when he is approached to perform an audit for the first time. This is required because the predecessor may provide the successor with relevant information which  will help the successor to determine<\/p>", "Answers": [{ "QuestionToAnswersId": "5", "QuestionId": "2", "DisplayText": "if the company has been following GAAP in previous years.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "6", "QuestionId": "2", "DisplayText": "if the companies internal control has been materially weak in the past", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "7", "QuestionId": "2", "DisplayText": "whether or not to accept the engagement.", "IsAnswerToQuestion": "1" }, { "QuestionToAnswersId": "8", "QuestionId": "2", "DisplayText": "whether the work of the internal auditors can be relied upon.", "IsAnswerToQuestion": "0" }], "CorrectAnswerIndex": 2, "Explanation": "<p>Communication between predecessor and successor is required to allow the successor more information that will help in making a determination as to whether the engagement should be accepted<\/p>", "QuestionId": "2", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "2", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p> A CPA should make inquiries of the predecessor auditor  when he is approached to perform an audit for the first time. This is required because the predecessor may provide the successor with relevant information which  will help the successor to determine<\/p>", "QuestionsToAnswersId": "1", "Correct": "Yes", "Skipped": "No", "TimeSpentOnQuestion": "8", "SimultationDate": "2013-07-04 09:07:28" }] }, { "Metrics": [{ "QuestionId": "43", "SectionType": "AUD", "TimesCorrect": "0", "TimesIncorrect": "1", "TimesAnswered": "0", "AverageTimePerQuestion": "2", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD19S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>In terms of maintaining good internal controls, which of these pairs of duties are considered incompatible?<\/p>", "Answers": [{ "QuestionToAnswersId": "169", "QuestionId": "43", "DisplayText": "Posting to the general ledger and approving payroll-related additions and terminations", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "170", "QuestionId": "43", "DisplayText": "Maintaining receivable subsidiary ledger accounts and preparing monthly customer statements", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "171", "QuestionId": "43", "DisplayText": "Maintaining expense subsidiary ledgers and having custody of signed but un-mailed checks", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "172", "QuestionId": "43", "DisplayText": "Maintaining accounts receivable records and collecting receipts on account", "IsAnswerToQuestion": "1" }], "CorrectAnswerIndex": 3, "Explanation": "<p>Maintaining accounts receivable records and collecting receipts on account combine custody and recording of assets.<\/p>", "QuestionId": "43", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "43", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>In terms of maintaining good internal controls, which of these pairs of duties are considered incompatible?<\/p>", "QuestionsToAnswersId": "170", "Correct": "No", "Skipped": "No", "TimeSpentOnQuestion": "2", "SimultationDate": "2013-09-20 00:53:31" }] }, { "Metrics": [{ "QuestionId": "41", "SectionType": "AUD", "TimesCorrect": "0", "TimesIncorrect": "1", "TimesAnswered": "0", "AverageTimePerQuestion": "2", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD17S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>Choose the correct statement that applies to CPA records. <\/p>", "Answers": [{ "QuestionToAnswersId": "161", "QuestionId": "41", "DisplayText": "CPAs may withhold the supporting records, These records are not reflected in the client\'s records if fees for the engagement remain unpaid.(e.g., proposed adjustment entries).", "IsAnswerToQuestion": "1" }, { "QuestionToAnswersId": "162", "QuestionId": "41", "DisplayText": "CPAs may retain records prepared by their client until fees due are received (e.g., the general ledger )", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "163", "QuestionId": "41", "DisplayText": "Working papers of CPAs include copies of client records. This information is not available to third parties under any circumstances.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "164", "QuestionId": "41", "DisplayText": "Working papers of CPAs are the joint property of the CPA and the client.", "IsAnswerToQuestion": "0" }], "CorrectAnswerIndex": 0, "Explanation": "<p>CPAs may keep supporting records that they prepare.<\/p>", "QuestionId": "41", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "41", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>Choose the correct statement that applies to CPA records. <\/p>", "QuestionsToAnswersId": "162", "Correct": "No", "Skipped": "No", "TimeSpentOnQuestion": "2", "SimultationDate": "2013-09-20 00:53:31" }] }, { "Metrics": [{ "QuestionId": "39", "SectionType": "AUD", "TimesCorrect": "0", "TimesIncorrect": "1", "TimesAnswered": "0", "AverageTimePerQuestion": "2", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD15S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>A publicly owned company wants to issue financial statements in an audit report. In this scenario, which of the following standards apply?<\/p>", "Answers": [{ "QuestionToAnswersId": "153", "QuestionId": "39", "DisplayText": "Securities and Exchange Commission", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "154", "QuestionId": "39", "DisplayText": "Sarbanes-Oxley", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "155", "QuestionId": "39", "DisplayText": "Public Company Accounting Oversight Board", "IsAnswerToQuestion": "1" }, { "QuestionToAnswersId": "156", "QuestionId": "39", "DisplayText": "Generally accepted auditing standards", "IsAnswerToQuestion": "0" }], "CorrectAnswerIndex": 2, "Explanation": "<p>In the United States, the PCAOB requires that audit reports indicate that audits are performed with PCAOB standards. <\/p>", "QuestionId": "39", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "39", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>A publicly owned company wants to issue financial statements in an audit report. In this scenario, which of the following standards apply?<\/p>", "QuestionsToAnswersId": "154", "Correct": "No", "Skipped": "No", "TimeSpentOnQuestion": "2", "SimultationDate": "2013-09-20 00:53:31" }] }, { "Metrics": [{ "QuestionId": "36", "SectionType": "AUD", "TimesCorrect": "0", "TimesIncorrect": "1", "TimesAnswered": "0", "AverageTimePerQuestion": "2", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD12S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>For a reasonable period of time, an auditor has had substantial doubt about an entity\'s ability to continue as a going concern. Of the following mitigation plans, which would the auditor most likely consider while evaluating the entity\'s plan for dealing with the future?<\/p>", "Answers": [{ "QuestionToAnswersId": "141", "QuestionId": "36", "DisplayText": "Issue stock options to key executives.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "142", "QuestionId": "36", "DisplayText": "Accelerate  expenditures for research and development projects.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "143", "QuestionId": "36", "DisplayText": "Operate at increased levels of production.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "144", "QuestionId": "36", "DisplayText": "Extend the due dates of existing loans.", "IsAnswerToQuestion": "1" }], "CorrectAnswerIndex": 3, "Explanation": "<p>Extending due dates of existing loans might help to reduce a cash shortage<\/p>", "QuestionId": "36", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "36", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>For a reasonable period of time, an auditor has had substantial doubt about an entity\'s ability to continue as a going concern. Of the following mitigation plans, which would the auditor most likely consider while evaluating the entity\'s plan for dealing with the future?<\/p>", "QuestionsToAnswersId": "142", "Correct": "No", "Skipped": "No", "TimeSpentOnQuestion": "2", "SimultationDate": "2013-09-20 00:53:31" }] }, { "Metrics": [{ "QuestionId": "31", "SectionType": "AUD", "TimesCorrect": "0", "TimesIncorrect": "1", "TimesAnswered": "0", "AverageTimePerQuestion": "2", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD7S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>Rehmer Corporation has many customers. A file of customer records is stored on disk. Each customer record contains a name, address, credit limit and account balance.  How can auditors check whether customers have exceed their credit limits?<\/p>", "Answers": [{ "QuestionToAnswersId": "121", "QuestionId": "31", "DisplayText": "Develop a program to compare credit limits with account balances. Then, print out the details of any accounts with a balance exceeding its credit limit.", "IsAnswerToQuestion": "1" }, { "QuestionToAnswersId": "122", "QuestionId": "31", "DisplayText": "Print out a sample set of customer records so that the balances can be checked against the credit limit.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "123", "QuestionId": "31", "DisplayText": "Print out all account balances so that credit balances can be checked manually against the credit limit.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "124", "QuestionId": "31", "DisplayText": "Develop a test that would cause account balances to exceed the credit limit. Then, determine if the system actually detects test situations.", "IsAnswerToQuestion": "0" }], "CorrectAnswerIndex": 0, "Explanation": "<p>Actual account balances are compared with a pre-determined credit limit. The auditor can prepare a report about which balances exceed credit limits. <\/p>", "QuestionId": "31", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "31", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>Rehmer Corporation has many customers. A file of customer records is stored on disk. Each customer record contains a name, address, credit limit and account balance.  How can auditors check whether customers have exceed their credit limits?<\/p>", "QuestionsToAnswersId": "123", "Correct": "No", "Skipped": "No", "TimeSpentOnQuestion": "2", "SimultationDate": "2013-09-20 00:53:31" }] }, { "Metrics": [{ "QuestionId": "4", "SectionType": "AUD", "TimesCorrect": "0", "TimesIncorrect": "1", "TimesAnswered": "0", "AverageTimePerQuestion": "2", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD4S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>Cost accounting systems are tested during an audit to determine that<\/p>", "Answers": [{ "QuestionToAnswersId": "13", "QuestionId": "4", "DisplayText": "matching of physical inventory and book inventory exists", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "14", "QuestionId": "4", "DisplayText": "The calculation of inventory quantities on hand have been completed with due diligence.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "15", "QuestionId": "4", "DisplayText": "GAAP has been followed regarding the implementation of the system.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "16", "QuestionId": "4", "DisplayText": "The correct accounts have been affected by the progression of the product through the various stages involved in the production process.", "IsAnswerToQuestion": "1" }], "CorrectAnswerIndex": 3, "Explanation": "<p>Cost accounting systems are used for the purpose of product valuation. &nbsp;Testing the accuracy of cost accounting systems is done to assure that the costs related to creating a product are assigned correctly to the different accounts that relate to the variety of stages involved in the completion of a salable product.<\/p>", "QuestionId": "4", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "4", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>Cost accounting systems are tested during an audit to determine that<\/p>", "QuestionsToAnswersId": "14", "Correct": "No", "Skipped": "No", "TimeSpentOnQuestion": "2", "SimultationDate": "2013-09-20 00:53:31" }] }, { "Metrics": [{ "QuestionId": "3", "SectionType": "AUD", "TimesCorrect": "0", "TimesIncorrect": "1", "TimesAnswered": "0", "AverageTimePerQuestion": "2", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD3S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>In respect to an auditor\'s responsibility to report fraud, which of the following statements is true?<\/p>", "Answers": [{ "QuestionToAnswersId": "9", "QuestionId": "3", "DisplayText": "The SEC should immediately be informed of all fraud discovered which involves the company\'s senior management.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "10", "QuestionId": "3", "DisplayText": "Ordinarily, regarding the discovery of fraud, the auditor is not required to inform any parties other than the audit committee and management.", "IsAnswerToQuestion": "1" }, { "QuestionToAnswersId": "11", "QuestionId": "3", "DisplayText": "Every instance of fraud discovered by the auditor should be communicated to upper management.", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "12", "QuestionId": "3", "DisplayText": "Both the SEC and principal stockholders should be notified by the auditor when the discovery of fraud is material and involves senior management.", "IsAnswerToQuestion": "0" }], "CorrectAnswerIndex": 1, "Explanation": "<p>The auditor is generally not required to report fraud to anyone except the audit committee and the company\'s management.<\/p>", "QuestionId": "3", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "3", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>In respect to an auditor\'s responsibility to report fraud, which of the following statements is true?<\/p>", "QuestionsToAnswersId": "12", "Correct": "No", "Skipped": "No", "TimeSpentOnQuestion": "2", "SimultationDate": "2013-09-20 00:53:31" }] }, { "Metrics": [{ "QuestionId": "44", "SectionType": "AUD", "TimesCorrect": "0", "TimesIncorrect": "1", "TimesAnswered": "0", "AverageTimePerQuestion": "2", "IsActive": "1" }], "QuestionResponse": [{ "Result": 1, "Reason": "", "QuestionClientId": "AUD20S1", "QuestionTypeId": 1, "QuestionCategoryId": "1", "SectionTypeId": 0, "QuestionClientImage": "", "IsApprovedForUse": 0, "IsActive": 0, "Question": "<p>Use the ratio sampling method to calculate the year-end, accounts payable, audited balance from the following data:<\/p><br><table><tbody><tr><td>Number of Accounts Population:<\/td><td>2050<\/td><\/tr><tr><td>Sample:<\/td><td>100<\/td><\/tr><tr><td>Book Balance<\/td><td>$2,500,000 <\/td><\/tr><tr><td>Audited Balance <\/td><td>$150,000<\/td><\/tr><\/tbody><\/table>", "Answers": [{ "QuestionToAnswersId": "173", "QuestionId": "44", "DisplayText": "$5,250,000", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "174", "QuestionId": "44", "DisplayText": "$2,562,500", "IsAnswerToQuestion": "0" }, { "QuestionToAnswersId": "175", "QuestionId": "44", "DisplayText": "$3,000,000", "IsAnswerToQuestion": "1" }, { "QuestionToAnswersId": "176", "QuestionId": "44", "DisplayText": "$75,000", "IsAnswerToQuestion": "0" }], "CorrectAnswerIndex": 2, "Explanation": "<p>The ratio method estimates the audited value by: 1.Taking the ratio of the audited value over the book value of the sample. 2. Multiplying the ratio value by the population book value. In this case, ($150,000\/$125,000) X $2,500,000 = $ 3,000,000<\/p>", "QuestionId": "44", "IsDeprecated": 0 }], "Summary": [{ "QuestionId": "44", "SimulationMode": "Practice", "TestSection": "AUD", "Question": "<p>Use the ratio sampling method to calculate the year-end, accounts payable, audited balance from the following data:<\/p><br><table><tbody><tr><td>Number of Accounts Population:<\/td><td>2050<\/td><\/tr><tr><td>Sample:<\/td><td>100<\/td><\/tr><tr><td>Book Balance<\/td><td>$2,500,000 <\/td><\/tr><tr><td>Audited Balance <\/td><td>$150,000<\/td><\/tr><\/tbody><\/table>", "QuestionsToAnswersId": "174", "Correct": "No", "Skipped": "No", "TimeSpentOnQuestion": "2", "SimultationDate": "2013-09-20 00:53:31" }] }] }';