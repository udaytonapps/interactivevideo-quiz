<?php
require_once "../config.php";
require_once "dao/IV_DAO.php";

use \Tsugi\Core\LTIX;
use \IV\DAO\IV_DAO;
use \Tsugi\UI\SettingsForm;

// Retrieve the launch data if present
$LTI = LTIX::requireData();

$p = $CFG->dbprefix;

$IV_DAO = new IV_DAO($PDOX, $p);

$LAUNCH = LTIX::requireData();

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

if (SettingsForm::isSettingsPost()) {
    if (!isset($_POST["videotitle"]) || trim($_POST["videotitle"]) === '') {
        $_SESSION["error"] = __('Title cannot be blank.');
    } else {
        SettingsForm::handleSettingsPost();
        $_SESSION["success"] = __('All settings saved.');
    }
    header('Location: '.addSession('build-video.php'));
    return;
}

SettingsForm::start();
SettingsForm::text('videotitle',__('Video Title'));
SettingsForm::checkbox('singleattempt', __('Only count first attempt for each question'));
SettingsForm::text('starttime',__('Video Start Time (hh:mm:ss)'));
SettingsForm::text('endtime',__('Video End Time (hh:mm:ss)'));
SettingsForm::end();

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
$OUTPUT->pageTitle($videoTitle, false, true);
?>
    <div class="row">
        <div class="col-sm-8">
            <div id="buildVideo" class="videoWrapper">
                <p class="text-center">
                    Loading video <span aria-hidden="true" class="fa fa-spinner fa-spin"></span>
                </p>
            </div>
            <p class="text-center pull-right" style="padding-top:1rem;" >
                <a href="actions/deletevideo.php" class="h4 text-danger delete-video" onclick="return IntVideo.deleteVideoConfirm();">
                    <span class="fa fa-trash" aria-hidden="true" title="Delete Video"></span> Delete Video
                </a>
            </p>
            <h4 style="margin-bottom:0;">Current Video Settings</h4>
            <p>
                <strong>Start Time: </strong><?= gmdate("H:i:s", $startTimeSeconds) ?><br>
                <strong>End Time: </strong><?= ($endTimeSeconds > $startTimeSeconds) ? gmdate("H:i:s", $endTimeSeconds) : 'End of video' ?><br>
                <?= $singleAttempt == 1 ? 'Only count first attempt for each question enabled.' : ''?><br>
            </p>
        </div>
        <div class="col-sm-4">
            <h3 class="question-list-title">Questions</h3>
            <div class="list-group" id="theQuestions">
            </div>
            <button id="addQuestionBtn" type="button" class="btn btn-success disabled" data-toggle="modal" data-target="#addQuestionModal">
                <span aria-hidden="true" class="fa fa-plus"></span> Add Question
            </button>
        </div>
    </div>
    <div id="addQuestionModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="addQuestionForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Add Question</h4>
                    </div>
                    <div class="modal-body">
                        <p id="formFeedback" class="alert alert-danger" style="display:none;"></p>
                        <input type="hidden" id="questionId" name="questionId" value="-1">
                        <div class="form-group row">
                            <div class="col-xs-12">
                                <label class="h4" for="videoTime">Video Time</label>
                                <br />
                                <input type="text" size="2" maxlength="2" id="videoHrs" name="videoHrs" title="Video Hours"> hrs
                                <input type="text" size="2" maxlength="2" id="videoMin" name="videoMin" title="Video Minutes"> min
                                <input type="text" size="2" maxlength="2" id="videoSec" name="videoSec" title="Video Seconds" required oninvalid="this.setCustomValidity('You must enter a time for this question.');" oninput="setCustomValidity('');"> sec
                                <input type="hidden" class="form-control" id="videoTime" name="videoTime">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="h4" for="questionType">Question Type</label>
                            <select class="form-control" id="questionType" name="questionType">
                                <option value="1" selected>Multiple Choice</option>
                                <option value="2">Short Answer</option>
                                <option value="3">Info Card</option>
                                <option value="4">Multiple Choice Survey</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label id="questionTextLabel" for="questionText">Question Text</label>
                            <textarea class="form-control" rows="3" id="questionText" name="questionText" required oninvalid="this.setCustomValidity('Question text cannot be blank.');" oninput="setCustomValidity('');"></textarea>
                        </div>
                        <div id="mccontent">
                            <div class="form-group" id="answerContainer">
                                <label>Possible Answers</label>
                            </div>
                            <button type="button" class="btn btn-primary" id="addAnswerBtn"><span aria-hidden="true" class="fa fa-plus"></span> Add Answer</button>
                            <div class="checkbox" id="random-box">
                                <label><strong><input type="checkbox" name="randomize" id="randomizeAnswers"> Randomize Answers</strong></label>
                            </div>
                            <hr>
                        </div>
                        <div id="feedbackContent">
                            <div class="panel panel-default">
                                <div class="panel-heading feedback-panel">
                                    <button data-toggle="collapse" href="#panelFeedback" class="btn btn-block">
                                        <span id="feedbackDown" aria-hidden="true" class="fa fa-chevron-down"></span><span id="feedbackUp" aria-hidden="true" class="fa fa-chevron-up" style="display:none;"></span>Add Feedback
                                    </button>
                                </div>
                                <div id="panelFeedback" class="panel-body collapse">
                                    <div class="form-group">
                                        <label id="correctFeedbackLabel" for="correctFeedback">Correct Feedback</label>
                                        <textarea class="form-control" rows="3" id="correctFeedback" name="correctFeedback"></textarea>
                                    </div>
                                    <div id="incorrectFeedbackContent" class="form-group">
                                        <label for="incorrectFeedback">Incorrect Feedback</label>
                                        <textarea class="form-control" rows="3" id="incorrectFeedback" name="incorrectFeedback"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="answersToRemove" name="answersToRemove">
                    </div>
                    <div class="modal-footer">
                        <span class="text-danger" id="errorMessage" style="display:none;"><span aria-hidden="true" class="fa fa-exclamation-triangle"></span> Please fix errors before continuing.</span>
                        <button type="submit" class="btn btn-success" id="submitQuestion">Save</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
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
            IntVideo.initBuild(<?php echo $videoType ?>, "<?php echo $videoUrl ?>", <?= $startTimeSeconds ?>, <?= $endTimeSeconds ?>);
        });

        function onWarpwirePlayerAPIReady() {
            IntVideo.wwPlayer = new wwIframeApi();
            IntVideo.setupWarpwireBuildEvents();
        }

        function onYouTubeIframeAPIReady() {
            IntVideo.ytPlayer = new YT.Player('ytvideo', {
                events: {
                    'onReady': IntVideo.youTubeOnReadyBuild
                }
            });
        }
    </script>
<?php
$OUTPUT->footerEnd();
