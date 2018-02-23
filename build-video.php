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
        <div class="col-sm-8">
            <div id="buildVideo" class="videoWrapper">
                <p class="text-center">
                    Loading video <span aria-hidden="true" class="fa fa-spinner fa-spin"></span>
                </p>
            </div>
        </div>
        <div class="col-sm-4">
            <h3>Questions</h3>
            <ul class="list-group">
            </ul>
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addQuestionModal">
                <span aria-hidden="true" class="fa fa-plus" /> Add Question
            </button>
        </div>
    </div>
</div>
    <div id="addQuestionModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Add Question</h4>
                </div>
                <div class="modal-body">
                    <form id="addQuestionForm">
                        <input type="hidden" id="sess" value="<?php $_GET["PHPSESSID"] ?>">
                        <div class="form-group row">
                            <div class="col-xs-3">
                                <label for="videoTime">Video Time (seconds)</label>
                                <input type="text" class="form-control" id="videoTime" name="videoTime">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="questionText">Question Text</label>
                            <textarea class="form-control" rows="5" id="questionText" name="questionText"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Possible Answers</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-dismiss="modal">Save</button>
                </div>
            </div>

        </div>
    </div>
<?php
$OUTPUT->footerStart();
?>
    <!-- Our main javascript file for tool functions -->
    <script src="scripts/main.js" type="text/javascript"></script>

    <script type="text/javascript">
        $(document).ready(function(){
            IntVideo.initBuild(<?php echo $videoType ?>, "<?php echo $videoUrl ?>");
        });
    </script>
<?php
$OUTPUT->footerEnd();
