(function ($) {

    // Adding COR ot jQuery
    $.COR = {
        debug: true,
        MD5: null,
        DisableCache: true,

        Utilities: {
            PopupHandler: {},
            PostHandler: {},
            Phono: {}
        }

    };

    /* Gets input fields for serialization from a form??? */
    $.fn.getInputFields = function () {

        var toReturn = [];
        var els = $(this).find(':input').get();

        $.each(els, function () {
            if (this.name && !this.disabled && (this.checked || /select|textarea/i.test(this.nodeName) || /text|hidden|password/i.test(this.type))) {
                var val = $(this).val();
                toReturn.push(encodeURIComponent(this.name) + "=" + encodeURIComponent(val));
            }
        });

        return toReturn.join("&").replace(/%20/g, "+");

    };

})(jQuery);



$.COR.log = function (logMessage) {
    if (this.debug)
        console.log("***************  " + logMessage)
}

$.COR.pageSwap = function (inPageToHideId, inPageToShowId) {

    if (inPageToShowId == "home") {
        $(".js-content-wrapper").hide();
        $("#js-content-wrapper-splash").fadeIn("slow");
    }
    else {
        $("#" + inPageToHideId).hide();
        $("#" + inPageToShowId).fadeIn("slow");
    }


    this.log("Swapping Pages: " + inPageToHideId + " to " + inPageToShowId);
}

$.COR.pageEvents = function () {

    var self = $.COR;


    $(".js-page-switch").on("click", function (e) {

        e.preventDefault();


        if (self.validateForm($(this).parents("form") && $(this).attr("ignorevalidation") == undefined)) {
            self.pageSwap(
                $(this).parents(".js-content-wrapper").attr("id"),
                $(this).attr("page-switch")
            );
        }

    });

    $("#header-logo").on("click", function () {
        location.hash = "";
    });

    $("#register").on("click", function () {

        self.pageSwap("js-content-wrapper-splash", "js-content-wrapper-register");
    });

    $("#login").on("click", function () {
        self.log("Attempting Login");

        var password = $("#home-login-password").val().length != 0 ? $("#home-login-password").val() : "testing";
        var email = $("#home-login-username").val().length != 0 ? $("#home-login-username").val() : "steffan77@gmail.com";
        self.login(
            email,
            password,
            function () { // success function

                self.toggleAccountNavigation();

                location.hash = "account";
            },
            function (failedReason) { // failure function (invalid email/password etc)
                console.log("Error logging in : " + failedReason);
            }
        );
    });

    $("#registration-finish-button").on("click", function (e) {

        var clickedElement = this;
        var originalHTML = $(this).html();
        e.preventDefault();

        if (self.validateForm($(this).parents("form"))) {

            self.log("registration validated");

            // Sets the password so that it will be serialized correctly
            $("#phash").val(self.MD5(""));

            var registrationData = $($(this).parents("form")).serialize();


            self.log($(this).parents("form").serialize());

            $(this).html("One Sec...");

            /* */
            $.post("/PHP/AJAX/Account/Registration.php", registrationData + "&Data=true", function (data) {

                self.log("Back from registration attempt");

                // null = email already exists
                if (data.uid == null) {
                    $("#registration-email").parent().append("<span class='error-message'>Email Already Exists</span>");
                    $("#registration-finish-button").html(originalHTML);
                }
                else {
                    self.login($("#registration-email").val(), "", function () {
                        self.pageSwap($(clickedElement).parents(".js-content-wrapper").attr("id"), "js-content-wrapper-user-account");
                    });
                }
            }, "JSON");


        }


    });

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

    $("input[name='reset-account-accounttype']").change(function () {
        //console.log("AccountType changed");
        if ($("input[name='reset-account-accounttype']:checked").val() == '2')
            $("#reset-account-row-teachercode").removeAttr('style');
        else
            $("#reset-account-row-teachercode").css('display', 'none');
    });

    //*************** footer events **********************************\\
    $('#footer-nav-aboutus').on('click', function () {
        self.pageSwap(getCurrentDisplayedId(), 'js-content-wrapper-aboutus');
    });

    $('#footer-nav-contactus').on('click', function () {
        self.pageSwap(getCurrentDisplayedId(), 'js-content-wrapper-contactus');
    });

    $('#footer-nav-support').on('click', function () {
        var FAQAjax = $.post("/PHP/AJAX/Support/GetFAQs.php", "SetId=1&HTMLEntities=false&Data=true", function (data) {
            if (data.ResultsFound != null) {
                var myHTML = "<ol id='support-FAQs'>";
                var mySQLObject = null;
                for (var i = 0; i < data['FAQs'].length; i++) {
                    mySQLObject = data['FAQs'][i];
                    myHTML += "<li id='FAQ-" + mySQLObject['FAQCopyId'] + "'>";
                    myHTML += "<div class='support-FAQs-question'>" + mySQLObject['Question'] + "</div>";
                    myHTML += "<div class='support-FAQs-answer'>" + mySQLObject['Answer'] + "</div>";
                    myHTML += "</li>";
                }
                myHTML += "</ol>";
                $('#support-FAQs-holder').html(myHTML);
            }
        }, "json");
        self.pageSwap(getCurrentDisplayedId(), 'js-content-wrapper-support');
    });

    $('#footer-nav-privacypolicy').on('click', function () {
        self.pageSwap(getCurrentDisplayedId(), 'js-content-wrapper-privacypolicy');
    });

    $('#footer-nav-termsofuse').on('click', function () {
        self.pageSwap(getCurrentDisplayedId(), 'js-content-wrapper-termsofuse');
    });
    //*************** END footer events **********************************\\

    //*************** header events **********************************\\

    //// TODO: nav links need to be refactored to be handled automatically... shouldn't be driven by id's like this.. (i blame marcus)
    $('#header-navigation-home').on('click', function () {
        if ($(this).hasClass('current')) { return; }
        $(this).parent().children().removeClass('current');
        $(this).addClass('current');
        self.pageSwap(getCurrentDisplayedId(), 'js-content-wrapper-splash');
    });

    $('#header-navigation-aboutus').on('click', function () {
        if ($(this).hasClass('current')) { return; }
        $(this).parent().children().removeClass('current');
        $(this).addClass('current');
        self.pageSwap(getCurrentDisplayedId(), 'js-content-wrapper-aboutus');
    });

    $('#header-navigation-pricing').on('click', function () {
        if ($(this).hasClass('current')) { return; }
        $(this).parent().children().removeClass('current');
        $(this).addClass('current');
        self.pageSwap(getCurrentDisplayedId(), 'js-content-wrapper-pricing');
    });

    $('#header-navigation-faqs').on('click', function () {
        if ($(this).hasClass('current')) { return; }
        $(this).parent().children().removeClass('current');
        $(this).addClass('current');

        var FAQAjax = $.post("/PHP/AJAX/Support/GetFAQs.php", "SetId=2&HTMLEntities=false&Data=true", function (data) {
            if (data.ResultsFound != null) {
                var myHTML = "<ol id='home-FAQs'>";
                var mySQLObject = null;
                for (var i = 0; i < data['FAQs'].length; i++) {
                    mySQLObject = data['FAQs'][i];
                    myHTML += "<li id='FAQ-" + mySQLObject['FAQCopyId'] + "'>";
                    myHTML += "<div class='support-FAQs-question'>" + mySQLObject['Question'] + "</div>";
                    myHTML += "<div class='support-FAQs-answer'>" + mySQLObject['Answer'] + "</div>";
                    myHTML += "</li>";
                }
                myHTML += "</ol>";
                $('#faqs-FAQs-holder').html(myHTML);
            }
        }, "json");

        self.pageSwap(getCurrentDisplayedId(), 'js-content-wrapper-faqs');
    });

    $('#header-navigation-contactus').on('click', function () {

        if ($(this).hasClass('current')) { return; }
        $(this).parent().children().removeClass('current');
        $(this).addClass('current');

        self.pageSwap(getCurrentDisplayedId(), 'js-content-wrapper-contactus');
    });

    $('#header-navigation-my-info').on('click', function () {
        if ($(this).hasClass('current')) { return; }
        $(this).parent().children().removeClass('current');
        $(this).addClass('current');

        self.pageSwap(getCurrentDisplayedId(), 'js-content-wrapper-my-info');
    });

    $('#header-navigation-study').on('click', function () {
        if ($(this).hasClass('current')) { return; }
        $(this).parent().children().removeClass('current');
        $(this).addClass('current');

        self.pageSwap(getCurrentDisplayedId(), 'js-content-wrapper-practice');
    });

    $('#home-forgot-login').on('click', function () {
        $('#reset-account-form input[type=text]').val('');
        $('#reset-account-form input[type=password]').val('');
        $('#reset-account-form input[name=reset-account-accounttype]:eq(1)').click();
        //get security questions from db
        //TODO
        self.pageSwap(getCurrentDisplayedId(), 'js-content-wrapper-reset-account');
    });


    //*************** END header events **********************************\\

    function getCurrentDisplayedId() {
        var myID = "";
        myID = $('#body').find('div.js-content-wrapper').not(':hidden').attr('id');
        return myID;
    }

}

$.COR.getCurrentDisplayedId = function () {
    var myID = "";
    myID = $('#body').find('div.js-content-wrapper').not(':hidden').attr('id');
    return myID;
}

$.COR.login = function (email, password, successCallback, failcallback) {

    var self = this;

    if(this.offline == false){
        var ph = new $.COR.Utilities.PostHandler({
            service: "account", call: "login",
            params: { email: email, password: $.COR.MD5(password) },
            success: function (data) {

                if (data.Account != null) {
                    self.account.setup(data, successCallback);
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


        self.account.setup(data, successCallback);
    }
}

$.COR.checkLogin = function (successCallback, failCallback) {
    var self = this;


    var ph = new $.COR.Utilities.PostHandler({
        service: "account", call: "autoLogin",
        params: null,
        success: function (data) {

            if (data.Account != null) {
                successCallback(data);
            }
            else {
                failcallback(data.LoginFailedReason);
            }

        }
    });

    ph.submitPost();

    /*
    $.post("/PHP/AJAX/Account/CheckLogin.php", "&Data=true", function (data) {

        if (data.Account != null) {
            successCallback(data);

        }
        else {
            failcallback(data.LoginFailedReason);
        }

    }, "json");
    */
}






// Validation Stuff

$.COR.validateForm = function (formElement) {

    var Validates = true;
    var ElementsToBeValidated = $(formElement).find(".Validate");

    for (var i = 0; i < ElementsToBeValidated.length; i++) {

        var Element = ElementsToBeValidated[i];
        var Validations = $(Element).attr("validate");

        if (typeof Validations !== 'undefined') {

            $(Element).parent().children(".error-message").remove();

            ValidationArray = Validations.split(",");

            var FieldValidates = true;
            for (var j = 0; j < ValidationArray.length; j++) {
                var Result = $(Element).parents("tr").css('display') != "none" ? this.validateField(Element, ValidationArray[j]) : true;
                FieldValidates = FieldValidates == true ? Result : FieldValidates; // If we find one false we need to pass it back
            }

            // No problems with that field, remove any previous errors
            if (!FieldValidates) {
                Validates = Validates == true ? FieldValidates : Validates;
            }
        }

    }

    return Validates;

}

$.COR.validateField = function (Element, Validation) {
    var result = true;
    var message;


    var Value = $.trim($(Element).val());

    switch (Validation) {
        case "AlphaNumeric":
            result = Value.length > 0 ? /^[A-Za-z\d\s]+$/.test(Value) : true;
            message = "Alpha Numeric Only";
            break;
        case "Email":
            result = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/.test(Value);
            message = "Invalid Email";
            break;
        case "Match":
            result = $.trim($(Element).val()) == $.trim($("#" + $(Element).attr("ValidationMatch")).val());
            message = "Does Not Match"
            break;
        case "MatchPassword":
            result = $.COR.MD5($.trim($(Element).val())) == $.trim($("#" + $(Element).attr("ValidationMatch")).val());
            message = "Incorrect Password"
            break;
        case "MinLength":
            result = $.trim($(Element).val()).length >= parseInt($(Element).attr("ValidationMinLength"));
            message = "Min Length (" + $(Element).attr("ValidationMinLength") + ")";
            break;
        case "ValidLength":
            result = $.trim($(Element).val()).length >= parseInt($(Element).attr("ValidationValidLength"));
            message = "Invalid Number";
            break;
        case "Name":
            result = Value.length > 0 ? /^[A-Za-z\'\-\d\s]+$/.test(Value) : true; // Name is AlphaNumeric and allows for apostraphe and dash
            message = "Invalid Characters";
            break;
        case "Numeric":
            result = Value.length > 0 ? /^[\d\s]+$/.test(Value) : true;
            message = "Numeric Only";
            break;
        case "NumericMin":
            var NumericMin = parseInt($(Element).attr("numericmin"));
            result = parseInt(Value) >= NumericMin;
            message = "Must be greater than " + NumericMin;
            break;
        case "Required":
            result = /\S/.test($.trim($(Element).val()));
            message = "Required";
            break;

        default:
            alert("Unknown input error, please check your inputs and try again");
            console.log("Could not find regex for : " + Validation);
            return;
    }

    if (!result) {
        $(Element).parent().children(".error-message").remove();
        $(Element).parents("td").append("<span class='error-message'>" + message + "</span>");
    }

    return result;
};

$.COR.cycleButton = function (buttonElement, cycleName, originalName, fadeoutTime, cycleNameTime) {

    fadeoutTime = fadeoutTime | 1000;
    cycleNameTime = cycleNameTime | 1000;

    $(buttonElement).fadeOut(fadeoutTime, function () {
        $(buttonElement).html(cycleName);
        $(buttonElement).fadeIn(function () {
            setTimeout(function () {
                $(buttonElement).hide();
                $(buttonElement).html(originalName).fadeIn()
            }, cycleNameTime);
        });
    });


    return buttonElement;
};

// Helper Functions

$.COR.toggleAccountNavigation = function () {

    // Setup the navigation to change to the account set
    $("#header-navigation li").each(function (index, element) {

        $(element).hide();

        if ($(element).hasClass("account")) {
            $(element).show();
        }

    });

    $("#header-navigation-study").trigger('click');
};

$.COR.toggleHomeNavigation = function () {

    // Setup the navigation to change to the home / logged out set
    $("#header-navigation li").each(function (index, element) {

        $(element).show();

        if ($(element).hasClass("account-only")) {
            $(element).hide();
        }

    });

    $("#header-navigation li").removeClass('current');
    $("#header-navigation_").addClass('current');
};

$.COR.toggleAccountLogin = function () {

    // Will hide and show the login w/logout link
};


// Stopwatch plugin
(function ($) {
    $.fn.stopwatch = function (theme) {
        var stopwatch = $(this);
        stopwatch.addClass('stopwatch').addClass(theme);

        stopwatch.each(function () {
            var instance = $(this);
            var timer = 0;

            var stopwatchFace = $('<div>').addClass('the-time');
            var timeHour = $('<span>').addClass('hr').text('00:');
            var timeMin = $('<span>').addClass('min').text('00:');
            var timeSec = $('<span>').addClass('sec').text('00');

            var startStopBtn = $('<a>').attr('href', '').addClass('start-stop').text('Start');
            var resetBtn = $('<a>').attr('href', '').addClass('reset').text('Reset');
            stopwatchFace = stopwatchFace.append(timeHour).append(timeMin).append(timeSec);
            instance.html('').append(stopwatchFace).append(startStopBtn).append(resetBtn);

            $(startStopBtn).hide();
            $(resetBtn).hide();

            startStopBtn.bind('click', function (e) {
                e.preventDefault();
                var button = $(this);
                if (button.text() === 'Start') {
                    timer = setInterval(runStopwatch, 1000);
                    button.text('Stop');
                } else {
                    clearInterval(timer);
                    button.text('Start');
                }
            });

            resetBtn.bind('click', function (e) {
                e.preventDefault();
                clearInterval(timer);
                startStopBtn.text('Stop');
                timer = 0;
                timeHour.text('00:');
                timeMin.text('00:');
                timeSec.text('00');
            });

            function runStopwatch() {
                // We need to get the current time value within the widget.
                var hour = parseFloat(timeHour.text());
                var minute = parseFloat(timeMin.text());
                var second = parseFloat(timeSec.text());

                second++;

                if (second > 59) {
                    second = 0;
                    minute = minute + 1;
                }

                if (minute > 59) {
                    minute = 0;
                    hour = hour + 1;
                }

                timeHour.html(("0".substring(hour >= 10) + hour) + ":");
                timeMin.html(("0".substring(minute >= 10) + minute) + ":");
                timeSec.html("0".substring(second >= 10) + second);
            }
        });
    }
})(jQuery);


// Pubty Utils

$.COR.getDisplayNameById = function (languageId) {
    for (var i = 0; i < Global_Languages.length; i++) {
        if (Global_Languages[i].Value == languageId) {
            return Global_Languages[i].Name;
        }

    }
};


// Random Utils ( Should move to COR.utlities.

$.COR.MD5 = function (string) {

    function RotateLeft(lValue, iShiftBits) {
        return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
    }

    function AddUnsigned(lX, lY) {
        var lX4, lY4, lX8, lY8, lResult;
        lX8 = (lX & 0x80000000);
        lY8 = (lY & 0x80000000);
        lX4 = (lX & 0x40000000);
        lY4 = (lY & 0x40000000);
        lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
        if (lX4 & lY4) {
            return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
        }
        if (lX4 | lY4) {
            if (lResult & 0x40000000) {
                return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
            } else {
                return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
            }
        } else {
            return (lResult ^ lX8 ^ lY8);
        }
    }

    function F(x, y, z) { return (x & y) | ((~x) & z); }
    function G(x, y, z) { return (x & z) | (y & (~z)); }
    function H(x, y, z) { return (x ^ y ^ z); }
    function I(x, y, z) { return (y ^ (x | (~z))); }

    function FF(a, b, c, d, x, s, ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    };

    function GG(a, b, c, d, x, s, ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    };

    function HH(a, b, c, d, x, s, ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    };

    function II(a, b, c, d, x, s, ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    };

    function ConvertToWordArray(string) {
        var lWordCount;
        var lMessageLength = string.length;
        var lNumberOfWords_temp1 = lMessageLength + 8;
        var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
        var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
        var lWordArray = Array(lNumberOfWords - 1);
        var lBytePosition = 0;
        var lByteCount = 0;
        while (lByteCount < lMessageLength) {
            lWordCount = (lByteCount - (lByteCount % 4)) / 4;
            lBytePosition = (lByteCount % 4) * 8;
            lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount) << lBytePosition));
            lByteCount++;
        }
        lWordCount = (lByteCount - (lByteCount % 4)) / 4;
        lBytePosition = (lByteCount % 4) * 8;
        lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
        lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
        lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
        return lWordArray;
    };

    function WordToHex(lValue) {
        var WordToHexValue = "", WordToHexValue_temp = "", lByte, lCount;
        for (lCount = 0; lCount <= 3; lCount++) {
            lByte = (lValue >>> (lCount * 8)) & 255;
            WordToHexValue_temp = "0" + lByte.toString(16);
            WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length - 2, 2);
        }
        return WordToHexValue;
    };

    function Utf8Encode(string) {
        string = string.replace(/\r\n/g, "\n");
        var utftext = "";

        for (var n = 0; n < string.length; n++) {

            var c = string.charCodeAt(n);

            if (c < 128) {
                utftext += String.fromCharCode(c);
            }
            else if ((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            }
            else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }

        }

        return utftext;
    };

    var x = Array();
    var k, AA, BB, CC, DD, a, b, c, d;
    var S11 = 7, S12 = 12, S13 = 17, S14 = 22;
    var S21 = 5, S22 = 9, S23 = 14, S24 = 20;
    var S31 = 4, S32 = 11, S33 = 16, S34 = 23;
    var S41 = 6, S42 = 10, S43 = 15, S44 = 21;

    string = Utf8Encode(string);

    x = ConvertToWordArray(string);

    a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;

    for (k = 0; k < x.length; k += 16) {
        AA = a; BB = b; CC = c; DD = d;
        a = FF(a, b, c, d, x[k + 0], S11, 0xD76AA478);
        d = FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
        c = FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
        b = FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
        a = FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
        d = FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
        c = FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
        b = FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
        a = FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
        d = FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
        c = FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
        b = FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
        a = FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
        d = FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
        c = FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
        b = FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
        a = GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
        d = GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
        c = GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
        b = GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
        a = GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
        d = GG(d, a, b, c, x[k + 10], S22, 0x2441453);
        c = GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
        b = GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
        a = GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
        d = GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
        c = GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
        b = GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
        a = GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
        d = GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
        c = GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
        b = GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
        a = HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
        d = HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
        c = HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
        b = HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
        a = HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
        d = HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
        c = HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
        b = HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
        a = HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
        d = HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA);
        c = HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
        b = HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
        a = HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
        d = HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
        c = HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
        b = HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
        a = II(a, b, c, d, x[k + 0], S41, 0xF4292244);
        d = II(d, a, b, c, x[k + 7], S42, 0x432AFF97);
        c = II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
        b = II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
        a = II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
        d = II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
        c = II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
        b = II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
        a = II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
        d = II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
        c = II(c, d, a, b, x[k + 6], S43, 0xA3014314);
        b = II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
        a = II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
        d = II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
        c = II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
        b = II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
        a = AddUnsigned(a, AA);
        b = AddUnsigned(b, BB);
        c = AddUnsigned(c, CC);
        d = AddUnsigned(d, DD);
    }

    var temp = WordToHex(a) + WordToHex(b) + WordToHex(c) + WordToHex(d);

    return temp.toLowerCase();
}


$.COR.Utilities.PostHandler = function (options) {

    this.params = new Array();
    this.service = options.service || null;
    this.call = options.call || null;

    this.succesCallback = options.success || null;
    this.errroCallback = options.error || null;

    this.addParam = function (name, value) {
        var ParamObject = new Object();
        ParamObject.name = name;
        ParamObject.value = value;
        this.params.push(ParamObject);
    }

    this.submitPost = function () {

        var data = new Object();
        data.service = this.service;
        data.call = this.call;

        for (var i = 0; i < this.params.length; i++) {
            var ParamObj = this.params[i];
            data[ParamObj.name] = ParamObj.value;
        }

        if ($("#account-hash").length > 0) {
            data['Hash'] = $("#account-hash").val();
        }

        $.ajax({
            type: "POST",
            url: "/PHP/services.php",
            data: data,
            success: this.succesCallback,
            error: function () {

                // On server error show DC Box assuming we haven't already shown it!
                if ($('#ServerErrorHandler').css("top") == -171) {
                    $('#ServerErrorHandler').animate({ top: '+=131' }, 1000);
                }

                if (typeof (this.errorCallback) == "function") {
                    this.errorCallback();
                }
            },
            dataType: 'json'
        });

        /*
        $.post("/PHP/services.php", data, function () {

        }, 'json');
        */
    }


    if (options.params != null) {
        for (var key in options.params) {
            this.addParam(key, options.params[key]);
        }
    }

}


