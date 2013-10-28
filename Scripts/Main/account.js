
$.COR.account = {
    offline: false,
    user: null,
    hash: null,
    licenses: null,
    stripePublicKey: null,
    subscriptions:null,
    userSettings: null,
    simulator: {
        options: {
            mode:null,
            category:null,
            questionCount:null,
            strategy:null
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
            $("#practice-question-count").val(5);
            $("#practice-question-count").attr("disabled", "disabled");
            $(this).parents("tr").first().find(".subscribe-message").css("display","block");
        }
        else {
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
                { password: oldPassword, newPassword: newPassword},
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

                $("#update-subscription-holder .credit-card-info").hide();


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

                        // show the errors on the form
                        $(".payment-errors").text(response.error.message);
                    } else {

                        // token contains id, last4, and card type
                        var token = response['id'];

                        // Update Credit Card
                        $.COR.services.changeCreditCard({token:token}, function (data) {

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
            $("#account-upodate-no-subscriptions-selected").show();
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

                    if($(this).hasClass('disabled')){return;}
                    $(this).addClass('disabled');



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

                            // show the errors on the form
                            $(".payment-errors").text(response.error.message);
                        } else {

                            // token contains id, last4, and card type
                            var token = response['id'];


                            var successFunction = function () {
                                // Refresh Login
                                $.COR.checkLogin(function (data) {

                                    var subscriptions = self.getSubscriptionsForServer();

                                    $.COR.services.chargeSubscription(subscriptions, function () {

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

                    $.COR.services.chargeSubscription(subscriptions, function () {

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
                self.BuildQuestionHistory(data.QuestionHistoryReturns);

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
        $("#practice-question-count").val(5);
        $("#practice-question-count").attr("disabled", "disabled");
        $("#practice-category_aud").parents("tr").first().find(".subscribe-message").css('display','block');
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

}

$.COR.account.showDefaultPage = function () {

    $.COR.pageSwap($.COR.getCurrentDisplayedId(), "js-content-wrapper-study");

    $("#header-navigation li").removeClass('current');
    $("#header-navigation_study").addClass('current');
}

// Subscription Helpers

$.COR.account.getSubscriptionsForServer = function(){
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
        for(var j = 0; j < response.QuestionResponse[0].Answers.length; j++){
            var correctClass = j == response.QuestionResponse[0].CorrectAnswerIndex ? "class='correct'" : "";

            questionAnswers += "<li " + correctClass+ " >" + response.QuestionResponse[0].Answers[j].DisplayText + "</li>";
        }

        // Build Question History

        var questionHistory = "";

        for(var j = 0; j < response.Summary.length; j++){
            var summary = response.Summary[j];

            // Figure out the index of the answer
            var questionIndex = "-"; // default set for skipped;
            if(summary.QuestionsToAnswersId != 0){
                for(var k = 0; k < response.QuestionResponse[0].Answers.length; k++){
                    if (response.QuestionResponse[0].Answers[k].QuestionToAnswersId == summary.QuestionsToAnswersId) {
                        questionIndex = k;
                        break;
                    }
                }
            }


            questionHistory += "<tr>" +
                                            //"<td>" + questionIndex + "</td>"+
                                            "<td>" + summary.Correct + "</td>"+
                                            "<td>" + summary.TimeSpentOnQuestion + "</td>"+
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

    $("#full-screen-container .exit-simulator-exit").on('click', function(){
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
        $("#study-question-viewer-question-mc").hide();
        $("#study-question-viewer-directions").fadeIn();
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

    if ((question.selectedAnswer != 0  && self.simulator.options.mode == 'study') || self.simulator.completed == true) {

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
                else{
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