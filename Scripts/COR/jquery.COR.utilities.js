


$.COR.Utilities.cycleButton = function (buttonElement, cycleName, originalName, fadeoutTime, cycleNameTime) {

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

// Post Handler Utility

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

        var self = this;

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
            success: function (data) {
                self.succesCallback(data);
            },
            error: function () {

                // On server error show DC Box assuming we haven't already shown it!
                if ($('#ServerErrorHandler').css("top") == "-190px") {
                    $('#ServerErrorHandler').animate({ top: '+=131' }, 1000);
                }

                if (typeof (this.errorCallback) == "function") {
                    this.errorCallback();
                }
            },
            dataType: 'json'
        });
    }


    if (options.params != null) {
        for (var key in options.params) {
            this.addParam(key, options.params[key]);
        }
    }

}



// Poll Handler Utility (Basically just a wrapper around the Post Handler to call it repeatedly based on an interval)

$.COR.Utilities.PollHandler = function (options) {

   this.ph = new $.COR.Utilities.PostHandler({
       service: options.service,
       call: options.call,
       params: options.params,
       success: options.sucess,
       error: options.error
    });

   this.interval = options.interval || 10000;
   this.intervalReference = null;
}

$.COR.Utilities.PollHandler.prototype.start = function () {

    var self = this;

    this.intervalReference = setInterval(function () {
        self.ph.submitPost();
    }, this.interval);
}

$.COR.Utilities.PollHandler.prototype.stop = function () {

    clearInterval(this.intervalReference);
}

$.COR.Utilities.PollHandler.prototype.updateInterval = function (newInterval) {
    this.interval = newInterval;
}



// Refresh Login - Relogins user on refresh if valid session

$.COR.Utilities.refreshLogin = function (successCallback) {

    $.COR.checkLogin(

        // Sucessfully still logged in
        function (data) {
            $.COR.toggleAccountNavigation();
            $.COR.account.setup(data, function () {
                
                successCallback();
                //$.COR.pageSwap("js-content-wrapper-splash", "js-content-wrapper-user-account");
            });
        },

        // Failed Login - Send Back To Home
        function (reason) {

            $.COR.toggleHomeNavigation();
            $.COR.pageSwap($.COR.getCurrentDisplayedId(), "js-content-wrapper-splash");
            console.log("Didn't log you in because : " + reason);
        }
    );
}


// Popup handler

$.COR.Utilities.showFullScreenOverlay = function (content, contentClassSize, events) {


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

$.COR.Utilities.hideFullScreenOverlay = function () {

    $("#full-screen-container").fadeOut(function () {
        $("#full-screen-overlay").fadeOut(200);
    });
}