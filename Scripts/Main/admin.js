var offline = false;

var QuestionResponses = null;

$(document).on('ready', function () {

    setupTinyEditor();

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
            QuestionResponses = [];

            QuestionResponses.push(getDummyResultRowData());
            buildSearchResults(QuestionResponses);
            
            return;
        }


        if (QuestionResponses == null) {
            var ph = new $.COR.Utilities.PostHandler({
                service: "question", call: "getAllQuestionsAndAnswers_Manager",
                params: {},
                success: function (data) {

                    QuestionResponses = data.QuestionResponses;
                    buildSearchResults(QuestionResponses);


                }
            });

            ph.submitPost();
        }
        else {
            buildSearchResults(QuestionResponses);
        }


    });

    $("#search-helper-specific-search").on('change', function () {

        if ($(this).val() == 0) {
            $("#search-helper-specific-search-input").hide();
            $(".filter-option").show();
        }
        else {
            $("#search-helper-specific-search-input").show();
            $(".filter-option").hide();
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

                // Question Copy
                $("#full-screen-container .btn-orange").on('click', function () {

                    if ($(this).hasClass('disabled')) { return; }
                    $(this).addClass('disabled');

                    $(this).html("Copying...");

                    var ph = new $.COR.Utilities.PostHandler({
                        service: "question", call: "copyQuestion",
                        params: {
                            QuestionId: $("#edit-question-id").html()
                        },
                        success: function (data) {

                            // Add question to array
                            QuestionResponses.push(data.QuestionResponse[0]);

                            // Remove client reference ID
                            $("#edit-question-image").html("");

                            // Set current question ID
                            $("#edit-question-id").html(data.QuestionResponse[0].QuestionId);

                            // Reset
                            $(this).html("Continue").removeClass('disabled');

                            // Hide Popup
                            $.COR.Utilities.hideFullScreenOverlay();

                            

                        }
                    });

                    ph.submitPost();

                });


            });
    });

    $("#edit-save-all").on('click', function () {
        if ($(this).hasClass('disabled')) { return; }
        $(this).addClass('disabled').html("Saving...");


        var self = this;

        var QuestionResponseDTO = {
            QuestionId: $("#edit-question-id").html(),
            CorrectAnswerIndex: parseInt($("#edit-correct-answer-index").val()) - 1, // change back to 0 indexed
            QuestionClientId: $("#edit-qrid").val(),
            QuestionClientImage: $("#edit-question-image").val(),
            IsApprovedForUse: $("#edit-approved").is(":checked") ? "1" : "0",
            IsDeprecated: $("#edit-deprecated").is(":checked") ? "1" : "0",
            IsActive: $("#edit-active").is(":checked") ? "1" : "0",
            Explanation: $("#edit-explanation-value").html(),
            Question: $("#edit-question-value").html(),
            Answers: [
                    {
                        DisplayText: $("#edit-answer-1-value").html(),
                        QuestionToAnswersId: $("#edit-answer-1-value").attr("qaid"),
                        IsAnswerToQuestion: (parseInt($("#edit-correct-answer-index").val()) - 1) == 0 ? "1" : "0"
                    },
                    {
                        DisplayText: $("#edit-answer-2-value").html(),
                        QuestionToAnswersId: $("#edit-answer-2-value").attr("qaid"),
                        IsAnswerToQuestion: (parseInt($("#edit-correct-answer-index").val()) - 1) == 1 ? "1" : "0"
                    },
                    {
                        DisplayText: $("#edit-answer-3-value").html(),
                        QuestionToAnswersId: $("#edit-answer-3-value").attr("qaid"),
                        IsAnswerToQuestion: (parseInt($("#edit-correct-answer-index").val()) - 1) == 2 ? "1" : "0"
                    },
                    {
                        DisplayText: $("#edit-answer-4-value").html(),
                        QuestionToAnswersId: $("#edit-answer-4-value").attr("qaid"),
                        IsAnswerToQuestion: (parseInt($("#edit-correct-answer-index").val()) - 1) == 3 ? "1" : "0"
                    }

            ]
        };

        var ph = new $.COR.Utilities.PostHandler({
            service: "question", call: "updateQuestion",
            params: {
                QuestionUpdateResponse: JSON.stringify(QuestionResponseDTO)
            },
            success: function (data) {

                // Update Cached Questions
                updateQuestion(QuestionResponseDTO);

                // Re-Build Search Results So that Table Is Updated
                buildSearchResults(QuestionResponses);

                // Make sure it's on the screen long enough to see
                setTimeout(function () {
                    $(self).removeClass('disabled').html("Save");
                }, 500);
            }
        });

        ph.submitPost();
        

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


function buildSearchResults(QuestionResponses) {


    $("#results tbody").html("");

    for (var i = 0 ; i < QuestionResponses.length; i++) {

        if (validateSearchResults(QuestionResponses[i])) {
            appendSearchResultsRow(QuestionResponses[i]);
        }
    }

    $("#results tr").on('click', function () {
        console.log("Setting Question: " + $(this).attr("qid"));
        setQuestionData(getQuestionById($(this).attr("qid")));

        $("#search-wrapper").fadeOut(function () {
            $("#question-wrapper").fadeIn();
        });

    });
}

function validateSearchResults(questionResponse) {

    var result = false;
    var filterFound = false;

    // Will do filtering/searching here...
    if ($("#search-helper-specific-search").val() !== "0") {

        filterFound = true;

        var searchValue = $("#search-helper-specific-search-input").val();
        switch ($("#search-helper-specific-search").val()) {
            case "1":
                result = questionResponse.QuestionClientId == searchValue;
                break;
            case "2":
                result = questionResponse.Quesiton.match("/" + searchValue + "/g"); 
                break;
            case "3":
                result = questionResponse.QuestionClientImage.replace("jpeg", "").match(searchValue);
                break;
        }
        
    }
    else {

        if ($("#search-approved").is(":checked")) {
            result = questionResponse.IsApprovedForUse == 1;
            filterFound = true;
        }

        if ($("#search-active").is(":checked")) {
            result = questionResponse.IsActive == 1;
            filterFound = true;
        }

        if ($("#search-deprecated").is(":checked")) {
            result = questionResponse.IsDeprecated == 1;
            filterFound = true;
        }

        if ($("#search-section").val() != "0") {

            filterFound = true;

            if ($("#search-section").val() == questionResponse.SectionTypeId) {
                result = true;
                
            }
        }


    }

    // No filters : Set to true
    if (!filterFound) {
        result = true;
    }

    return result;
}


function appendSearchResultsRow(QuestionData) {
    
    var SectionName = "-";
    switch (QuestionData.SectionTypeId) {
        case "1":
            SectionName = "FAR";
            break;
        case "2":
            SectionName = "AUD";
            break;
        case "3":
            SectionName = "BEC";
            break;
        case "4":
            SectionName = "REG";
            break;
    }

    var Approved = QuestionData.IsApprovedForUse == "1" ? "Yes" : "No";
    var Deprecated = QuestionData.IsDeprecated == "1" ? "Yes" : "No";
    var Active = QuestionData.IsActive == "1" ? "Yes" : "No";

    var row = "<tr qid='" + QuestionData.QuestionId + "'>" +
                "<td>" + QuestionData.QuestionId + "</td>" +
                "<td>" + QuestionData.QuestionClientId + "</td>" +
                "<td>" + SectionName + "</td>" +
                "<td>" + QuestionData.QuestionClientImage + "</td>" +
                "<td>" + Approved + "</td>" +
                "<td>" + Deprecated + "</td>" +
                "<td>" + Active + "</td>" +
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

function updateQuestion(questionResponseDTO) {

    var question = getQuestionById(questionResponseDTO.QuestionId);

    for (var propt1 in question) {
        for (var propt2 in questionResponseDTO) {
            if (propt1 == propt2) {
                question[propt1] = questionResponseDTO[propt2];
            }
        }
    }


}

function getQuestionById(questionId) {

    var q = QuestionResponses;
    var len = q.length;

    var question = null;

    for (var i = 0; i < len; i++) {
        if (q[i].QuestionId == questionId) {
            question = q[i];
            break;
        }
    }

    return question;
}

function setQuestionData(question) {


    // Reset the Radio Button for Question/Answers to the Question Option
    $("input[name='edit-option']").prop("checked", "false");
    $("#edit-question").prop("checked", "true");

    var SectionName = "-";
    switch (question.SectionTypeId) {
        case "1":
            SectionName = "FAR";
            break;
        case "2":
            SectionName = "AUD";
            break;
        case "3":
            SectionName = "BEC";
            break;
        case "4":
            SectionName = "REG";
            break;
    }

    // Read Only
    $("#edit-question-id").html(question.QuestionId);
    $("#edit-section-type").html(SectionName);


    
    // Question Meta Data
    $("#edit-qrid").val(question.QuestionClientId);
    $("#edit-question-image").val(question.QuestionClientImage);
    $("#edit-deprecated").attr('checked', question.IsDeprecated == "0" ? false : true);
    $("#edit-active").attr('checked', question.IsActive == "0" ? false : true);
    $("#edit-approved").attr('checked', question.IsApprovedForUse == "0" ? false : true);
    $("#edit-correct-answer-index").val(parseInt(question.CorrectAnswerIndex) + 1);
    
    // Set Editor defaulting w/the question text
    editor.i.contentWindow.document.body.innerHTML = question.Question;

    // Question Content Data
    $("#edit-question-value").html(question.Question);
    $("#edit-explanation-value").html(question.Explanation);

    $("#edit-answer-1-value").html(question.Answers[0].DisplayText);
    $("#edit-answer-2-value").html(question.Answers[1].DisplayText);
    $("#edit-answer-3-value").html(question.Answers[2].DisplayText);
    $("#edit-answer-4-value").html(question.Answers[3].DisplayText);

    $("#edit-answer-1-value").attr("qaid", question.Answers[0].QuestionToAnswersId);
    $("#edit-answer-2-value").attr("qaid", question.Answers[1].QuestionToAnswersId);
    $("#edit-answer-3-value").attr("qaid", question.Answers[2].QuestionToAnswersId);
    $("#edit-answer-4-value").attr("qaid", question.Answers[3].QuestionToAnswersId);

}

function getDummyResultRowData() {
    return {
        CorrectAnswerIndex: 1,
        QuestionClientId: "AUS1S1",
        QuestionClientImage: "123",
        IsApprovedForUse: "2",
        IsDeprecated: "0",
        IsActive: "1",
        SectionTypeId: "1",
        QuestionId: "1",
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




// Setup

function setupTinyEditor() {

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

}