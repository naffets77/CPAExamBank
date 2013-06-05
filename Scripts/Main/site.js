
window.onblur = function () {  window.blurred = true; };
window.onfocus = function () { window.blurred = false; };


var hashHandler = {

    account: function () {
        $.COR.account.hashHandler();
    }

};

$(document).ready(function(){





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
                $.COR.pageSwap(null, "home");
            }
        }
        else {
            $.COR.pageSwap(null, "home");
        }
    });

    $(window).hashchange();

});




if (!Date.now) {
    Date.now = function now() {
        return +(new Date);
    };
}