
$(document).on('ready', function () {

    $(".register-sign-up").on('click', function () {

        $.COR.TPrep.showFullScreenOverlay(
            $("#js-overlay-register").html(),
            $("#js-overlay-register").attr("contentSize"), function () {

                $(".register-close").on('click', function () {
                    $.COR.TPrep.hideFullScreenOverlay();
                });

            });
    });




});