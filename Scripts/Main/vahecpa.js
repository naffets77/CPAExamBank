
$(document).on('ready', function () {

    $(".register-sign-up").on('click', function () {

        $.COR.TPrep.showFullScreenOverlay(
            $("#js-overlay-register").html(),
            $("#js-overlay-register").attr("contentSize"), function () {

                $(".js-overlay-close").on('click', function () {
                    $.COR.TPrep.hideFullScreenOverlay();
                });

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
                                // We're good to go lets setup the account object and change pages...


                                $.COR.account.setup(response, function () {
                                    $.COR.toggleAccountNavigation(); // TODO: This should be done on the account side of things
                                    $.COR.TPrep.hideFullScreenOverlay();
                                    location.hash = "account";
                                });
                            }
                        });

                    }


                });


            });
    });




    $("#faq-holder .section ul li a").on('click', function () {

        $("#faq-holder .section ul li a").removeClass("active");

        $(this).addClass('active');

    });


    $("#pricing-holder .squaredTwo label").on('click', function () {
        var amount = 0;


        // This is necessary because the checkbox element doesn't change it's checked state till after this event occurs, so wait for that
        setTimeout(function () {

            $("#pricing-holder .squaredTwo input").each(function (index, element) {
                if ($(element).prop("checked")) {
                    amount += 5;
                }
            });

            $("#pricing-total .amount").html(amount);
        }, 50);

    });




});