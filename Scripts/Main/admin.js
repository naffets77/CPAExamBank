var offline = false;


$(document).on('ready', function () {

    new TINY.editor.edit('editor', {
        id: 'question-editor',
        width: 860,
        height: 100,
        cssclass: 'te',
        controlclass: 'tecontrol',
        rowclass: 'teheader',
        dividerclass: 'tedivider',
        controls: ['bold', 'italic', 'underline', 'strikethrough', '|', 'subscript', 'superscript', '|',
                  'orderedlist', 'unorderedlist', '|', 'outdent', 'indent', '|', 'leftalign',
                  'centeralign', 'rightalign', 'blockjustify', '|', 'unformat', '|', 'undo', 'redo', 'n',
                  'size', 'style', '|', 'image', 'hr', '|', 'cut', 'copy', 'paste', 'print'],
        footer: true,
        fonts: ['Verdana', 'Arial', 'Georgia', 'Trebuchet MS'],
        xhtml: true,
        cssfile: '/Scripts/Plugins/tinyeditor/style.css',
        bodyid: 'editor',
        footerclass: 'tefooter',
        toggle: { text: 'source', activetext: 'wysiwyg', cssclass: 'toggle' },
        resize: { cssclass: 'resize' }
    });

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

                $("#results tbody").html("");

                for (var i = 0 ; i < data.QuestionResponses.length; i++) {
                    appendSearchResultsRow(data.QuestionResponses[i]);
                }

            }
        });

        ph.submitPost();


    });

    $("#search-helper-specific-search").on('change', function () {

        if ($(this).val() == 0) {
            $("#search-helper-specific-search-input").hide();
        }
        else {
            $("#search-helper-specific-search-input").show();
        }

    });

    // Question Wrapper Events

    $("#back-to-search").on('click', function () {

        $("#question-wrapper").hide();
        $("#search-wrapper").fadeIn();
    });

    $("#question-manager-navigation li").on('click', function () {

        if ($(this).hasClass('current')) { return; }

        $("#question-manager-navigation li").removeClass('current');

        $(this).addClass('current');

        $(".question-manager-view").hide();

        $("#question-manager_" + $(this).html()).fadeIn();
    });


    $('input:radio[name="edit-option"]').on('change', function () {
        editor.i.contentWindow.document.body.innerHTML = $(this).parents('tr').find('label').html().trim();
    });

    $("#question-copy").on('click', function () {

        $.COR.Utilities.showFullScreenOverlay(
            $("#js-overlay-copy-question").html(),
            $("#js-overlay-copy-question").attr("contentSize"), function () {

                $(".js-overlay-close").on('click', function () {
                    $.COR.Utilities.hideFullScreenOverlay();
                });

            });
    });


    // Editor updater
    setInterval(function () {
        if ($("#question-manager_Edit").is(":visible")) {

            // get radio button selected html
            var radio = $('input:radio[name="edit-option"]:checked');

            // get html for radio button
            var html = $(radio).parents('tr').find('label').html().trim();

            // get html for text editor
            editor.post();
            var editorHtml = editor.t.value.trim();

            if (editorHtml != html) {
                $(radio).parents('tr').find('label').html(editorHtml);
            }
        }
    }, 50);
    
});


function appendSearchResultsRow(QuestionData) {
    

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


    $("#results tr").on('click', function () {
        $("#search-wrapper").fadeOut(function () {
            $("#question-wrapper").fadeIn();
        });

    });

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
