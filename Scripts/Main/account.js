
$.COR.account = {
    offline : false,
    user: null,
    hash: null,
    licenses: null,
    userSettings:null,
    simulator: {
        options: {},
        live : false,
        questions: null,
        questionIndex: null,
        completed : false
    }
};



$.COR.account.setup = function (data, successCallback) {

    var self = this;
    var cacheInvalidator = $.COR.DisableCache ? "?num=" + Math.floor(Math.random() * 11000) : "";

    //Set Hash 

    $(body).append("<input id='account-hash' type='hidden' value='" + data.Hash + "'></input>");


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

$.COR.account.hashHandler = function () {

    if ($.COR.account.user != null) {

        // WTF do these comments even mean?? WHAT WAS I DOING? _sigh_
        // This case should only be called because we've just created a new user but havent finished filling out their information
        //this.initUser(); // TODO: This was being called on every login, but apparently based on the above comment shouldn't be .. 
    }
    else {

        $.COR.checkLogin(
            function (data) {
                $.COR.account.setup(data, function () {
                    //$.COR.pageSwap("js-content-wrapper-splash", "js-content-wrapper-user-account");
                });
            },

            function (reason) {
                console.log("Didn't log you in because : " + reason);
            }
        );


    }

};

$.COR.account.setupEvents = function () {
    
    var self = this;
  
    
    // Navigation
    
    $("#user-account-navigation li").on("click", function(){
       
       if($(this).hasClass("current")){return;}
       
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
    
    $("#account-settings-update-password-button").on("click", function(e){
        e.preventDefault();
                
        if ($.COR.validateForm($(this).parents("form")) && $(this).hasClass("disabled") == false) {
            
            var self = this;
            var oldPassword = $.COR.MD5($("#account-settings-old-password").val());
            var newPassword = $.COR.MD5($("#account-settings-new-password").val());
            
            
            
            $(this).html("Saving...").addClass("disabled");
            
            //$.post("/PHP/AJAX/Account/UpdatePassword.php","old_password=" + oldPassword + "&password=" + newPassword + "&Data=true", function(data){
                
            //    $("#account-settings-current-password").val(newPassword);
            //    $.COR.account.user.LoginPassword = newPassword;
            //    $.COR.Utilities.cycleButton(self, "Saved", "Update");
            //    $(self).removeClass("disabled");
            //});

            var ph = new $.COR.Utilities.PostHandler({
                service: "account", call: "updatePassword",
                params: { password: $.COR.MD5(newPassword), hash: self.hash },
                success: function (data) {

                    $("#account-settings-current-password").val("");
                    $("#account-settings-old-password").val("")
                    $("#account-settings-old-password-again").val("")

                    $.COR.account.user.LoginPassword = newPassword;
                    $.COR.Utilities.cycleButton(self, "Saved", "Update");
                    $(self).removeClass("disabled");

                }
            });

            ph.submitPost();


        }     
           
    });
    
    $("#account-settings-add-class-code-button").on("click", function(e){
        
        e.preventDefault();
        if($(this).hasClass("disabled")){return;}
        
        if ($.COR.account.user.IsInClass == 0 && $.COR.validateForm($(this).parents("form"))) {
            
            var self = this;
            
            $(this).html("Adding...").addClass("disabled");
            
            $.post("/PHP/AJAX/Account/AddClassCode.php",$(this).parents("form").serialize() + "&Data=true", function(data){
                                
                alert("Added");
                
            });                
        }        
        
    });

    $("#account-settings-purchase-classes").on("click", function () {

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

    setTimeout(function () {
        // Show Testing UI - full screen
        $.COR.TPrep.showFullScreenOverlay(
            $("#js-overlay-content-study-questions").html(),
            $("#js-overlay-content-loading-questions").attr("contentSize"),
            function () {
                // Get Questions

                self.simulator.questions = self.getOfflineQuestions();
                self.simulator.questionIndex = 0;

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
        );




    }, 1000);

};

// Gets a set of questions for offline testing
$.COR.account.getOfflineQuestions = function () {

    return offlineObjects = [
        {
            type: "direction",
            index: 0
        },
        {
            text: "Question 1",
            answers: [
                    "Answer 1",
                    "Answer 2",
                    "Answer 3",
                    "Answer 4"
            ],
            answerIndex: 1,
            explanation: "Answer Explanation"
        },
        {
            text: "Question 2",
            answers: [
                    "Answer 1",
                    "Answer 2",
                    "Answer 3",
                    "Answer 4"
            ],
            answerIndex: 1,
            explanation: "Answer Explanation"
        },
        {
            text: "Question 3",
            answers: [
                    "Answer 1",
                    "Answer 2",
                    "Answer 3",
                    "Answer 4"
            ],
            answerIndex: 1,
            explanation: "Answer Explanation"
        }
    ];


}

// Builds the dynamic footer UI
$.COR.account.setupQuestionFooterNavigation = function (questions) {

    $("#full-screen-container .footer-questions-quicklink-holder div").each(function (index, element) {
        if ($(element).hasClass('directions')) return;
        $(element).remove();
    });

    var len = questions.length;

    for (var i = 0; i < len; i++) {

        if (questions[i].text != undefined) {

            var html = "<div class='question-quicklink' index='" + i + "'>" + i + " <div class='flag'></div></div>";
            $("#full-screen-container .footer-questions-quicklink-holder").append(html);

            questions[i].index = i;
            questions[i].type = 'question';
        }
    }

    // Insert Results quicklink
    $("#full-screen-container .footer-questions-quicklink-holder").append("<div index='" + (i++) + "' class='results question-quicklink hidden'>Results</div>");


}

// Shows the selected Question UI
$.COR.account.displayStudyQuestion = function (question) {

    var self = this;


    if($("#full-screen-container  #study-question-viewer-results").is(":visible")){
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

    $("#full-screen-container .answer-explanation").hide();

    $("#full-screen-container .question-content").html(question.text);
    $("#full-screen-container .answer-explanation-holder").html(question.explanation);

    $("#full-screen-container .answer-options table").html("");

    for (var i = 0; i < question.answers.length; i++) {

        var html = "<tr><td class='result-spacer'></td><td><input type='radio' id='study-question-answer-" + i + "' value='" + i + "' name='study-question-answer' /></td>";
        html += "<td><label for='study-question-answer-" + i + "'>" + question.answers[i] + "</label></td></tr>";
        $("#full-screen-container .answer-options table").append(html);
    }

    // Question Events

    $("#full-screen-container .answer-options input").on('change', function () {
        self.selectAnswer(question, this);
    });


    // Check if Study Mode and Question Answered - Disable Question

    if ((typeof question.selectedAnswer != "undefined" && self.simulator.options.mode == 'study') || self.simulator.completed == true) {

        // Set Checked Index
        $($($('#full-screen-container .answer-options table tr')[question.selectedAnswer]).find('input')).attr('checked', 'checked');

        // Set Questions Disabled
        $("#full-screen-container .answer-options table input").attr('disabled', 'disabled');

        // Indicate Correct Answer
        $($($('#full-screen-container .answer-options table tr')[question.answerIndex]).children()[0]).addClass('correct');

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
        $($($(selectedInput).parents('tbody').children('tr')[question.answerIndex]).children()[0]).addClass('correct');

        // Show Explanation
        $("#full-screen-container .answer-explanation").show();

        // Disable Inputs
        $("#full-screen-container .answer-options table input").attr('disabled', 'disabled');
    }

}

// Transition from questions to review page

$.COR.account.completeTest = function () {

    var self = this;

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

            // skip first question
            for (var i = 1; i < questions.length; i++) {

                var question = questions[i];

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



        });
    });
}

// Exit simulator
$.COR.account.exitSimulator = function(){

    this.simulator.live = false;

    $("#full-screen-container #study-questions-viewer-wrapper").unbind().remove();


    $.COR.TPrep.hideFullScreenOverlay();
    location.hash = "study";
}