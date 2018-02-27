/*Main Javascript File*/
var IntVideo = (function () {
    var intVideo = {};

    intVideo.wwPlayer = null;

    /* Matches VideoType.php */
    var typeEnum = Object.freeze({"Warpwire": 0, "YouTube": 1});

    /* Defaults to Warpwire */
    var _videoType = typeEnum.Warpwire;
    var _videoUrl = '';

    var _numberOfAnswers = 0;

    var _questionModal = null;

    intVideo.initBuild = function (videoType, videoUrl) {
        if (videoType === typeEnum.YouTube) {
            _videoType = videoType;
        } else {

            var tag = document.createElement('script');
            tag.src = "scripts/wwIframeApi.min.js";

            var firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }

        _videoUrl = videoUrl;

        _getEmbedForBuild();

        _setupAddQuestionForm();
    };

    intVideo.setupWarpwireBuildEvents = function () {
        intVideo.wwPlayer('wwvideo').onReady = function(event) {

            var restartOnClose = false;

            _questionModal.on('show.bs.modal', function() {
                if (intVideo.wwPlayer('wwvideo').getPlayerState() === WWIRE.PLAYERSTATES.PLAYING) {
                    intVideo.wwPlayer('wwvideo').pause();
                    restartOnClose = true;
                } else {
                    restartOnClose = false;
                }
                $("#videoTime").val(Math.floor(intVideo.wwPlayer('wwvideo').getCurrentTime()));
            });

            _questionModal.on('hidden.bs.modal', function() {
                if (restartOnClose) {
                    intVideo.wwPlayer('wwvideo').play();
                }
                _resetAddQuestionForm();
            });
        };
    };

    intVideo.deleteQuestion = function (link) {
        // TODO: Delete the question and update question list.
        $(link).parent().parent().parent().fadeOut("slow", function () {
            $(this).remove();
        });
    };

    intVideo.updateQuestionList = function () {

    };

    intVideo.seekTo = function (seconds, play) {
        if (_videoType === typeEnum.Warpwire) {
            if (!isNaN(seconds)) {
                intVideo.wwPlayer('wwvideo').seekTo(seconds);
                if (play) {
                    intVideo.wwPlayer('wwvideo').play();
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
        }
    };

    _setupAddQuestionForm = function () {
        var sess = $("input#sess").val();

        _questionModal = $("#addQuestionModal");
        _numberOfAnswers = 2;

        $("#addQuestionForm").on("submit", function(e) {
            e.preventDefault();

            /*$.ajax({
                type: "post",
                url: "actions/addquestion.php?PHPSESSID="+sess,
                data: $("#addQuestionForm").serialize(),
                success: function (response) {

                }
            });*/

            var questionText = $("#questionText").val();
            var timeSeconds = $("#videoTime").val();

            $("#theQuestions").append('<div class="dropdown">' +
                '<button type="button" class="btn btn-default btn-block question-text" data-toggle="dropdown">' +
                '<span class="label label-default">' + timeSeconds + ' sec</span> ' + questionText + '</button>' +
                '<ul class="dropdown-menu">' +
                '<li><a href="javascript:void(0);" onclick="IntVideo.seekTo(' + timeSeconds + ', true);"><span class="fa fa-external-link text-primary"></span> Go to Question</a></li>' +
                '<li class="divider"></li>' +
                '<li><a href="#"><span class="fa fa-pencil text-warning"></span> Edit Question</a></li>' +
                '<li><a href="javascript:void(0);" onclick="IntVideo.deleteQuestion(this)"><span class="fa fa-trash text-danger"></span> Delete Question</a></li>' +
                '</ul>' +
                '</div>'
            ).hide().fadeIn("slow");

            intVideo.seekTo(timeSeconds);

            $("#addQuestionModal").modal("hide");
        });

        $("#addAnswerBtn").on("click", function () {
            $("#answerContainer").append(_possibleAnswerMarkup);
            _numberOfAnswers++;
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

    _removeAnswer = function () {
        $(this).parent().parent('div.possible-answer').remove();
        _numberOfAnswers--;
        if (_numberOfAnswers < 6) {
            $("#addAnswerBtn").removeProp("disabled");
        }
    };

    _markAsCorrect = function () {
        $(this).toggleClass("btn-default btn-success");
    };

    _resetAddQuestionForm = function () {
        $("#videoTime").val("");
        $("#questionText").val("");
        $(".possible-answer").remove();
        var answerContainer = $("#answerContainer");
        answerContainer.append(_possibleAnswerMarkup);
        answerContainer.append(_possibleAnswerMarkup);
        _numberOfAnswers = 2;
        $("#addAnswerBtn").removeProp("disabled");
        $("#randomizeAnswers").removeProp("checked");
        $("button.answer-correct").off("click").on("click", _markAsCorrect);
        $("button.remove-answer").off("click").on("click", _removeAnswer);
        $("#correctFeedback").val("");
        $("#incorrectFeedback").val("");
        $("#panelFeedback").collapse("hide");
    };

    const _possibleAnswerMarkup = '<div class="input-group possible-answer" data-answer-id="-1">' +
                '<span class="input-group-btn"><button type="button" class="btn btn-default answer-correct"><span class="fa fa-check"></span></button></span>' +
                '<input type="text" class="form-control">' +
                '<div class="input-group-btn">' +
                '<button type="button" class="btn btn-danger remove-answer">' +
                '<span class="fa fa-lg fa-remove"></span>' +
                '</button></div></div>';

    return intVideo;
})();