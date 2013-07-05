


$(document).on('ready', function () {


    $("#login").on("click", function () {
        //$("#login-wrapper").hide();
        //$("#search-wrapper").fadeIn();
        //return;


        $("#invalid-account-message").hide();

        var password = $("#login-password").val();
        var email = $("#login-username").val();

        if (password.length != 0 && email.length != 0 && password != 'Password' && email != 'Email') {

            $.COR.services.login(
                email,
                password,
                function () { // success function

                    $("#login-wrapper").hide();
                    $("#search-wrapper").fadeIn();


                },
                function (failedReason) { // failure function (invalid email/password etc)
                    $("#invalid-login").show();
                }
            );
        } else {
            $("#invalid-login").show();
        }

    });

    $("#search").on('click', function () {

        var ph = new $.COR.Utilities.PostHandler({
            service: "question", call: "getAllQuestionsAndAnswers",
            params: {},
            success: function (data) {
                console.log(data);

            }
        });

        ph.submitPost();


    });



});


function appendSearchResultsRow(QuestionData) {



}
