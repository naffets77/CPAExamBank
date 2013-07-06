
$.COR.account = {
    offline: false,
    user: null,
    hash: null,
    licenses: null,
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

    //Set Hash 

    $('body').append("<input id='account-hash' type='hidden' value='" + data.Hash + "'></input>");


    $.get("/HTMLPartials/Account.html" + cacheInvalidator, function (loggedinPageHTML) {

        $("#body").append(loggedinPageHTML);

        self.user = data.Account;
        self.hash = data.Hash;
        self.licenses = data.Licenses;
        self.settings = data.UserSettings;

        $("#account-settings-username").val(self.user.LoginName);
        $("#account-settings-first-name").val(self.user.FirstName);
        $("#account-settings-last-name").val(self.user.LastName);

        $("#account-settings-naitive-language").val(self.user.PrimaryLanguageId);
        $("#account-settings-practice-language").val(self.user.SoughtLanguageId);
        $("#account-settings-practice-language-proficiency").val(self.user.SoughtLanguageProficiencyId);

        $("#account-settings-current-password").val(self.user.LoginPassword);

        self.setupEvents();

        $("#header-login-container").hide();
        $("#header-logout-container").show();


        $("#home-login-password").val("");
        $("#home-login-username").val("");

        self.initUser();

        successCallback();
    });
}

$.COR.account.hashHandler = function (hashParts, loc) {

    if ($.COR.account.user != null) {

        console.log("Account Page: " + loc);

        if (hashParts.length == 1) {
            $("#header-navigation-account_study").addClass('current');
        }
        else if (hashParts.length == 2 ) {

            //Auto Header Nav - Page Nav
             if (!$("#header-navigation-account_" + hashParts[1]).hasClass('current')) {
                $("#header-navigation-account li").removeClass('current');
                $("#header-navigation-account_" + hashParts[1]).addClass('current');
            }

            // We're going to handle subpages and 'default's by using a loc_default subpage and showing it
            // We also assume that the rest of the subpages are loc_content (showing these would be used by doing two parts,
            // i.e. part1/part and used in the else

            // hide anything sub pages that might be open
             $("." + hashParts[1] + "-content").addClass('hidden');

            // Show the default

             $("#" + hashParts[1] + "-default").removeClass('hidden');

             $.COR.pageSwap($.COR.getCurrentDisplayedId(), 'js-content-wrapper-' + hashParts[1]);
        }
        else if(hashParts.length == 3) {
            // It's a subpage!
            //console.log("show: " + parts[0] + " @ " + parts[1]);

            /*
                Subpages work by using the #part1/part2 to build the content id that is shown : id='part1_part2'
                In order to have multiple sub pages that show and hide, we assume that they are all on the same branch, 
                so we can go to the parent hide everyone at that level, then show the one that we want to see...

                Should work ... and scale to even deeper levels if needed!
            */

            var element = $("#" + hashParts[0] + "_" + hashParts[1]);
            $(element).parent().children().addClass("hidden");
            $(element).removeClass("hidden");

            $.COR.pageSwap($.COR.getCurrentDisplayedId(), 'js-content-wrapper-' + hashParts[0]);

            $(".nav a[href='#" + loc + "']").addClass('active');

        }


    }
    else {

        $.COR.Utilities.refreshLogin();

    }

};

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

        // Clear Account User & stop polling and things like that?
        self.user = null;

        // Swap Logout with Login UI
        $("#header-logout-container").hide();
        $("#header-login-container").show();

        location.hash = "";

        // TODO: Shold post a logout here to kill the session


    });



    /* ----- Settings Management ---- */

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
            var newPassword = $.COR.MD5($("#account-settings-new-password").val());

            var ph = new $.COR.Utilities.PostHandler({
                service: "account", call: "updatePassword",
                params: { password: newPassword, hash: self.hash },
                success: function (data) {

                    $("#account-settings-old-password").val("");
                    $("#account-settings-new-password").val("")
                    $("#account-settings-new-password-again").val("")

                    $.COR.account.user.LoginPassword = newPassword;
                    $.COR.Utilities.cycleButton(self, "Saved", "Update");
                    $(self).removeClass("disabled");

                }
            });

            ph.submitPost();


        }

    });

    $("#account-settings-add-class-code-button").on("click", function (e) {

        e.preventDefault();
        if ($(this).hasClass("disabled")) { return; }

        if ($.COR.account.user.IsInClass == 0 && $.COR.validateForm($(this).parents("form"))) {

            var self = this;

            $(this).html("Adding...").addClass("disabled");

            $.post("/PHP/AJAX/Account/AddClassCode.php", $(this).parents("form").serialize() + "&Data=true", function (data) {

                alert("Added");

            });
        }

    });

    $("#account-settings-purchase-classes").on("click", function () {

    });


    /* ----- Question Review ----- */

    $("#my-review-view-history").on("click", function () {

        var thisElement = this;

        if ($(this).hasClass("disabled")) { return; }

        $(this).addClass("disabled");
        $("#review-results tbody").html("");

        var ph = new $.COR.Utilities.PostHandler({
            service: "question", call: "getAccountUserQuestionHistory",
            params: {
                AccountUserId: $.COR.account.user.AccountUserId,
                QuestionAmount: 20,
                SectionTypeId: $("#my-review-section-type").val()
            },
            success: function (data) {
                $(thisElement).removeClass("disabled");
                self.BuildQuestionHistory(data.QuestionHistoryReturns);
            }
        });

        ph.submitPost();


    });

    


    // Call setup on any other events that are sub of the account object

};








// Helper Functions


$.COR.account.initUser = function () {

    // handle if user first time flag is true
    if ($.COR.account.user.IsRegistrationInfoObtained == "0") {
        this.showNewAccountPopup();
    }

    $.COR.pageSwap($.COR.getCurrentDisplayedId(), "js-content-wrapper-study");

    $("#header-navigation li").removeClass('current');
    $("#header-navigation_study").addClass('current');
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




// Question History Helpers

$.COR.account.BuildQuestionHistory = function (QuestionResponse) {


    

    var len = QuestionResponse.length;

    for (var i = 0; i < len; i++) {

        var response = QuestionResponse[i];


        // Build Question Answers
        var questionAnswers = "";
        for(var j = 0; j < response.QuestionResponse[0].Answers.length; j++){
            var correctClass = i == response.QuestionResponse[0].CorrectAnswerIndex ? "span class='correct'" : "";

            questionAnswers += "<li " + correctClass+ " >" + response.QuestionResponse[0].Answers.DisplayText + "</li>";
        }

        // Build Question History

        var questionHistory = "";

        for(var j = 0; j < response.Summary.length; j++){
            var summary = response.Summary[j];

            // Figure out the index of the answer
            var questionIndex = "-"; // default set for skipped;
            if(summary.QuestionToAnswersId != 0){
                for(var k = 0; k < repsonse.QuestionResponse[0].Answers.length; k++){
                    if(response.questionResponse[0].Answers[i].QuestionToAnswersId == summary.QuestionToAnswersId){
                        questionIndex = k;
                        return;
                    }
                }
            }


            questionAnswers +=           "<tr>"+
                                            "<td>" + questionIndex + "</td>"+
                                            "<td>" + summary.Correct + "</td>"+
                                            "<td>" + summary.TimeSpentOnQuestion + "</td>"+
                                            "<td>" + summary.SimulationMode + "</td>" + 
                                            "<td>" + summary.SimulationDate + "</td>"+
                                        "</tr>";
        }


        $("#review-results tbody").append(
            "<tr>"+
                "<td>" + response.Metrics[0].QuestionId + "</td>" +
                "<td>" + response.Metrics[0].SectionType + "</td>" +
                "<td>" + response.Metrics[0].TimesCorrect + "</td>" +
                "<td>" + response.Metrics[0].TimesIncorrect + "</td>" +
                "<td>" + response.Metrics[0].AverageTimePerQuestion + " s</td>" +
                "<td>" + response.Metrics[0].IsActive + "</td>" +
                "<td><span class='link more-info'>More</span></td>" +
            "</tr>"+
            "<tr class='my-info-question-data-row'>"+
                    "<td colspan ='7'>" +
                        "<div class='my-info-question-data'>" +
                            "<div class='my-info-question-holder'>" +
                                "<div class='header bold'>Question</div>" +
                                "<div class='my-info-question-text'>"+ response.QuestionResponse.Question +"</div>" +
                                "<ol class='my-info-question-answers'>" + questionAnswers + "</ol>" +
                                "<p class='my-info-explanation-text'>" +
                                    "<span class='bold'>Explanation:</span><br />"+ response.QuestionResponse.Question +
                                "</p>" +
                            "</div>" +
                            "<div class='my-info-question-history'>" +
                                "<div class='my-info-question-history-header'>Your Summary</div>" +
                                "<table>" +
                                    "<thead>" +
                                        "<tr>" +
                                            "<td>Selected Answer</td>" +
                                            "<td>Correct</td>" +
                                            "<td>Seconds Taken</td>" +
                                            "<td>Mode</td>" + 
                                            "<td>Date</td>" +
                                        "</tr>" +
                                    "</thead>" +
                                    "<tbody>" + questionAnswers + "</tbody>" +
                                "</table>" +
                            "</div>" +
                        "</div>" +
                    "</td>" +
                "</tr> "
        );

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

    this.simulator.live = true;
    this.simulator.completed = false;
    this.simulator.questions = null;
    this.simulator.questionIndex = null;

    this.simulator.options.category = $("[name=practice-category]:checked").val();
    this.simulator.options.questionCount = $("#practice-question-count").val();
    this.simulator.options.mode = $("[name=practice-mode]:checked").val();
    this.simulator.options.strategy = $("[name=practice-strategy]:checked").val();


    // Build data objects w/options

    // Show UI 'getting questions' - full screen

    $.COR.TPrep.showFullScreenOverlay(
        $("#js-overlay-content-loading-questions").html(),
        $("#js-overlay-content-loading-questions").attr("contentSize")
    );


    // Show Testing UI - full screen
    $.COR.TPrep.showFullScreenOverlay(
        $("#js-overlay-content-study-questions").html(),
        $("#js-overlay-content-loading-questions").attr("contentSize"),
        function () {
            // Get Questions

            self.simulator.questionIndex = 0;

            if (self.offline == true) {
                self.simulator.questions = self.getOfflineQuestions();
          
                setTimeout(function () {
                    self.initQuestions();
                }, 1000);
            }
            else {

                var ph = new $.COR.Utilities.PostHandler({
                    service: "question", call: "getQuestionsAndAnswers",
                    params: {
                        SectionTypeId: $("input:radio[name=practice-category]").val(),
                        QuestionAmount: $("#practice-question-count").val()
                    },
                    success: function (data) {

                        if (data.QuestionResponses != null) {

                            self.simulator.questions = [{
                                type: "direction",
                                index: 0
                            }];
                            self.simulator.questions = self.simulator.questions.concat(data.QuestionResponses);

                            self.initQuestions();
                        }
                        else {
                            alert('Server Error, please refresh the page and try again');
                        }

                    }
                });

                ph.submitPost();

            }



        }
    );
};



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
        $("#study-question-viewer-question-mc").hide(function () {
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

        var html = "<tr><td class='result-spacer'></td><td><input type='radio' id='study-question-answer-" + i + "' value='" + i + "' name='study-question-answer' /></td>";
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

        // Set Checked Index
        $($($('#full-screen-container .answer-options table tr')[question.selectedAnswer]).find('input')).attr('checked', 'checked');

        // Set Questions Disabled
        $("#full-screen-container .answer-options table input").attr('disabled', 'disabled');

        // Indicate Correct Answer
        $($($('#full-screen-container .answer-options table tr')[question.CorrectAnswerIndex]).children()[0]).addClass('correct');

        // Show Explanation
        $("#full-screen-container .answer-explanation").show();
    }

}

// Handles selecting an answer
$.COR.account.selectAnswer = function (question, selectedInput) {

    // get answer
    var selectedAnswer = $(selectedInput).val();

    // set question answered
    question.selectedAnswer = selectedAnswer;

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
                    selectedAnswer = question.Answers[question.selectedAnswer].QuestionToAnswersId;
                    answeredCorrectly = question.correctAnswerIndex == question.selectedAnswer ? 1 : 0;
                }

                postQuestions.push({
                    questionId: question.Answers[0].QuestionId,
                    accountUserId: $.COR.account.user.AccountUserId,
                    timeTaken: question.timeTaken,
                    mode: self.simulator.options.mode == 'study' ? 1 : 2,
                    selectedAnswer: selectedAnswer,
                    answeredCorrectly: answeredCorrectly
                });


                if (question.selectedAnswer != null) {

                    // make all the wrong answers red in the navigation
                    if (question.selectedAnswer != question.answerIndex) {
                        incorrect++;
                        $("#full-screen-container .footer-questions-quicklink-holder [index=" + question.index + "]").addClass("incorrect");
                    }
                    else {
                        correct++
                    }
                }
                else {
                    skipped++;
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

    this.simulator.live = false;

    $("#full-screen-container #study-questions-viewer-wrapper").unbind().remove();


    $.COR.TPrep.hideFullScreenOverlay();
    location.hash = "study";
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