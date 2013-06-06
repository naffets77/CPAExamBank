
$.COR.account = {
    user:null,
    chat : {
        Init : false,
        Active : false,
        LastPoll : null,
        PollInterval : 30,
        FriendsList : null,
        FriendRequests : null,
        NewMessages: null,
        PartnerId : null
    },
    classes : {
       ClassCache: [],
       StudentCache: [],
       TopicsLoaded :false
    },
    classPlans :{
        classPlans: {},
        selectedClassPlan: null,
        activeTopicId : null
    }
};



$.pubty.account.setup = function (data, successCallback) {

    var self = this;
    var cacheInvalidator = $.pubty.DisableCache ? "?num=" + Math.floor(Math.random() * 11000) : "";

    //Set Hash 

    $(body).append("<input id='account-hash' type='hidden' value='" + data.Hash + "'></input>");


    $.get("/HTMLPartials/Account.html" + cacheInvalidator, function (loggedinPageHTML) {

        $("#body div.content-wrapper").append(loggedinPageHTML);

        self.user = data.Account;

        $("#account-settings-username").val(self.user.LoginName);
        $("#account-settings-first-name").val(self.user.FirstName);
        $("#account-settings-last-name").val(self.user.LastName);

        $("#account-settings-naitive-language").val(self.user.PrimaryLanguageId);
        $("#account-settings-practice-language").val(self.user.SoughtLanguageId);
        $("#account-settings-practice-language-proficiency").val(self.user.SoughtLanguageProficiencyId);

        $("#account-settings-current-password").val(self.user.LoginPassword);


        if (data.Account.IsInstructor == 0) {
            $("#user-account-navigation .instructor").hide();
            $("#account-nav_3").trigger("click");
        }
        else {
            $("#user-account-navigation .instructor").show();
            $("#account-nav_1").trigger("click");

            self.classes.addClasses(data.Classes);
            self.classPlans.addClassPlans(data.ClassPlans);
        }


        SetupDropdowns();

        self.setupEvents();

        $("#header-login-container").hide();
        $("#header-logout-container").show();


        $("#home-login-password").val("");
        $("#home-login-username").val("");

        self.initUser();

        successCallback();
    })



}

$.pubty.account.hashHandler = function () {

    if ($.pubty.account.user != null) {

        // This case should only be called because we've just created a new user but havent finished filling out their information
        //this.initUser(); // TODO: This was being called on every login, but apparently based on the above comment shouldn't be .. 
    }
    else {

        $.pubty.checkLogin(
            function (data) {
                $.pubty.account.setup(data, function () {
                    //$.pubty.pageSwap("js-content-wrapper-splash", "js-content-wrapper-user-account");
                });
            },

            function (reason) {
                console.log("Didn't log you in because : " + reason);
            }
        );


    }

};

$.pubty.account.setupEvents = function(){
    
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
       
       // 5 Second polling if we're on the chat window, otherwise 30 seconds       
       $.pubty.account.chat.PollInterval = $(this).attr("id").split("_")[1]  === "4" ? 5 : 30;
       
    });
    

    // Logout
    $("#header-logout-container").on("click", function () {

        // Clear Account User & stop polling and things like that?
        self.user = null;

        // Fade Out Account Page
        $("#js-content-wrapper-user-account").hide();

        // Fade in Home Page
        $("#js-content-wrapper-splash").fadeIn();

        // Swap Logout with Login UI
        $("#header-logout-container").hide();
        $("#header-login-container").show();
        

    });

    // Temporary Show the Practice Topic Screen Stuff

    $("#voice-chat-start-practice").on("click", function () {

        // TODO - This will need to go in it's correct spot, for now while developing the UI this will work here for now
        $.pubty.practiceLesson.init();
        //$("#js-content-wrapper-matched").fadeIn();

    });
    
    /* ----- Settings Management ---- */
    
    $("#account-settings-update-button").on("click", function(e){
        e.preventDefault();
        if($(this).hasClass("disabled")){return;}
        
        if($.pubty.validateForm($(this).parents("form"))){
            
            var self = this;
            
            $(this).html("Saving...").addClass("disabled");
            
            $.post("/PHP/AJAX/Account/UpdateAccount.php",$(this).parents("form").serialize() + "&Data=true", function(data){
                                
                $.pubty.cycleButton(self,"Saved", "Update");
                $(self).removeClass("disabled");
                
            });            
            
        }
                    
    });
    
    $("#account-settings-update-password-button").on("click", function(e){
        e.preventDefault();
                
        if($.pubty.validateForm($(this).parents("form")) && $(this).hasClass("disabled") == false){
            
            var self = this;
            var oldPassword = $.pubty.MD5($("#account-settings-old-password").val());
            var newPassword = $.pubty.MD5($("#account-settings-new-password").val());
            
            
            
            $(this).html("Saving...").addClass("disabled");
            
            $.post("/PHP/AJAX/Account/UpdatePassword.php","old_password=" + oldPassword + "&password=" + newPassword + "&Data=true", function(data){
                
                $("#account-settings-current-password").val(newPassword);
                $.pubty.account.user.LoginPassword = newPassword;
                $.pubty.cycleButton(self,"Saved", "Update");
                $(self).removeClass("disabled");
            });
        }     
           
    });
    
    $("#account-settings-add-class-code-button").on("click", function(e){
        
        e.preventDefault();
        if($(this).hasClass("disabled")){return;}
        
        if($.pubty.account.user.IsInClass == 0 && $.pubty.validateForm($(this).parents("form"))){
            
            var self = this;
            
            $(this).html("Adding...").addClass("disabled");
            
            $.post("/PHP/AJAX/Account/AddClassCode.php",$(this).parents("form").serialize() + "&Data=true", function(data){
                                
                alert("Added");
                
            });                
        }        
        
    });

    $("#account-settings-purchase-classes").on("click", function () {

    }); 

    
    $.pubty.account.chat.setupEvents();
    $.pubty.account.classes.setupEvents();
    $.pubty.account.classPlans.setupEvents();
    
    $.pubty.account.chat.init();
    
};


 




// Helper Functions


$.pubty.account.initUser = function () {

    // handle if user first time flag is true
    if ($.pubty.account.user.IsRegistrationInfoObtained == "0") {
        this.showNewAccountPopup();
    }

        // Normal Account Initalization
    else {
        this.chat.startPoll();
    }

    $.pubty.pageSwap("js-content-wrapper-splash", "js-content-wrapper-user-account");
}

$.pubty.account.showNewAccountPopup = function(){


    // show the overlay
    $("#popup-overlay").show();

    // show the popup (should default with swirly and loading)
    $("#popup-container").show();

    //show default loading popup
    $("#new-account-popup_0").show();


    $.get("/HTMLPartials/Account/NewInstructorAccountPopup.html", function (data) {

        $.pubty.Utilities.PopupHandler.init(data, function () {

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
                        postData[name] = $.pubty.MD5(value);
                        break;
                    case 'password-again':
                        break;
                    default:
                        postData[name] = value;
                        break;
                }
            });

            postData['UID'] = $.pubty.account.user.AccountUserId;


            
            var ph = new $.pubty.postHandler({
                service: "account", call: "completeTeacherRegistration",
                params: postData,
                success: function (data) {
                    window.location.reload()
                }
            });

            ph.submitPost();
            

            //var postData = form1 + form2 + form3 + "&UID=" + $.pubty.account.user.AccountUserId;

            //console.log(postData);

            /*
            $.post("/PHP/AJAX/Account/TeacherOtherRegistration.php", postData, function (data) {
                // Will Probably want to update user object with returned user object?
                // Or will want to autolog them back on (If possible?)
                //$.pubty.Utilities.PopupHandler.hide();

                // Lets just reload the page, which should handle logging them in for now... later we can do 
                // a fancier delete the user and reload with the password they provided, or something like that? 
                window.location.reload()
            });
            */


        });

    });

}