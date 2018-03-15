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
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div id="playVideo" class="videoWrapper">
                <p class="text-center">
                    Loading video <span aria-hidden="true" class="fa fa-spinner fa-spin"></span>
                </p>
            </div>
        </div>
        <div class="col-sm-12 col-md-3">
            <div id="questionContainer">
                <h4 id="questionsRemaining"></h4>
                <ul class="list-group" id="theQuestions">
                </ul>
            </div>
        </div>
    </div>
    <div class="row video-action-row">
        <div class="col-sm-10 col-sm-offset-2">
            <button id="playButton" class="btn btn-success" disabled="disabled" onclick="IntVideo.play()">Play</button>
            <button id="pauseButton" class="btn btn-danger" disabled="disabled" onclick="IntVideo.pause()">Pause</button>
            <span id="currentPlayTime">0</span> seconds
        </div>
    </div>
</div>
    <div id="askQuestionModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
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
            IntVideo.initPlay(<?php echo $videoType ?>, "<?php echo $videoUrl ?>");
        });

        function onWarpwirePlayerAPIReady() {
            IntVideo.wwPlayer = new wwIframeApi();
            IntVideo.setupWarpwirePlayEvents();
        }

        function onYouTubeIframeAPIReady() {
            IntVideo.ytPlayer = new YT.Player('ytvideo', {
                events: {
                    'onReady': IntVideo.youTubeOnReadyPlay
                }
            });
        }
    </script>
<?php
$OUTPUT->footerEnd();