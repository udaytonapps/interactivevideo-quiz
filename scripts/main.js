/*Main Javascript File*/
var IntVideo = (function () {
    var intVideo = {};

    intVideo.wwPlayer = null;
    intVideo.ytPlayer = null;

    /* Matches VideoType.php */
    var typeEnum = Object.freeze({"Warpwire": 0, "YouTube": 1});

    var _videoType = typeEnum.Warpwire;
    var _videoUrl = '';
    var _videoStart = 0;
    var _videoEnd = 0;

    var _numberOfAnswers = 0;
    var _numberOfQuestionsRemaining = 0;
    var _totalQuestions = 0;

    var _questionModal = null;
    var _questionArray;

    var _questionInterval = null;

    var _singleAttempt = 0;

    const _questions = [];
    let _activeQuestion = null;

    intVideo.initBuild = function (videoType, videoUrl, startTime = 0, endTime = 0) {
        let tag = document.createElement('script');
        if (videoType === typeEnum.YouTube) {
            _videoType = videoType;

            tag.src = "https://www.youtube.com/iframe_api";
        } else {
            tag.src = "https://www.warpwire.com/player_api/";
        }
        let firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        _videoUrl = videoUrl;
        _videoStart = startTime;
        _videoEnd = endTime;

        _getEmbedForBuild();

        _setupAddQuestionForm();

        _setupEditTitleModal();

        intVideo.updateQuestionList(true);
    };

    intVideo.initPlay = function (videoType, videoUrl, startTime = 0, endTime = 0, singleAttempt = 0) {
        var tag = document.createElement('script');
        if (videoType === typeEnum.YouTube) {
            _videoType = videoType;

            tag.src = "https://www.youtube.com/iframe_api";
        } else {
            tag.src = "https://www.warpwire.com/player_api/";
        }
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        _videoUrl = videoUrl;
        _videoStart = startTime;
        _videoEnd = endTime;
        _singleAttempt = singleAttempt;

        if (_videoType === typeEnum.YouTube) {
            intVideo.defaultYoutubeCaptions();
        }
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
                var videoSeconds = Math.floor(intVideo.wwPlayer('wwvideo').getCurrentTime());
                var hours = Math.floor(videoSeconds / 3600);
                var mins = Math.floor((videoSeconds % 3600) / 60);
                var secs = Math.floor(videoSeconds % 60);

                $("#videoTime").val(videoSeconds);
                $("#videoHrs").val(hours);
                $("#videoMin").val(mins);
                $("#videoSec").val(secs);
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

            $.ajax({
                type: "GET",
                dataType: "json",
                url: "actions/getquestions.php?PHPSESSID="+$("input#sess").val(),
                success: function (response) {
                    _questionArray = response;

                    var theQuestions = $("#theQuestions");

                    theQuestions.hide();
                    theQuestions.empty();
                    _numberOfQuestionsRemaining = 0;

                    var questionCount = 1;
                    for (var question in _questionArray) {
                        theQuestions.append('<li class="list-group-item question-item next-up text-muted" data-question-time="' + _questionArray[question].questionTime + '">' +
                            '<span class="question-time label label-primary">' + _formatPlayTime(_questionArray[question].questionTime) + '</span> Question ' + questionCount + '</li>');
                        _questionArray[question].answered = false;
                        _numberOfQuestionsRemaining++;
                        questionCount++;
                    }

                    _totalQuestions = _numberOfQuestionsRemaining;

                    _updateQuestionsRemainingDisplay();
                    theQuestions.fadeIn("slow");
                }
            }).done( function () {
                let _currentQuestionIndex = 0;
                _questionInterval = setInterval(function () {
                    let currentPlayTime = Math.floor(intVideo.wwPlayer('wwvideo').getCurrentTime());
                    for (let question in _questionArray) {
                        let questionTime = _questionArray[question].questionTime;
                        if (_videoStart <= questionTime && currentPlayTime >= parseInt(questionTime, 10) && _questionArray[question].answered === false) {
                            _questionArray[question].answered = true;
                            // Track the index to know which one to mark correct/incorrect in the sidebar
                            _questionArray[question].index = _currentQuestionIndex;
                            _numberOfQuestionsRemaining--;
                            // Store the questions in a list to be displayed sequentially
                            _questions.push(_questionArray[question]);
                            _currentQuestionIndex++;
                        }
                    }

                    if (!_activeQuestion && _questions.length > 0) {
                        let question = _questions.shift();
                        intVideo.wwPlayer('wwvideo').pause();
                
                        _addQuestionToModal(_questionModal.find("#askQuestionModalBody"), question);
                        $("button.answer-option").off("click").on("click", _markAsCorrect);
                        _questionModal.modal({
                            backdrop: 'static',
                            keyboard: false
                        });
                        let submitButton = $("#submitAnswerButton");
                        submitButton.removeClass("btn-success");
                        submitButton.addClass("btn-primary");
                        submitButton.off("click").on("click", () => {
                            _recordResponseAndCloseModal(question);
                        });
                        _activeQuestion = question; // Question is being answered now
                    }
                    
                    $("#currentPlayTime").text(_updateCurrentPlayTime(currentPlayTime, intVideo.wwPlayer('wwvideo').getDuration()));
                    _updateNextCountdown(currentPlayTime, intVideo.wwPlayer('wwvideo').getDuration());
                }, 1000);

                document.getElementById('playButton').removeAttribute('disabled');

                _questionModal.on('hidden.bs.modal', function() {
                    _questionModal.find("#askQuestionModalBody").empty();
                    _activeQuestion = false;
                    _updateQuestionsRemainingDisplay();
                    $("#questionContainer").fadeIn("fast");
                    // Only continue of there are no more questions at this second
                    if (!_questions.length) {
                        intVideo.wwPlayer('wwvideo').play();
                    }
                });
            });
        };

        intVideo.wwPlayer('wwvideo').onStateChange = function(event) {
            let sess = $("input#sess").val();

            let playButton = document.getElementById('playButton');
            let pauseButton = document.getElementById('pauseButton');
            let backButton = document.getElementById('backTen');

            if (event.data == WWIRE.PLAYERSTATES.PLAYING) {
                pauseButton.removeAttribute('disabled');
                backButton.removeAttribute('disabled');
                playButton.setAttribute('disabled', 'disabled');
                $.ajax({
                    type: 'POST',
                    url: "actions/markstarted.php?PHPSESSID="+sess
                });
            } else if (event.data == WWIRE.PLAYERSTATES.PAUSED) {
                pauseButton.setAttribute('disabled', 'disabled');
                backButton.setAttribute('disabled', 'disabled');
                playButton.removeAttribute('disabled');
            } else if (event.data == WWIRE.PLAYERSTATES.ENDED) {
                $.ajax({
                    type: "post",
                    url: "actions/markfinished.php?PHPSESSID="+sess,
                    async: false,
                    success: function (response) {
                        if (!isNaN(response)) {
                            window.location = "student-results.php?PHPSESSID=" + $("#sess").val();
                        }
                    }
                });
            }
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
            var videoSeconds = Math.floor(intVideo.ytPlayer.getCurrentTime());
            var hours = Math.floor(videoSeconds / 3600);
            var mins = Math.floor((videoSeconds % 3600) / 60);
            var secs = Math.floor(videoSeconds % 60);

            $("#videoTime").val(videoSeconds);
            $("#videoHrs").val(hours);
            $("#videoMin").val(mins);
            $("#videoSec").val(secs);
        });

        _questionModal.on('hide.bs.modal', function() {
            if (restartOnClose) {
                event.target.playVideo();
            }
            _resetAddQuestionForm();
        });
    };

    intVideo.youTubeOnReadyPlay = function (event) {
        _questionModal = $("#askQuestionModal");

        $.ajax({
            type: "GET",
            dataType: "json",
            url: "actions/getquestions.php?PHPSESSID="+$("input#sess").val(),
            success: function (response) {
                _questionArray = response;

                var theQuestions = $("#theQuestions");

                theQuestions.hide();
                theQuestions.empty();
                _numberOfQuestionsRemaining = 0;

                var questionCount = 1;
                for (var question in _questionArray) {
                    theQuestions.append('<li class="list-group-item question-item next-up text-muted" data-question-time="' + _questionArray[question].questionTime + '">' +
                        '<span class="question-time label label-primary">' + _formatPlayTime(_questionArray[question].questionTime) + '</span> Question ' + questionCount + '</li>');
                    _questionArray[question].answered = false;
                    _numberOfQuestionsRemaining++;
                    questionCount++;
                }

                _totalQuestions = _numberOfQuestionsRemaining;

                _updateQuestionsRemainingDisplay();
                theQuestions.fadeIn("slow");
            }
        }).done( function () {
            let _currentQuestionIndex = 0;
            _questionInterval = setInterval(function () {
                let currentPlayTime = Math.floor(intVideo.ytPlayer.getCurrentTime());
                for (let question in _questionArray) {
                    let questionTime = _questionArray[question].questionTime;
                    if (_videoStart <= questionTime && currentPlayTime >= parseInt(questionTime, 10) && _questionArray[question].answered === false) {
                        _questionArray[question].answered = true;
                        // Track the index to know which one to mark correct/incorrect in the sidebar
                        _questionArray[question].index = _currentQuestionIndex;
                        _numberOfQuestionsRemaining--;
                        // Store the questions in a list to be displayed sequentially
                        _questions.push(_questionArray[question]);
                        _currentQuestionIndex++;
                    }
                }

                if (!_activeQuestion && _questions.length > 0) {
                    let question = _questions.shift();
                    intVideo.ytPlayer.pauseVideo();
            
                    _addQuestionToModal(_questionModal.find("#askQuestionModalBody"), question);
                    $("button.answer-option").off("click").on("click", _markAsCorrect);
                    _questionModal.modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                    let submitButton = $("#submitAnswerButton");
                    submitButton.removeClass("btn-success");
                    submitButton.addClass("btn-primary");
                    submitButton.off("click").on("click", () => {
                        _recordResponseAndCloseModal(question);
                    });
                    _activeQuestion = question; // Question is being answered now
                }
                
                $("#currentPlayTime").text(_updateCurrentPlayTime(currentPlayTime, intVideo.ytPlayer.getDuration()));
                _updateNextCountdown(currentPlayTime, intVideo.ytPlayer.getDuration());
            }, 1000);

            document.getElementById('playButton').removeAttribute('disabled');

            _questionModal.on('hidden.bs.modal', function() {
                _questionModal.find("#askQuestionModalBody").empty();
                _activeQuestion = false;
                _updateQuestionsRemainingDisplay();
                $("#questionContainer").fadeIn("fast");
                // Only continue of there are no more questions at this second
                if (!_questions.length) {
                    intVideo.ytPlayer.playVideo();
                }
            });
        });
    };

    intVideo.youTubeOnStateChangePlay = function (event) {
        let sess = $("input#sess").val();

        let playButton = document.getElementById('playButton');
        let pauseButton = document.getElementById('pauseButton');
        let backButton = document.getElementById('backTen');
        if (event.data == 1) { // Playing
            pauseButton.removeAttribute('disabled');
            backButton.removeAttribute('disabled');
            playButton.setAttribute('disabled', 'disabled');
            $.ajax({
                type: 'POST',
                url: "actions/markstarted.php?PHPSESSID="+sess
            });
        } else if (event.data == 2) { // Paused
            pauseButton.setAttribute('disabled', 'disabled');
            backButton.setAttribute('disabled', 'disabled');
            playButton.removeAttribute('disabled');
        } else if (event.data == 0) { // Ended
            $.ajax({
                type: "post",
                url: "actions/markfinished.php?PHPSESSID="+sess,
                async: false,
                success: function (response) {
                    if (!isNaN(response)) {
                        window.location = "student-results.php?PHPSESSID=" + $("#sess").val();
                    }
                }
            });
        }
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

                    for (var question in _questionArray) {
                        _addQuestionToList(theQuestions, _questionArray[question].questionId, _questionArray[question].questionTime, _questionArray[question].questionText);
                    }

                    theQuestions.fadeIn("slow");
                }
            }
        });
    };

    intVideo.backTenSeconds = function () {
        let currentTime;
        if (_videoType === typeEnum.Warpwire) {
            currentTime = Math.floor(intVideo.wwPlayer('wwvideo').getCurrentTime());
        } else if (_videoType === typeEnum.YouTube) {
            currentTime = Math.floor(intVideo.ytPlayer.getCurrentTime());
        }
        let newTime = currentTime - 10.0;
        if (newTime < 0) {
            newTime = 0;
        }
        intVideo.seekTo(newTime, true);
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

    intVideo.play = function () {
        if (_videoType === typeEnum.Warpwire) {
            intVideo.wwPlayer('wwvideo').play();
        } else if (_videoType === typeEnum.YouTube) {
            intVideo.ytPlayer.playVideo();
        }
    };

    intVideo.pause = function () {
        if (_videoType === typeEnum.Warpwire) {
            intVideo.wwPlayer('wwvideo').pause();
        } else if (_videoType === typeEnum.YouTube) {
            intVideo.ytPlayer.pauseVideo();
        }
    };

    intVideo.defaultYoutubeCaptions = function () {
        if(intVideo.ytPlayer != null) {
            intVideo.ytPlayer.loadModule("captions");
            intVideo.ytPlayer.loadModule("cc");
            intVideo.ytPlayer.setOption("captions", 'track', {
                'languageCode': 'en'
            });
        }
    };

    $("#fullScreenButton").click(function () {
        let vidCon = document.getElementById("playVideoContainer");
        $(vidCon).toggleClass("transition");
    });

    intVideo.toggleCaptions = function () {
        if (_videoType === typeEnum.Warpwire) {
            if(intVideo.wwPlayer('wwvideo').getCaptions()[0] != null) {
                if(intVideo.wwPlayer('wwvideo').getCaptions()[0].enabled){
                    intVideo.wwPlayer('wwvideo').setCaption('');
                    document.getElementById("captionButton").classList.remove('btn-icon-selected');
                    document.getElementById("captionButton").classList.add('btn-icon');
                }else{
                    intVideo.wwPlayer('wwvideo').setCaption(intVideo.wwPlayer('wwvideo').getCaptions()[0].label);
                    document.getElementById("captionButton").classList.remove('btn-icon');
                    document.getElementById("captionButton").classList.add('btn-icon-selected');
                }
            }
        } else if (_videoType === typeEnum.YouTube) {
            let fullButton = document.getElementById('fullScreenButton');
            if(fullButton.getAttribute("captions")=="true"){
                intVideo.ytPlayer.unloadModule("captions");
                intVideo.ytPlayer.unloadModule("cc");
                captionBtn.setAttribute("data-captions", "false");
                document.getElementById("captionButton").classList.remove('btn-icon-selected');
                document.getElementById("captionButton").classList.add('btn-icon');
            }else{
                intVideo.ytPlayer.loadModule("captions");
                intVideo.ytPlayer.loadModule("cc");
                captionBtn.setAttribute("data-captions", "true");
                document.getElementById("captionButton").classList.remove('btn-icon');
                document.getElementById("captionButton").classList.add('btn-icon-selected');
            }
        }
    };

    intVideo.toggleFullScreen = function () {

        if (_videoType === typeEnum.Warpwire) {
            if ( document.getElementById("playVideoContainer").classList.contains('col-md-9')){

                document.getElementById("playVideoContainer").classList.remove('col-md-9');
                document.getElementById("playVideoContainer").classList.add('col-md-12');
                document.getElementById("fullScreenSpan").classList.remove('fa-expand');
                document.getElementById("fullScreenSpan").classList.add('fa-compress');
            } else if (document.getElementById("playVideoContainer").classList.contains('col-md-12')){
                document.getElementById("playVideoContainer").classList.remove('col-md-12');
                document.getElementById("playVideoContainer").classList.add('col-md-9');
                document.getElementById("fullScreenSpan").classList.remove('fa-compress');
                document.getElementById("fullScreenSpan").classList.add('fa-expand');
            }
        } else if (_videoType === typeEnum.YouTube) {
            if ( document.getElementById("playVideoContainer").classList.contains('col-md-9')){
                document.getElementById("playVideoContainer").classList.remove('col-md-9');
                document.getElementById("playVideoContainer").classList.add('col-md-12');
                document.getElementById("fullScreenSpan").classList.remove('fa-expand');
                document.getElementById("fullScreenSpan").classList.add('fa-compress');
            } else if (document.getElementById("playVideoContainer").classList.contains('col-md-12')){
                document.getElementById("playVideoContainer").classList.remove('col-md-12');
                document.getElementById("playVideoContainer").classList.add('col-md-9');
                document.getElementById("fullScreenSpan").classList.remove('fa-compress');
                document.getElementById("fullScreenSpan").classList.add('fa-expand');
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

            for (var question in _questionArray) {
                if (parseInt(_questionArray[question].questionId) === questionId) {

                    var videoTimeSeconds = _questionArray[question].questionTime;
                    var hours = Math.floor(videoTimeSeconds / 3600);
                    var mins = Math.floor((videoTimeSeconds % 3600) / 60);
                    var secs = Math.floor(videoTimeSeconds % 60);

                    $("#videoTime").val(videoTimeSeconds);
                    $("#videoHrs").val(hours);
                    $("#videoMin").val(mins);
                    $("#videoSec").val(secs);

                    $("#questionType").val(_questionArray[question].questionType).change();

                    $("#questionText").val(_questionArray[question].questionText);

                    $(".possible-answer").remove();

                    _numberOfAnswers = 0;

                    var answerContainer = $("#answerContainer");
                    for (var answer in _questionArray[question].answers) {
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

                    $("#randomizeAnswers").prop("checked", _questionArray[question].randomize == "1");

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
        let videoHtml = '<iframe id=';
        if (_videoType === typeEnum.Warpwire) {
            videoHtml += '"wwvideo" data-ww-id="wwvideo" src="' + _videoUrl + '?';
        } else if (_videoType === typeEnum.YouTube) {
            let youtubeID = _videoUrl.split('v=')[1];
            let ampersandPosition = youtubeID.indexOf('&');
            if(ampersandPosition !== -1) {
                youtubeID = youtubeID.substring(0, ampersandPosition);
            }
            videoHtml += '"ytvideo" src="https://www.youtube.com/embed/' + youtubeID + '?enablejsapi=1';
        }
        if (_videoStart > 0) {
            videoHtml += '&start=' + _videoStart;
        }
        if (_videoEnd > _videoStart) {
            videoHtml += '&end=' + _videoEnd;
        }
        videoHtml += '" allowfullscreen></iframe>';
        $("#buildVideo").html(videoHtml);
    };

    _getEmbedForPlay = function () {
        let videoHtml = '<iframe id=';
        if (_videoType === typeEnum.Warpwire) {
            videoHtml += '"wwvideo" data-ww-id="wwvideo" src="' + _videoUrl + '?share=0&title=0&controls=0';
        } else if (_videoType === typeEnum.YouTube) {
            let youtubeID = _videoUrl.split('v=')[1];
            let ampersandPosition = youtubeID.indexOf('&');
            if(ampersandPosition !== -1) {
                youtubeID = youtubeID.substring(0, ampersandPosition);
            }
            videoHtml += '"ytvideo" src="https://www.youtube.com/embed/' + youtubeID + '?enablejsapi=1&amp;rel=0&amp;controls=0&amp;showinfo=0';
        }
        if (_videoStart > 0) {
            videoHtml += '&start=' + _videoStart;
        }
        if (_videoEnd > _videoStart && _videoType !== typeEnum.Warpwire) {
            videoHtml += '&end=' + _videoEnd;
        }
        videoHtml += '" allowfullscreen></iframe>';
        $("#playVideo").html(videoHtml);
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

        $("#questionType").off("change").on("change", function () {
            let selectedType = $(this).val();
            if (selectedType == "1") {
                // Selected multiple choice show all
                $("#mccontent").fadeIn();
                $("#feedbackContent").fadeIn();
                $("#incorrectFeedbackContent").show();
                $("#questionTextLabel").text("Question Text");
                $("#correctFeedbackLabel").text("Correct Feedback");
                $(".possible-answer").remove();
                var answerContainer = $("#answerContainer");
                answerContainer.removeClass("hideCorrect");
                _numberOfAnswers = 0;
                _appendPossibleAnswerMarkup(answerContainer);
                _appendPossibleAnswerMarkup(answerContainer);
                $("#random-box").fadeIn();
            } else if (selectedType == "2") {
                // Selected short answer show question text and correct feedback
                $("#mccontent").hide();
                $("#feedbackContent").fadeIn();
                $("#incorrectFeedbackContent").hide();
                $("#correctFeedbackLabel").text("Feedback");
                $(".possible-answer").remove();
            } else if (selectedType == "3") {
                // Selected info card just question text
                $("#mccontent").hide();
                $("#feedbackContent").hide();
                $("#questionTextLabel").text("Card Text");
                $(".possible-answer").remove();
            } else if (selectedType == "4") {
                // Selected multiple choice survey show all
                $("#mccontent").fadeIn();
                $("#feedbackContent").fadeIn();
                $("#incorrectFeedbackContent").hide();
                $("#correctFeedbackLabel").text("Feedback");
                $("#questionTextLabel").text("Question Text");
                $(".possible-answer").remove();
                var answerContainer = $("#answerContainer");
                _numberOfAnswers = 0;
                _appendPossibleAnswerMarkup(answerContainer);
                _appendPossibleAnswerMarkup(answerContainer);
                answerContainer.addClass("hideCorrect");
                $("#random-box").hide();
            }
        });
    };

    _setupEditTitleModal = function () {
        var sess = $("input#sess").val();
        var videoTitle = $("#videoTitle");
        var editTitleModal = $("#editTitleModal");

        editTitleModal.on("show.bs.modal", function () {
            $("#videoTitleInput").val(videoTitle.text());
        });

        var saveTitleButton = $("#submitEditTitle");

        $("#editTitleForm").on("submit", function(e) {
            e.preventDefault();

            saveTitleButton.addClass("disabled");

            $.ajax({
                type: "post",
                url: "actions/editvideotitle.php?PHPSESSID="+sess,
                data: {
                    "videoTitle" : $("#videoTitleInput").val()
                },
                success: function (response) {
                    videoTitle.text($("#videoTitleInput").val());
                }
            });

            editTitleModal.modal("hide");
            saveTitleButton.removeClass("disabled");
        });
    };

    _addQuestionToList = function (theList, questionId, questionTime, questionText) {
        theList.append('<div class="dropdown">' +
            '<button type="button" class="btn btn-default btn-block question-text" data-toggle="dropdown">' +
            '<span class="label label-default">' + _formatPlayTime(questionTime) + '</span> ' + questionText + '<span class="caret iv-caret"></span></button>' +
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
        $("#videoHrs").val("");
        $("#videoMin").val("");
        $("#videoSec").val("");
        $("#questionId").val("-1");
        $("#questionType").val("1").change();
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
        if (isCorrect == "1") {
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

        // Combine time inputs into seconds
        var hours = $("#videoHrs").val();
        hours = hours ? parseInt(hours, 10) : 0;
        var minutes = $("#videoMin").val();
        minutes = minutes ? parseInt(minutes, 10) : 0;
        var seconds = $("#videoSec").val();
        seconds = seconds ? parseInt(seconds, 10) : 0;

        var questionTimeSec = (hours * 3600) + (minutes * 60) + seconds;

        var videoTimeInput = $("#videoTime");
        videoTimeInput.val(questionTimeSec);

        // Clear old errors
        $("#addQuestionForm").find("div.form-group").removeClass("has-error");
        var feedback = $("#formFeedback");
        var errorMsg = $("#errorMessage");
        feedback.hide();
        // Check if video time is > duration or less than 0
        var videoTime = videoTimeInput;
        if (parseInt(videoTime.val()) > duration ||
            (_videoEnd > _videoStart && parseInt(videoTime.val()) >= _videoEnd) ||
            parseInt(videoTime.val()) < 0 ||
            parseInt(videoTime.val()) < _videoStart) {
            videoTime.parent().parent("div.form-group").addClass("has-error");
            let endtime = duration;
            if (_videoEnd > _videoStart) {
                endtime = _videoEnd;
            }
            feedback.text("The question must appear during the video. Enter a whole number between " + _videoStart + " and " + endtime + " seconds.").fadeIn();
            errorMsg.hide().fadeIn();
            return false
        }
        // Check if answer time already used
        if (typeof _questionArray !== 'undefined') {
            for (var question in _questionArray) {
                if (_questionArray[question].questionTime === videoTime.val() && _questionArray[question].questionId !== $("#questionId").val()) {
                    videoTime.parent().parent("div.form-group").addClass("has-error");
                    feedback.text("There is already a question set for " + videoTime.val() + " seconds. Please choose another time.").fadeIn();
                    errorMsg.hide().fadeIn();
                    return false;
                }
            }
        }
        let qType = $("#questionType").val();
        if (qType == "1" || qType == "4") {
            // Check for no answers
            if (theForm.find("div.possible-answer").length === 0) {
                feedback.text("You must provide at least one possible answer.").fadeIn();
                errorMsg.hide().fadeIn();
                return false;
            }
            // Fix quotes in answer text
            $("input.answer-text").each(function() {
                $(this).val($(this).val().split('"').join('&quot;'));
            });
        }
        if (qType == "1") {
            // Check that there is atleast one correct answer
            if (theForm.find("input.correct-checkbox:checked").length === 0) {
                feedback.text("At least one answer must be marked as correct.").fadeIn();
                errorMsg.hide().fadeIn();
                return false;
            }
        }
        return true;
    };

    _addQuestionToModal = function (modalBody, question) {
        $("#askQuestionModalTitle").html("<span id=\"askQuestionModalTitleText\">Question " + (_totalQuestions - _numberOfQuestionsRemaining) + "</span>" +
            "<span class=\"label label-default pull-right\">" + _formatPlayTime(question.questionTime) + "</span>");
        modalBody.append('<h4 class="question-text">' + question.questionText + '</h4>' +
            '<input type="hidden" id="questionId" value="'+question.questionId+'">');
        if (question.questionType == "1" || question.questionType == "4") {
            modalBody.append('<div class="list-group answer-list">');
            if (question.randomize == "1") {
                _shuffle(question.answers);
            }
            for (var answer in question.answers) {
                modalBody.append("<div class=\"answer\">" +
                    "<div class=\"checkbox sr-only\"><label><input type=\"checkbox\" class=\"correct-checkbox\" name=\"markedAnswer[]\" value=\"" + question.answers[answer].answerId + "\">"+question.answers[answer].answerText+"</label></div>" +
                    "<span><button type=\"button\" class=\"btn btn-default answer-option\">" + question.answers[answer].answerText + "</button></span>" +
                    "</div>");
            }
            modalBody.append('</div>');
        } else if (question.questionType == "2") {
            // short answer
            modalBody.append(`
                <div class="form-group">
                    <label for="response" class="sr-only">Response</label>
                    <textarea class="form-control" rows="3" id="response" name="response"></textarea>
                    <p id="emptyResponseWarning" class="alert alert-danger" style="margin-top:1rem;padding:0.5rem;display:none;">You must submit a response.</p>
                </div> 
            `);
        }
        if (_singleAttempt === 1 && question.questionType != "3") {
            // Add warning that student
            modalBody.append('<p class="alert alert-warning" style="margin-top:1rem;padding:0.5rem;"><strong>Single Attempt:</strong> your instructor has set this video to "single attempt" so only your first response to each question will be recorded.</p>');
        }
    };

    _recordResponseAndCloseModal = function (question) {
        let responseWarning = $("#emptyResponseWarning");
        responseWarning.hide();
        let textArea = $("#response");
        if (textArea.length && textArea.val() === "") {
            responseWarning.fadeIn();
            return false;
        }
        let submitButton = $("#submitAnswerButton");
        submitButton.off("click").on("click", function(){$("#askQuestionModal").modal("hide");});
        submitButton.toggleClass("btn-primary btn-success");
        submitButton.text("Continue Video");

        let sess = $("#sess").val();
        let questionId = $("#questionId").val();
        let answerIds = [];
        $('input[type="checkbox"][name="markedAnswer\\[\\]"]:checked').each( function () {
            answerIds.push(parseInt($(this).val()));
        });

        let response = textArea.length ? textArea.val() : "";

        // First persist responses
        $.ajax({
            type: "POST",
            url: "actions/recordresponses.php?PHPSESSID="+sess,
            dataType: "json",
            data: {
                "questionId": questionId,
                "answers": answerIds,
                "response": response
            },
            success: function(response) {
                questionId = parseInt(questionId);
                if (response.savestatus === 'success') {
                    // Update student's score
                    $.ajax({
                        type: "POST",
                        url: "actions/marktotalcorrect.php?PHPSESSID="+sess,
                        data: {}
                    });
                    // Mark correct or not and continue
                    let correct = response.correct;

                    let questionTime = -1;
                    for (let question in _questionArray) {
                        if (_questionArray[question].questionId === questionId) {

                            // Found which question is being answered.
                            questionTime = _questionArray[question].questionTime;
                            let questionModalTitle = $("#askQuestionModalTitleText");
                            questionModalTitle.text(questionModalTitle.text() + " Feedback");
                            let feedbackString = '';
                            if (correct) {
                                if (_questionArray[question].questionType == "1") {
                                    feedbackString +=
                                        '<h4 style="font-weight:normal;"><strong>Question:</strong> ' + _questionArray[question].questionText + '</h4>' +
                                        '<div class="alert alert-success" style="padding:0.5rem;">' +'<h3 class="feedback-header">Correct</h3><p><strong>' + _questionArray[question].correctFeedback + '</strong></p></div>';
                                    feedbackString +='<h4>You Answered:</h4>';
                                    let noAnswerProvided = true;
                                    answerIds.forEach(function (id) {
                                        for (var answer in _questionArray[question].answers) {
                                            if(id === _questionArray[question].answers[answer].answerId){
                                                noAnswerProvided = false;
                                                feedbackString += '<p>' +  _questionArray[question].answers[answer].answerText + '</p>';
                                            }
                                        }
                                    });
                                    if(noAnswerProvided){
                                        feedbackString += '<p>' +  "No Answer" + '</p>';
                                    }
                                } else if (_questionArray[question].questionType == "2") {
                                    feedbackString +=
                                        '<h4>Thank you for your response.</h4>';
                                    if (_questionArray[question].correctFeedback !== null && _questionArray[question].correctFeedback !== "") {
                                        feedbackString +=
                                            '<div class="alert alert-info" style="padding:0.5rem;">' + _questionArray[question].correctFeedback + '</div>';
                                    }
                                } else if (_questionArray[question].questionType == "3") {
                                    feedbackString +=
                                        '<div class="alert alert-info" style="padding:0.5rem;">Press the "Continue Video" button to continue.</div>';
                                } else if (_questionArray[question].questionType == "4") {
                                    feedbackString +=
                                        '<h4 style="font-weight: normal"><strong>Question:</strong> ' + _questionArray[question].questionText + '</h4>';
                                    if (_questionArray[question].correctFeedback) {
                                        feedbackString += '<div class="alert alert-info" style="padding:0.5rem;">' +'<p><strong>' + _questionArray[question].correctFeedback + '</strong></p>';

                                    }
                                    feedbackString +='</div><h4><strong>You Answered:</strong></h4></div>';
                                    let noAnswerProvided = true;
                                    answerIds.forEach(function (id) {
                                        for (var answer in _questionArray[question].answers) {
                                            if(id === _questionArray[question].answers[answer].answerId){
                                                noAnswerProvided = false;
                                                feedbackString += '<div><p>' +  _questionArray[question].answers[answer].answerText + '</p></div>';
                                            }
                                        }
                                    });
                                    if(noAnswerProvided){
                                        feedbackString += '<div><p>' +  "No Answer" + '</p></div></div>';
                                    }
                                    feedbackString += '</div>';
                                }
                            } else {
                                feedbackString +=
                                    '<h4 style="font-weight: normal;"><strong>Question: </strong>' + _questionArray[question].questionText + '</h4>';
                                feedbackString +=
                                    '<div class="alert alert-danger" style="padding:0.5rem;">' +'<h3 class="feedback-header">Incorrect</h3><p><strong>' + _questionArray[question].incorrectFeedback + '</strong></p></div>';
                                feedbackString +='<h4>You Answered:</h4>';
                                let noAnswer = true;
                                answerIds.forEach(function (id) {
                                    for (var answer in _questionArray[question].answers) {
                                        if(id === _questionArray[question].answers[answer].answerId){
                                            noAnswer = false;
                                            feedbackString += '<p>' +  _questionArray[question].answers[answer].answerText + '</p>';
                                        }
                                    }
                                });
                                if(noAnswer){
                                    feedbackString += '<p>' +  "No Answer" + '</p>';
                                }
                                feedbackString +='<h4>Correct Answer(s):</h4>';
                                for (let answer in _questionArray[question].answers) {
                                    if(response.correctAnswers.includes(_questionArray[question].answers[answer].answerId)) {
                                        feedbackString += '<p>' + _questionArray[question].answers[answer].answerText + '</p>';
                                    }
                                }
                            }
                            $("#askQuestionModalBody").hide().empty().html(feedbackString).fadeIn("fast");
                            break;
                        }
                    }
                    $("#questionContainer").hide();

                    let questionListItem = $('li.question-item').eq(question.index);

                    questionListItem.removeClass("next-up");

                    if (correct) {
                        questionListItem.prepend('<span class="text-success fa fa-check"></span> ');
                        questionListItem.find("span.question-time").toggleClass("label-primary label-success");
                    } else {
                        questionListItem.prepend('<span class="text-danger fa fa-times"></span> ');
                        questionListItem.find("span.question-time").toggleClass("label-primary label-danger");
                    }
                } else {
                    alert("Unable to save response and continue. Please reload and try again.");
                }
            },
            error: function(response) {
                alert("Unable to save response and continue. Please reload and try again.");
                console.error("Something went wrong. " + response.responseText);
            }
        });
    };

    _updateQuestionsRemainingDisplay = function () {
        $("#questionsRemaining").html(_numberOfQuestionsRemaining + " Question" + (_numberOfQuestionsRemaining === 1 ? "" : "s") + " Remaining");
    };

    _shuffle = function (array) {
        //The Fisher-Yates (aka Knuth) shuffle

        var currentIndex = array.length, temporaryValue, randomIndex;

        // While there remain elements to shuffle...
        while (0 !== currentIndex) {

            // Pick a remaining element...
            randomIndex = Math.floor(Math.random() * currentIndex);
            currentIndex -= 1;

            // And swap it with the current element.
            temporaryValue = array[currentIndex];
            array[currentIndex] = array[randomIndex];
            array[randomIndex] = temporaryValue;
        }

        return array;
    };

    _updateCurrentPlayTime = function (currentTime, duration) {
        // Assumes video is less than 24 hours
        if (duration > 3600) {
            var start = 11;
            var length = 8;
        } else {
            var start = 14;
            var length = 5;
        }

        var currentFormattedTime = new Date(currentTime * 1000).toISOString().substr(start, length);
        var formattedDuration = new Date(duration * 1000).toISOString().substr(start, length);

        return currentFormattedTime + "/" + formattedDuration;
    };

    _updateNextCountdown = function (currentTime, duration) {
        if (duration > 3600) {
            var start = 11;
            var length = 8;
        } else {
            var start = 14;
            var length = 5;
        }
        var atleastoneleft = false;
        for (var question in _questionArray) {
            if(currentTime < _questionArray[question].questionTime){
                var newTime = _questionArray[question].questionTime - currentTime;
                var newFormattedTime = new Date(newTime * 1000).toISOString().substr(start, length);
                $("#nextPlayTime").text("Next question in: " + newFormattedTime);
                atleastoneleft = true;
                break;
            }
        }
        if (!atleastoneleft) {
            $("#nextPlayTime").text("Next question in: --:--");
        }
    };

    _formatPlayTime = function (timeToFormat) {
        // Assumes video is less than 24 hours
        if (timeToFormat > 3600) {
            var start = 11;
            var length = 8;
        } else {
            var start = 14;
            var length = 5;
        }

        return new Date(timeToFormat * 1000).toISOString().substr(start, length);
    };

    return intVideo;
})();