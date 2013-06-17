
$.COR.account = {
    offline : false,
    user:null
};



$.COR.account.setup = function (data, successCallback) {

    var self = this;
    var cacheInvalidator = $.COR.DisableCache ? "?num=" + Math.floor(Math.random() * 11000) : "";

    //Set Hash 

    $(body).append("<input id='account-hash' type='hidden' value='" + data.Hash + "'></input>");


    $.get("/HTMLPartials/Account.html" + cacheInvalidator, function (loggedinPageHTML) {

        $("#body").append(loggedinPageHTML);

        self.user = data.Account;

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
    
    $("#account-settings-update-button").on("click", function(e){
        e.preventDefault();
        if($(this).hasClass("disabled")){return;}
        
        if ($.COR.validateForm($(this).parents("form"))) {
            
            var self = this;
            
            $(this).html("Saving...").addClass("disabled");
            
            $.post("/PHP/AJAX/Account/UpdateAccount.php",$(this).parents("form").serialize() + "&Data=true", function(data){
                                
                $.COR.cycleButton(self, "Saved", "Update");
                $(self).removeClass("disabled");
                
            });            
            
        }
                    
    });
    
    $("#account-settings-update-password-button").on("click", function(e){
        e.preventDefault();
                
        if ($.COR.validateForm($(this).parents("form")) && $(this).hasClass("disabled") == false) {
            
            var self = this;
            var oldPassword = $.COR.MD5($("#account-settings-old-password").val());
            var newPassword = $.COR.MD5($("#account-settings-new-password").val());
            
            
            
            $(this).html("Saving...").addClass("disabled");
            
            $.post("/PHP/AJAX/Account/UpdatePassword.php","old_password=" + oldPassword + "&password=" + newPassword + "&Data=true", function(data){
                
                $("#account-settings-current-password").val(newPassword);
                $.COR.account.user.LoginPassword = newPassword;
                $.COR.cycleButton(self, "Saved", "Update");
                $(self).removeClass("disabled");
            });
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



    /* -------- Practice Management --------- */

    $("#start-practice").on("click", function () {


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
                    var questions = self.getOfflineQuestions();
                    var questionIndex = 0;

                    $("#full-screen-container .footer-nav .prev").on('click', function () {
                        questionIndex--;

                        self.displayStudyQuestion(questions[questionIndex]);


                    });

                    $("#full-screen-container .footer-nav .next").on('click', function () {
                        questionIndex++;

                        if (questionIndex == questions.length) {
                            // Were done show completed screen
                            alert("Are you sure you're done?");
                        }

                        if (questionIndex == 0) {
                            // Hide Previous
                            $("#study-question-viewer-page .footer-nav .prev").addClass('hidden')
                        }
                        else {
                            if (!$("#study-question-viewer-page .footer-nav .prev").is(":visible")) {
                                $("#study-question-viewer-page .footer-nav .prev").removeClass('hidden');
                            }
                        }


                        self.displayStudyQuestion(questions[questionIndex]);

                    });

                }
            );




        }, 1000);





    });




    // Call setup on any other events that are sub of the account object
    
};








// Helper Functions


$.COR.account.initUser = function () {

    // handle if user first time flag is true
    if ($.COR.account.user.IsRegistrationInfoObtained == "0") {
        this.showNewAccountPopup();
    }

    $.COR.pageSwap("js-content-wrapper-splash", "js-content-wrapper-user-account");
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

$.COR.account.getOfflineQuestions = function () {

    return offlineObjects = [
        {
            type: "direction"
        },
        {
            type: "question",
            question: "Question 1",
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
            type: "question",
            question: "Question 2",
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
            type: "question",
            question: "Question 3",
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

$.COR.account.displayStudyQuestion = function (question) {

    var self = this;

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


}

$.COR.account.setStudyQuestionData = function () {


}