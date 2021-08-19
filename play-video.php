<?php
require_once "../config.php";
require_once "dao/IV_DAO.php";

use \Tsugi\Core\LTIX;
use \IV\DAO\IV_DAO;

// Retrieve the launch data if present
$LTI = LTIX::requireData();

$p = $CFG->dbprefix;

$IV_DAO = new IV_DAO($PDOX, $p);

if (!isset($_SESSION["videoId"])) {
    // No video id in session. Redirect back to index.
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

$videoId = $_SESSION["videoId"];

$video = $IV_DAO->getVideoInfoById($videoId);

if (!$video) {
    // Video not found.
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

$videoType = $video["video_type"];
$videoUrl = $video["video_url"];

$videoTitle = $LTI->link->settingsGet("videotitle", false);

if (!$videoTitle) {
    $LTI->link->settingsSet("videotitle", $video["video_title"]);
    $videoTitle = $video["video_title"];
}

$finished = $IV_DAO->isStudentFinished($videoId, $USER->id);
$_SESSION["finished"] = $finished;

$videoStart = $LTI->link->settingsGet("starttime", "00");
preg_match("/^([\d]{1,2})?\:?([\d]{1,2})?\:?([\d]{1,2})?$/", $videoStart, $str_time);
$hours = 0;
$minutes = 0;
$seconds = 0;
if (count($str_time) == 4) {
    $hours = $str_time[1];
    $minutes = $str_time[2];
    $seconds = $str_time[3];
} else if (count($str_time) == 3) {
    $minutes = $str_time[1];
    $seconds = $str_time[2];
} else if (count($str_time) == 2) {
    $seconds = $str_time[1];
}
$startTimeSeconds = (intval($hours) * 3600) + (intval($minutes) * 60) + intval($seconds);

$videoEnd = $LTI->link->settingsGet("endtime", "00");
preg_match("/^([\d]{1,2})?\:?([\d]{1,2})?\:?([\d]{1,2})?$/", $videoEnd, $end_time);
$hours = 0;
$minutes = 0;
$seconds = 0;
if (count($end_time) == 4) {
    $hours = $end_time[1];
    $minutes = $end_time[2];
    $seconds = $end_time[3];
} else if (count($end_time) == 3) {
    $minutes = $end_time[1];
    $seconds = $end_time[2];
} else if (count($end_time) == 2) {
    $seconds = $end_time[1];
}
$endTimeSeconds = (intval($hours) * 3600) + (intval($minutes) * 60) + intval($seconds);

$singleAttempt = $LTI->link->settingsGet("singleattempt", 0);
if ($singleAttempt === "1") {
    $singleAttempt = 1;
}

// Start of the output
$OUTPUT->header();
?>
    <!-- Our main css file that overrides default Tsugi styling -->
    <link rel="stylesheet" type="text/css" href="styles/main.css">
<?php
$OUTPUT->bodyStart();

include("menu.php");
$OUTPUT->topNav($menu);
$OUTPUT->flashMessages();

if ($finished) {
    ?>
    <div id="continueModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-body" id="tryAgainBody">
                    <h3>You have already finished watching this video.</h3>
                    <p>Click continue to re-watch this video with questions. Any questions you answer will override your previous answers.</p>
                    <p><a href="<?php echo $videoUrl ?>" target="_blank" title="Link to video without questions">Click here to watch this video without the questions.</a></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-dismiss="modal">Continue</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <span class="h3 video-title"><?php echo $videoTitle ?></span>
        </div>
    </div>
    <div class="row">
        <div id="playVideoContainer" class="col-sm-12 col-md-9">
            <div id="playVideo" class="videoWrapper">
                <p class="text-center">
                    Loading video <span aria-hidden="true" class="fa fa-spinner fa-spin"></span>
                </p>
            </div>
            <div class="row video-action-row">
                <div class="col-sm-12">
                    <button id="playButton" class="btn btn-success" disabled="disabled" onclick="IntVideo.play()">
                        <span class="fa fa-play" aria-hidden="true" title="Play"></span><span class="sr-only">Play</span>
                    </button>
                    <button id="pauseButton" class="btn btn-danger" disabled="disabled" onclick="IntVideo.pause()">
                        <span class="fa fa-pause" aria-hidden="true" title="Pause"></span><span class="sr-only">Pause</span>
                    </button>
                    <button id="backTen" class="btn btn-warning" disabled="disabled" onclick="IntVideo.backTenSeconds()">
                        <span class="fa fa-undo" aria-hidden="true" title="Back Ten Seconds"></span> 10<span class="sr-only">Back 10 Seconds</span>
                    </button>
                    <button id="captionButton" class="btn btn-icon"  onclick="IntVideo.toggleCaptions()">
                        <span class="fa fa-cc " aria-hidden="true" title="Captions"></span><span class="sr-only">Turn Captions On/Off</span>
                    </button>
                    <button id="fullScreenButton" class="btn btn-icon"  captions = "true" onclick="IntVideo.toggleFullScreen()">
                        <span id="fullScreenSpan" class="fa fa-expand" aria-hidden="true" title="FullScreen"></span><span class="sr-only">Full Screen</span>
                    </button>
                    <span class="pull-right" id="currentPlayTime"></span>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-3">
            <div id="questionContainer">
                <h4 id="questionsRemaining"></h4>
                <p id="nextPlayTime"></p>
                <ul class="list-group" id="theQuestions">
                </ul>
            </div>
        </div>
    </div>
    <div id="askQuestionModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content move-modal-down">
                <div class="modal-header">
                    <h4 id="askQuestionModalTitle" class="modal-title"></h4>
                </div>
                <div class="modal-body" id="askQuestionModalBody">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="submitAnswerButton">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="sess" value="<?php echo($_GET["PHPSESSID"]); ?>">
<?php
$OUTPUT->footerStart();
?>
    <!-- Our main javascript file for tool functions -->
    <script src="scripts/main.js" type="text/javascript"></script>

    <script type="text/javascript">
        $(document).ready(function () {
            <?php
            if ($finished) {
                echo ('$("#continueModal").modal("show");');
                echo ('$("#continueModal").on("hide.bs.modal", function () { IntVideo.initPlay('.$videoType.', "'.$videoUrl.'", '.$startTimeSeconds.', '.$endTimeSeconds.', '.$singleAttempt.'); });');
            } else {
                echo ('IntVideo.initPlay('.$videoType.', "'.$videoUrl.'", '.$startTimeSeconds.', '.$endTimeSeconds.', '.$singleAttempt.');');
            }
            ?>
        });

        function onWarpwirePlayerAPIReady() {
            IntVideo.wwPlayer = new wwIframeApi();
            IntVideo.setupWarpwirePlayEvents();
        }

        function onYouTubeIframeAPIReady() {
            IntVideo.ytPlayer = new YT.Player('ytvideo', {
                events: {
                    'onReady': IntVideo.youTubeOnReadyPlay,
                    'onStateChange' : IntVideo.youTubeOnStateChangePlay
                }
            });
        }
    </script>
<?php
$OUTPUT->footerEnd();