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
}

$videoId = $_SESSION["videoId"];

$video = $IV_DAO->getVideoInfoById($videoId);

if (!$video) {
    // Video not found.
    header( 'Location: '.addSession('index.php') ) ;
}

$videoType = $video["video_type"];
$videoUrl = $video["video_url"];
$videoTitle = $video["video_title"];

// Start of the output
$OUTPUT->header();
?>
<!-- Our main css file that overrides default Tsugi styling -->
<link rel="stylesheet" type="text/css" href="styles/main.css">
<?php
$OUTPUT->bodyStart();

include("menu.php");
?>

<div class="container-fluid">
    <div class="row video-action-row">
        <div class="col-xs-8">
            <span id="videoTitle" class="h3 video-title"><?php echo $videoTitle ?></span>
        </div>
        <div class="col-xs-4 text-right video-actions">
            <button type="button" class="btn btn-warning edit-title" data-toggle="modal" data-target="#editTitleModal">
                <span class="fa fa-pencil" aria-hidden="true" title="Edit Video Title"></span><span class="sr-only">Edit Video Title</span>
            </button>
            <a href="actions/deletevideo.php" class="btn btn-danger delete-video" onclick="return IntVideo.deleteVideoConfirm();">
                <span class="fa fa-trash" aria-hidden="true" title="Delete Video"></span><span class="sr-only">Delete Video</span>
            </a>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-8">
            <div id="buildVideo" class="videoWrapper">
                <p class="text-center">
                    Loading video <span aria-hidden="true" class="fa fa-spinner fa-spin"></span>
                </p>
            </div>
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
                                <label for="videoTime">Video Time</label>
                                <br />
                                <input type="text" size="2" maxlength="2" id="videoHrs" name="videoHrs" title="Video Hours"> hrs
                                <input type="text" size="2" maxlength="2" id="videoMin" name="videoMin" title="Video Minutes"> min
                                <input type="text" size="2" maxlength="2" id="videoSec" name="videoSec" title="Video Seconds" required oninvalid="this.setCustomValidity('You must enter a time for this question.');" oninput="setCustomValidity('');"> sec
                                <input type="hidden" class="form-control" id="videoTime" name="videoTime">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="questionText">Question Text</label>
                            <textarea class="form-control" rows="3" id="questionText" name="questionText" required oninvalid="this.setCustomValidity('Question text cannot be blank.');" oninput="setCustomValidity('');"></textarea>
                        </div>
                        <div class="form-group" id="answerContainer">
                            <label>Possible Answers</label>
                        </div>
                        <button type="button" class="btn btn-primary" id="addAnswerBtn"><span aria-hidden="true" class="fa fa-plus"></span> Add Answer</button>
                        <div class="checkbox">
                            <label><strong><input type="checkbox" name="randomize" value="true" id="randomizeAnswers"> Randomize Answers</strong></label>
                        </div>
                        <hr>
                        <div class="panel panel-default">
                            <div class="panel-heading feedback-panel">
                                <a data-toggle="collapse" href="#panelFeedback" class="btn btn-link btn-block">
                                    <span id="feedbackDown" aria-hidden="true" class="fa fa-chevron-down"></span><span id="feedbackUp" aria-hidden="true" class="fa fa-chevron-up" style="display:none;"></span>Add Feedback
                                </a>
                            </div>
                            <div id="panelFeedback" class="panel-body collapse">
                                <div class="form-group">
                                    <label for="questionText">Correct Feedback</label>
                                    <textarea class="form-control" rows="3" id="correctFeedback" name="correctFeedback"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="questionText">Incorrect Feedback</label>
                                    <textarea class="form-control" rows="3" id="incorrectFeedback" name="incorrectFeedback"></textarea>
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
    <div id="editTitleModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <form id="editTitleForm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Edit Video Title</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="videoTitle">Video Title</label>
                        <input type="text" class="form-control" id="videoTitleInput" name="videoTitleInput" required oninvalid="this.setCustomValidity('You must enter a title for this interactive video.');" oninput="setCustomValidity('');">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success" id="submitEditTitle">Save</button>
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
            IntVideo.initBuild(<?php echo $videoType ?>, "<?php echo $videoUrl ?>");
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
