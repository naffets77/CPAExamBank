var offline = false;


$(document).on('ready', function () {


    $("#login").on("click", function () {

        if (offline == true) {
            $("#login-wrapper").hide();
            $("#search-wrapper").fadeIn();
            return;
        }


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

        if (offline == true) {
            appendSearchResultsRow(getDummyResultRowData());
            return;
        }


        var ph = new $.COR.Utilities.PostHandler({
            service: "question", call: "getAllQuestionsAndAnswers",
            params: {},
            success: function (data) {
                console.log(data);

                for (var i = 0 ; i < data.QuestionResponses.length; i++) {
                    appendSearchResultsRow(data.QuestionResponses[i];
                }

            }
        });

        ph.submitPost();


    });



});


function appendSearchResultsRow(QuestionData) {
    $("#results tbody").html("");

    var row = "<tr>" +
                "<td>1</td>" +
                "<td>Section1S3</td>" +
                "<td>AUD</td>" +
                "<td>No</td>"+
                "<td class='question'>" + QuestionData.Question + "</td><td class='answers'><ul>";


    for (var i = 0; i < QuestionData.Answers.length; i++) {

        var correctClass = "";

        if (i == QuestionData.CorrectAnswerIndex) {
            correctClass = " class='correct' ";
        }

        row += "<li" + correctClass + ">" + QuestionData.Answers[i].DisplayText + "</li>";
    }


    row += "</ul></td></tr>";

    $("#results tbody").append(row);


}

function getDummyResultRowData() {
    return {
        CorrectAnswerIndex: 0,
        Explanation: "<p>The answer is option A. Signing of payrolls checks is a management function which  impairs the independence of the CPA</p><p></p><p></p><p></p><p></p><p></p>",
        Question: "<p>What procedure should be followed to detect frauds that occur while updating checking account balances and print out details of overdrawn accounts when computer programmer never print overdrawn accounts?</p><p></p><p></p><p></p><p></p><p></p>",
        Answers: [
                {
                    DisplayText: "Master file of Checking account balances should be totalled through running control total and compared with printout.",
                },
                {
                    DisplayText: "Testing the client's program and verfication of the subsidiary file with use of test Date approach by the author",
                },
                {
                    DisplayText: "A program check  of Valid customer code",
                },
                {
                    DisplayText: "Compilation from documented source files on periodic basis and comparison with current programs in use.",
                }

            ]
    }

}
