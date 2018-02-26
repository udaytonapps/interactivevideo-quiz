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

    intVideo.setupWarpwireBuildEvents = function() {
        intVideo.wwPlayer('wwvideo').onReady = function(event) {

            _questionModal.on('show.bs.modal', function() {
                intVideo.wwPlayer('wwvideo').pause();
                $("#videoTime").val(Math.floor(intVideo.wwPlayer('wwvideo').getCurrentTime()));
            });

            _questionModal.on('hidden.bs.modal', function() {
                intVideo.wwPlayer('wwvideo').play();

                _resetAddQuestionForm();
            });
        };
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

            $("#theQuestions").append('<a href="javascript:void(0);" class="list-group-item question-text">' +
                '<span class="label label-default">' + timeSeconds + ' sec</span> ' + questionText + '</a>'
            ).hide().fadeIn("slow");

            if (_videoType === typeEnum.Warpwire) {
                var seekTo = timeSeconds;
                if (!isNaN(seekTo)) {
                    intVideo.wwPlayer('wwvideo').seekTo(seekTo);
                }
            }

            $("#addQuestionModal").modal("hide");
        });

        $("#addAnswerBtn").on("click", function() {
            $("#answerContainer").append(_possibleAnswerMarkup);
            _numberOfAnswers++;
            if (_numberOfAnswers >= 6) {
                $("#addAnswerBtn").prop("disabled", true);
            }
            $("button.answer-correct").off("click").on("click", _markAsCorrect);
            $("button.remove-answer").off("click").on("click", _removeAnswer);
        });

        $("button.answer-correct").on("click", _markAsCorrect);
        $("button.remove-answer").on("click", _removeAnswer);
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