window.onblur = function () { window.blurred = true; };
window.onfocus = function () { window.blurred = false; };


var siteOptions = {

    loginCallback: function () {

        $.CPAEB.hideLoginUI();
        location.hash = "account";
    }

};


$.COR.init(siteOptions);


$(document).ready(function () {
    $.CPAEB.init();


    var urlVars = getUrlVars();

    if (urlVars.offline != undefined && urlVars.offline == 'true') {
        $.COR.account.offline = true;
    }

    // Initialization
    $.COR.pageEvents();


    $("#pricing-holder .squaredTwo label").on('click', function () {
        var amount = 0;


        // This is necessary because the checkbox element doesn't change it's checked state till after this event occurs, so wait for that
        setTimeout(function () {

            $("#pricing-holder .squaredTwo input").each(function (index, element) {
                if ($(element).prop("checked")) {
                    amount += 20;
                }
            });

            $("#pricing-total .amount").html(amount);
        }, 50);

    });



    $(".register-sign-up").on('click', function () {


        $.COR.Utilities.FullScreenOverlay.loadLocal("js-overlay-register", $("#js-overlay-register").attr("contentSize"), false, function () {


            $("#full-screen-container .registration-far").prop('checked', $("#pricing-row1-check").prop('checked'));
            $("#full-screen-container .registration-aud").prop('checked', $("#pricing-row2-check").prop('checked'));
            $("#full-screen-container .registration-bec").prop('checked', $("#pricing-row3-check").prop('checked'));
            $("#full-screen-container .registration-reg").prop('checked', $("#pricing-row4-check").prop('checked'));


            $(".registration-finish-button").on("click", function (e) {

                if ($(this).hasClass("disabled")) { return; }

                var clickedElement = this;
                var originalHTML = $(this).html();
                e.preventDefault();

                $(this).addClass('disabled');

                if ($.COR.validateForm($(this).parents("form"))) {

                    var email = $("#full-screen-container .registration-email").val();
                    var password = $("#full-screen-container .registration-password").val();

                    var far = $("#full-screen-container .registration-far").is(":checked") ? "1" : "0";
                    var aud = $("#full-screen-container .registration-aud").is(":checked") ? "1" : "0";
                    var bec = $("#full-screen-container .registration-bec").is(":checked") ? "1" : "0";
                    var reg = $("#full-screen-container .registration-reg").is(":checked") ? "1" : "0";

                    var sections = [
                        {
                            "FAR": far,
                            "AUD": aud,
                            "BEC": bec,
                            "REG": reg
                        }
                    ];

                    $(this).html("One Sec...");

                    $.COR.services.register(email, password, sections, function (response) {
                        if (response.Result == "0") {
                            $("#full-screen-container .registration-email").parent().append("<span class='error-message'>" + response.Reason + "</span>");
                            $("#registration-finish-button").removeClass("disabled").html(originalHTML);
                        }
                        else {

                            // We should be able to automatically log them in...
                            $.COR.services.login(email, password, function () {

                                // We're good to go lets setup the account object and change pages...
                                $.COR.account.setup(response, function () {
                                    $.COR.toggleAccountNavigation(); // TODO: This should be done on the account side of things
                                    $.COR.TPrep.hideFullScreenOverlay();
                                    location.hash = "account";
                                });

                            });
                        }
                    });

                }


            });

        });
    });



    $("#header-logo").on("click", function () {
        location.hash = "";
    });





    $(window).hashchange();


});



(function ($) {

    // Adding COR ot jQuery
    $.CPAEB = {

    };

})(jQuery);


$.CPAEB.init = function () {

    var self = this;

    var singlePages = ["product-pricing", "about", "contact"];
    var accountPages = ["study", "my-review", "my-info","faqs","contact"];
    var singlePopups = ["privacy-policy", "terms-of-service","reset-password"];

    var pageHashCallback = function (hash) {

        $.COR.Utilities.FullScreenOverlay.hide();

        var loc = hash[0];
        var result = false;

        if (hash.length == 1 && $.inArray(loc, singlePages) != -1) {

            //Auto Header Nav - Page Nav
            if (!$("#header-navigation_" + loc).hasClass('current')) {
                $("#header-navigation li").removeClass('current');
                $("#header-navigation_" + loc).addClass('current');
            }

            // We're going to handle subpages and 'default's by using a loc_default subpage and showing it
            // We also assume that the rest of the subpages are loc_content (showing these would be used by doing two parts,
            // i.e. part1/part and used in the else

            // hide anything sub pages that might be open
            $("." + loc + "-content").addClass('hidden');

            // Show the default

            $("#" + loc + "-default").removeClass('hidden');

            $.COR.pageSwap($.COR.getCurrentDisplayedId(), 'js-content-wrapper-' + loc);

            result = true;
        }
        return result;
    };
    
    var popupHashCallback = function (hash) {

        var loc = hash[0];
        var result = false;

        if (hash.length == 1 && $.inArray(loc, singlePopups) != -1) {

            var fileName = loc.replace(/-/g, '');

            // No idea how to handle actions of externally loaded popups, or any kind of hash driven popups at all...
            $.COR.Utilities.FullScreenOverlay.loadExternal("/HTMLPartials/Home/" + fileName + ".html", "medium", true, function () {


                switch (loc) {

                    case "reset-password":

                        $("#reset-account-update-password").on("click", function (e) {
                            e.preventDefault();
                            $('#reset-account-reason').html('');
                            $('#reset-account-update-password').attr('disabled', true);
                            $('#reset-account-swirly').removeAttr('style');

                            if (self.validateForm($(this).parents("form"))) {
                                //console.log('validation succeeded');

                                //get form data into JSON object
                                var ResetData = $($(this).parents("form")).serialize();

                                //submit to form for processing
                                $.post("/PHP/AJAX/Account/ForgotPassword.php", ResetData + "&Data=true", function (data) {
                                    if (data.PasswordUpdated != null) {


                                        $('#reset-account-reason').html(data.Reason);
                                    }
                                    else {

                                        $('#reset-account-reason').html('Error in request');
                                    }
                                }, "JSON");
                            }
                            else {
                                //console.log('validation failed');
                            }
                            $('#reset-account-swirly').css('display', 'none');
                            $('#reset-account-update-password').removeAttr('disabled');
                        });

                        break;

                }


            });

            result = true;
        }

        return result;
    };

    var subPageHashCallback = function (hash) {

        $.COR.Utilities.FullScreenOverlay.hide();

        // It's a subpage!
        //console.log("show: " + parts[0] + " @ " + parts[1]);

        /*
            Subpages work by using the #part1/part2 to build the content id that is shown : id='part1_part2'
            In order to have multiple sub pages that show and hide, we assume that they are all on the same branch, 
            so we can go to the parent hide everyone at that level, then show the one that we want to see...

            Should work ... and scale to even deeper levels if needed!
        */

        var parts = hash;
        var loc = hash[0] + "/" + hash[1];

        var element = $("#" + parts[0] + "_" + parts[1]);
        var result = false;


        if (element.length > 0) {

            //Auto Header Nav - Page Nav
            if (!$("#header-navigation_" + parts[0]).hasClass('current')) {
                $("#header-navigation li").removeClass('current');
                $("#header-navigation_" + parts[0]).addClass('current');
            }


            $(element).parent().children().addClass("hidden");
            $(element).removeClass("hidden");

            $.COR.pageSwap($.COR.getCurrentDisplayedId(), 'js-content-wrapper-' + parts[0]);

            $(".nav a[href='#" + loc + "']").parents(".js-content-wrapper").find("ul.nav a").removeClass("active");

            $(".nav a[href='#" + loc + "']").addClass('active');

            result = true;
        }

        return result;
    };

    /* Account Callbacks */

    var accountPagesCallback = function (hash) {

        var result = false;
        var loc = hash[0] + "/" + hash[1];

        if (hash.length == 2 && $.inArray(hash[1], accountPages) != -1) {


            if ($.COR.account.user != null) {

                result = true;

                $.COR.log("Account Page: " + loc);

                if (hash.length == 1) {
                    $("#header-navigation-account_study").addClass('current');
                }
                else if (hash.length == 2) {

                    //Auto Header Nav - Page Nav
                    if (!$("#header-navigation-account_" + hash[1]).hasClass('current')) {
                        $("#header-navigation-account li").removeClass('current');
                        $("#header-navigation-account_" + hash[1]).addClass('current');
                    }

                    // We're going to handle subpages and 'default's by using a loc_default subpage and showing it
                    // We also assume that the rest of the subpages are loc_content (showing these would be used by doing two parts,
                    // i.e. part1/part and used in the else

                    // hide anything sub pages that might be open
                    $("." + hash[1] + "-content").addClass('hidden');

                    // Show the default

                    $("#" + hash[1] + "-default").removeClass('hidden');

                    $.COR.pageSwap($.COR.getCurrentDisplayedId(), 'js-content-wrapper-' + hash[1]);
                }
                else if (hash.length == 3) {
                    // It's a subpage!
                    $.COR.log.log("show: " + parts[0] + " @ " + parts[1]);

                    /*
                        Subpages work by using the #part1/part2 to build the content id that is shown : id='part1_part2'
                        In order to have multiple sub pages that show and hide, we assume that they are all on the same branch, 
                        so we can go to the parent hide everyone at that level, then show the one that we want to see...
        
                        Should work ... and scale to even deeper levels if needed!
                    */

                    var element = $("#" + hash[0] + "_" + hash[1]);
                    $(element).parent().children().addClass("hidden");
                    $(element).removeClass("hidden");

                    $.COR.pageSwap($.COR.getCurrentDisplayedId(), 'js-content-wrapper-' + hash[0]);

                    $(".nav a[href='#" + loc + "']").addClass('active');

                }


            }
            else { // I don't think this will ever get called...

                self.hideLoginUI();

                $.COR.Utilities.refreshLogin(function () {
                    //$.CPAEB.setupAccountHashHandling(accountPagesCallback, accountStartPracticeCallback);
                    $(window).hashchange();
                    
                });
                result = true;

            }








            result = true;

        }

        return result;

    };

    var accountStartPracticeCallback = function (hash) {

        var loc = hash[0];
        var result = false;

        if (hash.length == 1 && loc == "start-practice") {
            $.COR.account.startStudy();
            result = true;
        }

        return result;

    };

    var accountCallback = function (hash) {

        var result = false;
        var loc = hash[0];

        if (hash.length == 1 && loc == "account") {

            if ($.COR.account.user != null) {

                $.CPAEB.setupAccountHashHandling(accountPagesCallback, accountStartPracticeCallback);
                $("#header-navigation-account_study").addClass('current');
                result = true;

            }
            else {

                self.hideLoginUI();

                $.COR.Utilities.refreshLogin(function () {
                    $.CPAEB.setupAccountHashHandling(accountPagesCallback, accountStartPracticeCallback);
                    $("#header-navigation-account_study").addClass('current');
                });
                result = true;
            }
        }

        // We need to refresh login and try again
        else if (loc == "account" && hash.length > 1 && $.COR.account.user == null) {

            self.hideLoginUI();

            $.COR.Utilities.refreshLogin(function () {
                $.CPAEB.setupAccountHashHandling(accountPagesCallback, accountStartPracticeCallback);
                $(window).hashchange();
            });
            result = true;
        }

        return result;
    };



    // Setup any hash handling
    $.COR.Utilities.HashHandler.init({
        hashRequests: [
            $.COR.Utilities.HashHandler.buildHashRequest({
                callback: pageHashCallback
            }),
            $.COR.Utilities.HashHandler.buildHashRequest({
                callback: popupHashCallback
            }),
            $.COR.Utilities.HashHandler.buildHashRequest({
                callback: subPageHashCallback
            }),
            $.COR.Utilities.HashHandler.buildHashRequest({
                callback: accountCallback
            })
        ],
        defaultHashRequest: $.COR.Utilities.HashHandler.buildHashRequest({
            callback: function () {
                
                // Show Home Page If No User
                if ($.COR.account.user == null) {
                    $.COR.Utilities.FullScreenOverlay.hide();
                    $.COR.toggleHomeNavigation();
                    $.COR.pageSwap(null, "home");
                }

                // Show Default Account Page Otherwise
                else{
                    $.COR.account.showDefaultPage();
                }

                return true;
            }
        })

    });



}

$.CPAEB.hideLoginUI = function () {
    $("#header-login-container").hide();
    $("#header-logout-container").show();
    $("#home-login-password").val("");
    $("#home-login-username").val("");
}

$.CPAEB.setupAccountHashHandling = function (accountPagesCallback, accountStartPracticeCallback) {

    $.COR.Utilities.HashHandler.addHashRequest(
        $.COR.Utilities.HashHandler.buildHashRequest({
            callback: accountPagesCallback
        })
    );

    $.COR.Utilities.HashHandler.addHashRequest(
        $.COR.Utilities.HashHandler.buildHashRequest({
            callback: accountStartPracticeCallback
        })
    );
}

/* TODO: Clean up all this stuff... */


$.COR.TPrep = {};

$.COR.TPrep.showFullScreenOverlay = function (content, contentClassSize, events) {


    if ($("#full-screen-container").is(":visible")) {

        $("#full-screen-container .content").fadeOut(function () {
            $(this).html("");

            $("#full-screen-container .content").html(content).removeClass().addClass(contentClassSize + " content").fadeIn();

            if (typeof events == 'function') {
                events();
            }

            $("#full-screen-overlay").fadeIn();
        });


    }
    else {
        $("#full-screen-container .content").html(content);

        $("#full-screen-container").removeClass().addClass(contentClassSize);

        $("#full-screen-holder").show();
        $("#full-screen-container").show();


        if (typeof events == 'function') {
            events();
        }

        $("#full-screen-overlay").fadeIn();
    }
}

$.COR.TPrep.hideFullScreenOverlay = function () {

    $("#full-screen-container").fadeOut(function () {
        $("#full-screen-overlay").fadeOut(200);
    });
}


function getUrlVars() {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}
