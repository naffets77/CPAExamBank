


// Popup handler Utility
$.COR.Utilities.PopupHandler.init = function (popupContent, completeFunctionCallback) {

    this.reset();
    this.events();
    this.onComplete = completeFunctionCallback;

    $("#popup-content-holder").append(popupContent);


    $("#popup-content_0").hide();
    $("#popup-content_1").fadeIn();
    $("#popup-nav-holder").fadeIn();


};

$.COR.Utilities.PopupHandler.hide = function () {

    $("#popup-overlay").hide();
    $("#popup-container").hide();
}

$.COR.Utilities.PopupHandler.reset = function () {

    // TODO: remove popup content except if id = 0



    $("#popup-nav-holder .popup-next").off("click");
    $("#popup-nav-holder .popup-previous").off("click");
};

$.COR.Utilities.PopupHandler.checkForValidation = function (currentId, continueCallback) {

    if ($("#popup-content_" + currentId).hasClass("Validate")) {

        if ($.COR.validateForm($("#popup-content_" + currentId + " form"))) {
            continueCallback();
        }
    }
    else {
        continueCallback();
    }


};

$.COR.Utilities.PopupHandler.events = function () {

    var self = this;

    $("#popup-nav-holder .popup-next").on('click', function () {

        console.log("Next");

        var current = $("#popup-content-holder .popup-content").filter(":visible").attr('id').split("_")[1];
        var last = $("#popup-content-holder .popup-content").last().attr('id').split("_")[1];




        self.checkForValidation(current, function () {

            // Determine if we're on the first
            parseInt(current) + 1 > 1 ? $("#popup-nav-holder .popup-previous").show() : $("#popup-nav-holder .popup-previous").hide();

            // Determine if we're on the last
            if (current == last) {
                self.onComplete();
            }
            else {
                $("#popup-content-holder .popup-content").filter(":visible").hide();
                $("#popup-content_" + (parseInt(current) + 1)).fadeIn();
            }
        });

    });

    $("#popup-nav-holder .popup-previous").on('click', function () {

        console.log("Prev");

        var current = $("#popup-content-holder .popup-content").filter(":visible").attr('id').split("_")[1];
        var last = $("#popup-content-holder .popup-content").last().attr('id').split("_")[1];

        // Determine if we're on the first
        if (parseInt(current) - 1 == 1) {
            $("#popup-nav-holder .popup-previous").hide();
        }

        // Determine if we're on the last
        //if (current == last) {
        //    this.onComplete();
        //}

        $("#popup-content-holder .popup-content").filter(":visible").hide();
        $("#popup-content_" + (parseInt(current) - 1)).fadeIn();

    });
}

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