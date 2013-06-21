
window.onblur = function () {  window.blurred = true; };
window.onfocus = function () { window.blurred = false; };


var hashHandler = {

    account: function () {
        $.COR.account.hashHandler();
    },
    // have to use " " so i can have the hyphen... js pro move
    "start-practice": function () {
        $.COR.account.startStudy();
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

            if (loc != "") {
                if (typeof hashHandler[loc] === 'function') {
                    hashHandler[loc]();
                }
                else {

                    var parts = loc.split("/");
                    
                    if (parts.length == 1) {
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
                    }
                    else {
                        // It's a subpage!
                        //console.log("show: " + parts[0] + " @ " + parts[1]);

                        /*
                            Subpages work by using the #part1/part2 to build the content id that is shown : id='part1_part2'
                            In order to have multiple sub pages that show and hide, we assume that they are all on the same branch, 
                            so we can go to the parent hide everyone at that level, then show the one that we want to see...

                            Should work ... and scale to even deeper levels if needed!
                        */

                        var element = $("#" + parts[0] + "_" + parts[1]);
                        $(element).parent().children().addClass("hidden");
                        $(element).removeClass("hidden");

                    }
                }
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