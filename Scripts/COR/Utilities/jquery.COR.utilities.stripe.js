

$.COR.Utilities.Stripe = {

    init: function (options) {
        if (options.key) {
            Stripe.setPublishableKey(options.key);
        }
        else {
            alert("Stripe requires a publishable key");
        }
    },

    createToken : function(options){

        Stripe.card.createToken({
            number: options.CardNumber,
            cvc: options.CVC,
            exp_month: options.ExpirationMonth,
            exp_year: options.ExpirationYear
        }, function (status, response) {

            if (response.error) {

                if (typeof options.failCallback == "function") {
                    options.failCallback(response);
                    $.COR.log("Stripe Failed: " + response.error.message);
                }

            } else {

                if (typeof options.successCallback == "function") {
                    // token contains id, last4, and card type
                    options.successCallback(response['id'], response);
                }
            }

        });

    },

    validateForm: function (options) {

        var result = true;


        if (!stripe.validateCVC(options.CVC.value)) {
            $("#" + options.CVC.id).parent().append("<span class='error-message'>Invalid</span>");
            result = false;
        }

        if (!stripe.validateCardNumber(options.CardNumber.value)) {
            $("#" + options.CardNumber.id).parent().append("<span class='error-message'>Invalid</span>");
            result = false;
        }

        if (!stripe.validateDate(options.expirationDate.value)) {
            $("#" + options.expirationDate.id).parent().append("<span class='error-message'>Invalid</span>");
            result = false;
        }
        
        return result;
    }

};