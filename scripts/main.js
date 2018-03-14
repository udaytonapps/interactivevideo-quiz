/*Main Javascript File*/
var IntVideo = (function () {
    var intVideo = {};

    intVideo.wwPlayer = null;
    intVideo.ytPlayer = null;

    /* Matches VideoType.php */
    var typeEnum = Object.freeze({"Warpwire": 0, "YouTube": 1});

    var _videoType = typeEnum.Warpwire;
    var _videoUrl = '';

    var _numberOfAnswers = 0;
    var _numberOfQuestionsRemaining = 0;

    var _questionModal = null;
    var _questionArray;

    var _questionDictionary = {};

    var _questionInterval = null;

    intVideo.initBuild = function (videoType, videoUrl) {
        var tag = document.createElement('script');
        if (videoType === typeEnum.YouTube) {
            _videoType = videoType;

            tag.src = "https://www.youtube.com/iframe_api";
        } else {

            tag.src = "scripts/wwIframeApi.min.js";
        }
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        _videoUrl = videoUrl;

        _getEmbedForBuild();

        _setupAddQuestionForm();

        intVideo.updateQuestionList(true);
    };

    intVideo.initPlay = function (videoType, videoUrl) {
        var tag = document.createElement('script');
        if (videoType === typeEnum.YouTube) {
            _videoType = videoType;

            tag.src = "https://www.youtube.com/iframe_api";
        } else {

            tag.src = "scripts/wwIframeApi.min.js";
        }
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        _videoUrl = videoUrl;

        _getEmbedForPlay();
    };

    intVideo.setupWarpwireBuildEvents = function () {
        intVideo.wwPlayer('wwvideo').onReady = function(event) {

            var restartOnClose = false;

            $("#addQuestionBtn").removeClass("disabled");

            _questionModal.on('show.bs.modal', function() {
                if (intVideo.wwPlayer('wwvideo').getPlayerState() === WWIRE.PLAYERSTATES.PLAYING) {
                    intVideo.wwPlayer('wwvideo').pause();
                    restartOnClose = true;
                } else {
                    restartOnClose = false;
                }
                $("#videoTime").val(Math.floor(intVideo.wwPlayer('wwvideo').getCurrentTime()));
            });

            _questionModal.on('hide.bs.modal', function() {
                if (restartOnClose) {
                    intVideo.wwPlayer('wwvideo').play();
                }
                _resetAddQuestionForm();
            });
        };
    };

    intVideo.setupWarpwirePlayEvents = function () {
        intVideo.wwPlayer('wwvideo').onReady = function(event) {
            _questionModal = $("#askQuestionModal");

            intVideo.loadQuestionsForPlay();

            _questionInterval = setInterval(function () {
                var currentPlayTime = intVideo.wwPlayer('wwvideo').getCurrentTime();
                for(question in _questionArray) {
                    var questionTime = _questionArray[question].questionTime;
                    var questionText = _questionArray[question].questionText;
                    if (Math.floor(currentPlayTime).toString() === questionTime && _questionArray[question].answered === false) {
                        intVideo.wwPlayer('wwvideo').pause();
                        _questionArray[question].answered = true;
                        _numberOfQuestionsRemaining--;
                        _questionModal.find("#askQuestionModalBody").append("<p>" + questionText + "</p>");
                        _questionModal.modal({
                            backdrop: 'static',
                            keyboard: false
                        });
                        $("#questionContainer").hide();
                        $('li.question-item[data-question-time="' + questionTime + '"]').css("text-decoration", "line-through");
                    }
                }

            }, 1000);

            _questionModal.on('hidden.bs.modal', function() {
                _questionModal.find("#askQuestionModalBody").empty();
                _updateQuestionsRemainingDisplay();
                $("#questionContainer").fadeIn("slow");
                intVideo.wwPlayer('wwvideo').play();
            });
        };
    };

    intVideo.youTubeOnReadyBuild = function (event) {

        var restartOnClose = false;

        $("#addQuestionBtn").removeClass("disabled");

        _questionModal.on('show.bs.modal', function() {
            if (intVideo.ytPlayer.getPlayerState() == YT.PlayerState.PLAYING) {
                intVideo.ytPlayer.pauseVideo();
                restartOnClose = true;
            } else {
                restartOnClose = false;
            }
            $("#videoTime").val(Math.floor(intVideo.ytPlayer.getCurrentTime()));
        });

        _questionModal.on('hide.bs.modal', function() {
            if (restartOnClose) {
                event.target.playVideo();
            }
            _resetAddQuestionForm();
        });
    };

    intVideo.youTubeOnReadyPlay = function (event) {
        event.target.playVideo();
    };

    intVideo.deleteQuestion = function (link, questionId) {
        var sess = $("input#sess").val();

        $(link).parent().parent().parent().fadeOut("slow", function () {
            $(this).remove();
        });

        $.ajax({
            type: "post",
            data: { "questionId": questionId },
            url: "actions/deletequestion.php?PHPSESSID="+sess,
            success: function (response) {
                intVideo.updateQuestionList(false);
            }
        });

    };

    intVideo.updateQuestionList = function (refresh) {
        var sess = $("input#sess").val();

        $.ajax({
            type: "GET",
            dataType: "json",
            url: "actions/getquestions.php?PHPSESSID="+sess,
            success: function (response) {
                _questionArray = response;

                if (refresh) {
                    var theQuestions = $("#theQuestions");

                    theQuestions.hide();
                    theQuestions.empty();

                    for (question in _questionArray) {
                        _addQuestionToList(theQuestions, _questionArray[question].questionId, _questionArray[question].questionTime, _questionArray[question].questionText);
                    }

                    theQuestions.fadeIn("slow");
                }
            }
        });
    };

    intVideo.loadQuestionsForPlay = function () {
        var sess = $("input#sess").val();

        $.ajax({
            type: "GET",
            dataType: "json",
            url: "actions/getquestions.php?PHPSESSID="+sess,
            success: function (response) {
                _questionArray = response;

                var theQuestions = $("#theQuestions");

                theQuestions.hide();
                theQuestions.empty();
                _numberOfQuestionsRemaining = 0;

                for (question in _questionArray) {
                    theQuestions.append('<li class="list-group-item question-item" data-question-time="' + _questionArray[question].questionTime + '"><span class="question-time">' + _questionArray[question].questionTime + ' seconds</span></li>');
                    _questionArray[question].answered = false;
                    _numberOfQuestionsRemaining++;
                }

                _updateQuestionsRemainingDisplay();
                theQuestions.fadeIn("slow");
            }
        });
    };

    intVideo.seekTo = function (seconds, play) {
        if (_videoType === typeEnum.Warpwire) {
            if (!isNaN(seconds)) {
                intVideo.wwPlayer('wwvideo').seekTo(seconds);
                if (play) {
                    intVideo.wwPlayer('wwvideo').play();
                }
            }
        } else if (_videoType === typeEnum.YouTube) {
            if (!isNaN(seconds)) {
                intVideo.ytPlayer.seekTo(seconds);
                if (play) {
                    intVideo.ytPlayer.playVideo();
                }
            }
        }

    };

    intVideo.deleteVideoConfirm = function () {
        return confirm("Are you sure you want to delete this video and all associated questions? This cannot be undone.");
    };

    intVideo.editQuestion = function (questionId) {

        if (typeof _questionArray !== 'undefined') {

            _questionModal.modal("show");

            $("#questionId").val(questionId);

            for (question in _questionArray) {
                if (parseInt(_questionArray[question].questionId) === questionId) {

                    console.log(_questionArray[question].questionTime);
                    $("#videoTime").val(_questionArray[question].questionTime);
                    $("#questionText").val(_questionArray[question].questionText);

                    $(".possible-answer").remove();

                    _numberOfAnswers = 0;

                    var answerContainer = $("#answerContainer");
                    for (answer in _questionArray[question].answers) {
                        _appendPossibleAnswerMarkup(answerContainer,
                            _questionArray[question].answers[answer].answerId,
                            _questionArray[question].answers[answer].isCorrect,
                            _questionArray[question].answers[answer].answerText);
                    }

                    if (_numberOfAnswers >= 6) {
                        $("#addAnswerBtn").prop("disabled", true);
                    }
                    $("button.answer-correct").off("click").on("click", _markAsCorrect);
                    $("button.remove-answer").off("click").on("click", _removeAnswer);

                    $("#randomizeAnswers").prop("checked", _questionArray[question].randomize === "1");

                    $("#correctFeedback").val(_questionArray[question].correctFeedback);
                    $("#incorrectFeedback").val(_questionArray[question].incorrectFeedback);

                    if (_questionArray[question].correctFeedback !== '' || _questionArray[question].correctFeedback !== '') {
                        $("#panelFeedback").addClass("in");
                    }

                    break;
                }
            }
        }
    };

    _getEmbedForBuild = function () {
        if (_videoType === typeEnum.Warpwire) {
            $("#buildVideo").html(
                '<iframe id="wwvideo" data-ww-id="wwvideo" src="'
                + _videoUrl
                + '" frameborder="0" scrolling="0" allowfullscreen></iframe>'
            );
        } else if (_videoType === typeEnum.YouTube) {
            var youtubeID = _videoUrl.match(/youtube\.com.*?v[\/=](\w+)/)[1];
            $("#buildVideo").html(
                '<iframe id="ytvideo" src="https://www.youtube.com/embed/'
                + youtubeID
                + '?enablejsapi=1" frameborder="0" scrolling="0" allowfullscreen></iframe>'
            );
        }
    };

    _getEmbedForPlay = function () {
        if (_videoType === typeEnum.Warpwire) {
            $("#playVideo").html(
                '<iframe id="wwvideo" data-ww-id="wwvideo" src="'
                + _videoUrl
                + '?share=0&title=0&controls=0" frameborder="0" scrolling="0" allowfullscreen></iframe>'
            );
        } else if (_videoType === typeEnum.YouTube) {
            var youtubeID = _videoUrl.match(/youtube\.com.*?v[\/=](\w+)/)[1];
            $("#playVideo").html(
                '<iframe id="ytvideo" src="https://www.youtube.com/embed/'
                + youtubeID
                + '?enablejsapi=1&amp;rel=0&amp;controls=0&amp;showinfo=0" frameborder="0" scrolling="0" allowfullscreen></iframe>'
            );
        }
    };

    _setupAddQuestionForm = function () {
        var sess = $("input#sess").val();

        var answerContainer = $("#answerContainer");

        _questionModal = $("#addQuestionModal");
        _numberOfAnswers = 0;
        _appendPossibleAnswerMarkup(answerContainer);
        _appendPossibleAnswerMarkup(answerContainer);

        var submitButton = $("#submitQuestion");

        $("#addQuestionForm").on("submit", function(e) {
            e.preventDefault();

            submitButton.addClass("disabled");

            if (_validateAddQuestion($(this))) {
                $.ajax({
                    type: "post",
                    url: "actions/addquestion.php?PHPSESSID="+sess,
                    data: $("#addQuestionForm").serialize(),
                    success: function (response) {
                        intVideo.updateQuestionList(true);
                    }
                });

                intVideo.seekTo($("#videoTime").val());

                _questionModal.modal("hide");
            } else {
                submitButton.removeClass("disabled");
            }
        });

        $("#addAnswerBtn").on("click", function () {
            _appendPossibleAnswerMarkup(answerContainer);
            if (_numberOfAnswers >= 6) {
                $("#addAnswerBtn").prop("disabled", true);
            }
            $("button.answer-correct").off("click").on("click", _markAsCorrect);
            $("button.remove-answer").off("click").on("click", _removeAnswer);
        });

        $("button.answer-correct").off("click").on("click", _markAsCorrect);
        $("button.remove-answer").off("click").on("click", _removeAnswer);

        var feedbackPanel = $("#panelFeedback");

        feedbackPanel.on("hide.bs.collapse", function () {
            $("#feedbackDown").show();
            $("#feedbackUp").hide();
        });

        feedbackPanel.on("show.bs.collapse", function () {
            $("#feedbackDown").hide();
            $("#feedbackUp").show();
        });
    };

    _addQuestionToList = function (theList, questionId, questionTime, questionText) {
        theList.append('<div class="dropdown">' +
            '<button type="button" class="btn btn-default btn-block question-text" data-toggle="dropdown">' +
            '<span class="label label-default">' + questionTime + ' sec</span> ' + questionText + '<span class="caret iv-caret"></span></button>' +
            '<ul class="dropdown-menu dropdown-menu-right">' +
            '<li><a href="javascript:void(0);" onclick="IntVideo.seekTo(' + questionTime + ', true);"><span class="fa fa-external-link text-primary"></span> Go to Question</a></li>' +
            '<li class="divider"></li>' +
            '<li><a href="javascript:void(0);" onclick="IntVideo.editQuestion('+questionId+');"><span class="fa fa-pencil text-warning"></span> Edit Question</a></li>' +
            '<li><a href="javascript:void(0);" onclick="IntVideo.deleteQuestion(this, '+ questionId +');"><span class="fa fa-trash text-danger"></span> Delete Question</a></li>' +
            '</ul>' +
            '</div>'
        );
    };

    _removeAnswer = function () {
        var ansRemoveInput = $("#answersToRemove");
        var answersToRemove = ansRemoveInput.val();

        if (answersToRemove !== '') {
            answersToRemove += ",";
        }

        answersToRemove += $(this).data("answerid");

        ansRemoveInput.val(answersToRemove);

        $(this).parent().parent('div.possible-answer').remove();

        _numberOfAnswers--;

        if (_numberOfAnswers < 6) {
            $("#addAnswerBtn").removeProp("disabled");
        }

        _fixupAnswerIndexes();
    };

    _markAsCorrect = function () {
        $(this).toggleClass("btn-default btn-success");
        var theCheckbox = $(this).parent().parent().find("div.checkbox").find("input:checkbox");
        theCheckbox.prop("checked", !theCheckbox.prop("checked"));
    };

    _resetAddQuestionForm = function () {
        $("#videoTime").val("");
        $("#questionId").val("-1");
        $("#questionText").val("");
        $("#answersToRemove").val("");
        $(".possible-answer").remove();
        var answerContainer = $("#answerContainer");
        _numberOfAnswers = 0;
        _appendPossibleAnswerMarkup(answerContainer);
        _appendPossibleAnswerMarkup(answerContainer);
        $("#addAnswerBtn").removeProp("disabled");
        $("#randomizeAnswers").removeProp("checked");
        $("button.answer-correct").off("click").on("click", _markAsCorrect);
        $("button.remove-answer").off("click").on("click", _removeAnswer);
        $("#correctFeedback").val("");
        $("#incorrectFeedback").val("");
        $("#feedbackDown").show();
        $("#feedbackUp").hide();
        $("#panelFeedback").removeClass("in");
        $("#formFeedback").text("").hide();
        $("#errorMessage").hide();
        $("#addQuestionForm").find("div.form-group").removeClass("has-error");
        $("#submitQuestion").removeClass("disabled");
    };

    _fixupAnswerIndexes = function () {
        var count = 1;
        $("div.possible-answer").each(function () {
            $(this).find("input.correct-checkbox").val(count);
            $(this).find("input.answer-text").prop("name", "answer"+count);
            $(this).find("input.answer-id").prop("name", "answerId"+count);
            count++;
        });
    };

    _appendPossibleAnswerMarkup = function (container, answerId, isCorrect, answerText) {
        answerId = typeof answerId !== 'undefined' ? answerId : -1;
        isCorrect = typeof isCorrect !== 'undefined' ? isCorrect : "0";
        answerText = typeof answerText !== 'undefined' ? answerText : "";
        var checked;
        var buttonClass;
        if (isCorrect === "1") {
            checked = "checked";
            buttonClass = "btn-success";
        } else {
            checked = "";
            buttonClass = "btn-default";
        }
        container.append("<div class=\"input-group possible-answer\">" +
            "<input type=\"hidden\" class=\"answer-id\" name=\"answerId"+(_numberOfAnswers + 1)+"\" value=\""+answerId+"\">" +
            "<div class=\"checkbox sr-only\"><label><input type=\"checkbox\" "+checked+" class=\"correct-checkbox\" name=\"correctAnswer[]\" value=\"" + (_numberOfAnswers + 1) + "\">Is correct</label></div>" +
            "<span class=\"input-group-btn\"><button type=\"button\" class=\"btn " + buttonClass + " answer-correct\"><span class=\"fa fa-check\"></span></button></span>" +
            "<input type=\"text\" value=\""+answerText+"\" name=\"answer" + (_numberOfAnswers + 1) + "\" class=\"form-control answer-text\" required oninvalid=\"this.setCustomValidity('Answer text cannot be blank.');\" oninput=\"setCustomValidity('');\">" +
            "<div class=\"input-group-btn\">" +
            "<button type=\"button\" class=\"btn btn-danger remove-answer\" data-answerid=\""+answerId+"\">" +
            "<span class=\"fa fa-lg fa-remove\"></span>" +
            "</button></div></div>");
        _numberOfAnswers++;
    };

    _validateAddQuestion = function (theForm) {

        var duration;
        if (_videoType === typeEnum.Warpwire) {
            duration = intVideo.wwPlayer('wwvideo').getDuration();
        } else if (_videoType === typeEnum.YouTube) {
            duration = intVideo.ytPlayer.getDuration();
        }

        // Clear old errors
        $("#addQuestionForm").find("div.form-group").removeClass("has-error");
        var feedback = $("#formFeedback");
        var errorMsg = $("#errorMessage");
        feedback.hide();
        // Check if video time is > duration or less than 0
        var videoTime = $("#videoTime");
        if (parseInt(videoTime.val()) > duration || parseInt(videoTime.val()) < 0) {
            videoTime.parent().parent("div.form-group").addClass("has-error");
            feedback.text("The question must appear during the video. Enter a whole number between 0 and "+ duration + " seconds.").fadeIn();
            errorMsg.hide().fadeIn();
            return false
        }
        // Check if answer time already used
        if (typeof _questionArray !== 'undefined') {
            for (question in _questionArray) {
                if (_questionArray[question].questionTime === videoTime.val() && _questionArray[question].questionId !== $("#questionId").val()) {
                    videoTime.parent().parent("div.form-group").addClass("has-error");
                    feedback.text("There is already a question set for " + videoTime.val() + " seconds. Please choose another time.").fadeIn();
                    errorMsg.hide().fadeIn();
                    return false;
                }
            }
        }
        // Check for no answers
        if (theForm.find("div.possible-answer").length === 0) {
            feedback.text("You must provide at least one possible answer.").fadeIn();
            errorMsg.hide().fadeIn();
            return false;
        }
        // Check that there is atleast one correct answer
        if (theForm.find("input.correct-checkbox:checked").length === 0) {
            feedback.text("At least one answer must be marked as correct.").fadeIn();
            errorMsg.hide().fadeIn();
            return false;
        }
        return true;
    };

    _updateQuestionsRemainingDisplay = function () {
        $("#questionsRemaining").html(_numberOfQuestionsRemaining + " Question" + (_numberOfQuestionsRemaining === 1 ? "" : "s") + " Remaining");
    };

    return intVideo;
})();