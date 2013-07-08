
$(document).on('ready', function () {

    $(".register-sign-up").on('click', function () {

        $.COR.TPrep.showFullScreenOverlay(
            $("#js-overlay-register").html(),
            $("#js-overlay-register").attr("contentSize"), function () {

                $(".js-overlay-close").on('click', function () {
                    $.COR.TPrep.hideFullScreenOverlay();
                });

                $(".registration-finish-button").on("click", function (e) {

                    var clickedElement = this;
                    var originalHTML = $(this).html();
                    e.preventDefault();

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
                                $("#registration-finish-button").html(originalHTML);
                            }
                            else {
                                // We're good to go lets setup the account object and change pages...


                                COR.account.setup(data, function () {
                                    self.toggleAccountNavigation();
                                    location.hash = "account";
                                });
                            }
                        });

                        

                        /* 
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
                        */

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