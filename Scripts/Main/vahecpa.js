
$(document).on('ready', function () {

    $(".register-sign-up").on('click', function () {

        $.COR.TPrep.showFullScreenOverlay(
            $("#js-overlay-register").html(),
            $("#js-overlay-register").attr("contentSize"), function () {

                $(".js-overlay-close").on('click', function () {
                    $.COR.TPrep.hideFullScreenOverlay();
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