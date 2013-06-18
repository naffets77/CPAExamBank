
window.onblur = function () {  window.blurred = true; };
window.onfocus = function () { window.blurred = false; };


var hashHandler = {

    account: function () {
        $.COR.account.hashHandler();
    }

};

$(document).ready(function(){


    var urlVars = getUrlVars();

    if (urlVars.offline != undefined && urlVars.offline == 'true') {
        $.COR.account.offline = true;
    }




    // Initialization
    $.COR.pageEvents();



    $(window).hashchange(function () {


        // Any popups that are showing need to be hidden
        $.COR.Utilities.PopupHandler.hide();


        if (location !== undefined) {

            var loc = location.hash.replace("#", "");

            if (typeof hashHandler[loc] === 'function') {

                hashHandler[loc](
                    function () { getPage(loc) }
                );
            }
            else {
                $.COR.toggleHomeNavigation();
                $.COR.pageSwap(null, "home");
            }
        }
        else {
            $.COR.toggleHomeNavigation();
            $.COR.pageSwap(null, "home");
        }
    });

    $(window).hashchange();

});




$.COR.TPrep = {};

$.COR.TPrep.showFullScreenOverlay = function (content, contentClassSize, events) {


    if ($("#full-screen-container").is(":visible")) {

        $("#full-screen-container .content").fadeOut(function () {
            $(this).html("");

            $("#full-screen-container .content").html(content).removeClass().addClass(contentClassSize).fadeIn();

            if (typeof events == 'function') {
                events();
            }
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
    }
}

$.COR.TPrep.hideFullScreenOverlay = function () {
    $("#full-screen-container").fadeOut();
}


if (!Date.now) {
    Date.now = function now() {
        return +(new Date);
    };
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