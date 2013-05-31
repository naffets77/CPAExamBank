GLOBLA_INITIAL_LANGUAGE_ID = 1;
GLOBAL_LANGUAGE_JSON_HOLDER = []; // Caches Language Objects


Global_Languages = [{Name:"English",Value:1},{Name:"Spanish",Value:2}, {Name:"French", Value:6}];


window.onblur = function () {  window.blurred = true; };
window.onfocus = function () { window.blurred = false; };


var hashHandler = {

    account: function () {
        $.COR.account.hashHandler();
    }

};

$(document).ready(function(){





    // Initialization
    // $.COR.pageEvents();



    $(window).hashchange(function () {


        // Any popups that are showing need to be hidden
        $.COR.Utilities.PopupHandler.hide();


        if( location !== undefined){

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
        else{
            $.COR.pageSwap(null, "home");
        }
    })

    $(window).hashchange();




    /*
    $.COR.matching.setupEvents();




    
    // Setup Localization Dropdown
    $('#LocalizationOption').ddslick({
        data: ddData,
        width: 200,
        imagePosition: "left",
        selectText: "Select Language",
        onSelected: function (data) {
            console.log(data);
        }
    });
        
    // Handle Changing Languages
    $("#LocalizationOption").on("change",function(){
       
       var LanguageId = $(this).val();
       
       if(GLOBAL_LANGUAGE_JSON_HOLDER[LanguageId] != undefined){          
           $('body').fadeOut('slow',function(){
                LocalizationReplacement(LanguageId);
               $('body').fadeIn();
           });
       }
       else{
           $('body').fadeOut('slow', function(){
               $.getScript("/PHP/Localization/LocLanguageAsJSON.php?lid=" + LanguageId, function(){
                   LocalizationReplacement(LanguageId);
                   $('body').fadeIn();    
               });
           });
       }
    });
      
    SetupDropdowns();
    */
});



function SetupDropdowns(){
                                
    var Languages = ddData;
    var Proficiencies = ddProficiencyData;
    var LanguagesHTML = "";
    var ProficienciesHTML = "";
    
    for(var i = 0; i < Languages.length; i++){
        LanguagesHTML += "<option value='" + Languages[i].value + "' class='loc-" + Languages[i].text.replace(" ","-") + "'>" + Languages[i].text + "</option>";
    }
    
    for(var i = 0; i < Proficiencies.length; i++){
        ProficienciesHTML += "<option value='" + Proficiencies[i].value + "' class='loc-" + Proficiencies[i].text.replace(" ","-") + "'>" + Proficiencies[i].text + "</option>";
    }
    
    
    $(".languages-holder").each(function(index, item){
           $(item).html(LanguagesHTML);
    });
    
    $(".proficiencies-holder").each(function(index, item){
           $(item).html(ProficienciesHTML);
    }); 
}

// Assumed LanguageId is in the GLOBAL JSON HOLDER - Does the replacement for localization
function LocalizationReplacement(LanguageId){
    
    var JSONObj = GLOBAL_LANGUAGE_JSON_HOLDER[LanguageId];
   
    for(var i = 0; i < JSONObj.SiteCopy.length; i ++){
        var CopyObj = JSONObj.SiteCopy[i];
        $("#" + CopyObj.Key).html(CopyObj.Value);   
    }     
}


if (!Date.now) {
    Date.now = function now() {
        return +(new Date);
    };
}