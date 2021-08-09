<?php
require_once "../config.php";
require_once "dao/IV_DAO.php";

use \Tsugi\Core\LTIX;
use \IV\DAO\IV_DAO;

// Retrieve the launch data if present
$LTI = LTIX::requireData();

$p = $CFG->dbprefix;

$IV_DAO = new IV_DAO($PDOX, $p);

// Start of the output
$OUTPUT->header();
$OUTPUT->topNav($menu);
?>
    <!-- Our main css file that overrides default Tsugi styling -->
    <link rel="stylesheet" type="text/css" href="styles/main.css">
<?php
$OUTPUT->bodyStart();

include("menu.php");

$videoId = $IV_DAO->getVideoId($CONTEXT->id, $LINK->id);

if (!$videoId) {
    // No video id in the database so ask for URL
    if ($USER->instructor) {
        // Show the enter URL form
        ?>
        <div class="row">
            <div class="col-sm-7" style="border-right: 1px solid #ccc;">
                <form method="post" action="actions/addvideo.php" id="addVideoForm">
                    <!-- TODO: This form uses two inputs for video url and can be simplified to a shared one.
                         TODO: Validate video url -->
                    <h3 class="text-center">Create a new interactive video</h3>
                    <p class="text-center">Add a Warpwire or YouTube video URL below to begin creating your interactive video.</p>

                    <div class="form-group">
                        <label for="videoTitle">Video Title</label>
                        <input type="text" class="form-control" id="videoTitle" name="videoTitle" placeholder="Interactive Video Quiz" required oninvalid="this.setCustomValidity('You must enter a title for this interactive video.');" oninput="setCustomValidity('');">
                    </div>

                    <ul class="nav nav-tabs nav-justified">
                        <li class="active">
                            <a data-toggle="tab" href="#warpwire">
                                <img src="images/icon-warpwire-circle-black.png" alt="Warpwire Logo" class="warpwire-logo"> Warpwire Video
                            </a>
                        </li>
                        <li>
                            <a data-toggle="tab" href="#youtube">
                                <span class="fa fa-youtube-play" style="color:#242424;font-size:20px;"></span> YouTube
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div id="warpwire" class="tab-pane fade in active">
                            <h3>Warpwire</h3>
                            <div class="form-group">
                                <label for="videoUrl">Video URL</label>
                                <div class="input-group">
                                    <input type="text" class="form-control url-input" id="wwUrl" name="wwUrl" placeholder="https://udayton.warpwire.com/w/...">
                                    <span class="input-group-addon"><img src="images/icon-warpwire-circle-black.png" alt="Warpwire Logo" class="warpwire-logo"></span>
                                </div>
                            </div>
                        </div>
                        <div id="youtube" class="tab-pane fade">
                            <h3>YouTube</h3>
                            <div class="form-group">
                                <label for="videoUrl">Video URL</label>
                                <div class="input-group">
                                    <input type="text" class="form-control url-input" id="ytUrl" name="ytUrl" placeholder="https://www.youtube.com/watch?v=...">
                                    <span class="input-group-addon"><span class="fa fa-youtube-play" style="color:#242424;font-size:20px;"></span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Create</button> <span class="text-danger" id="blankUrlAlert" style="display:none;"><span aria-hidden="true" class="fa fa-warning"></span> You must enter a video url to continue.</span>
                </form>
            </div>
            <div class="col-sm-5">
                <h3 class="text-center">Reuse an existing interactive video</h3>
                <p class="text-center">You can select a video from the list below to use a previously created video and questions.</p>
                <?php
                $previousVideos = $IV_DAO->findVideosForImport($USER->id);
                if (!$previousVideos) {
                    echo '<p class="text-center">No previous videos available for import.</p>';
                } else {
                    $videoMap = array();
                    foreach ($previousVideos as $video) {
                        if (!array_key_exists($video["sitetitle"], $videoMap)) {
                            $videoMap[$video["sitetitle"]] = array();
                        }
                        array_push($videoMap[$video["sitetitle"]], $video);
                    }
                    ?>
                    <form class="form" action="actions/import.php" method="post">
                        <div class="form-group">
                            <label for="importVid">Previous Interactive Video Quizzes</label>
                            <select class="form-control" id="importVid" name="import-video">
                                <?php
                                foreach($videoMap as $sitetitle => $videos_in_context) {
                                    echo '<optgroup label="'.$sitetitle,'">';
                                    foreach ($videos_in_context as $vid) {
                                        echo '<option value="'.$vid["video_id"].'">'.$vid["video_title"].'</option>';
                                    }
                                    echo '</optgroup>';
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><span class="fa fa-upload" aria-hidden="tre"></span> Import</button>
                    </form>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    } else {
        // Show the this tool is not yet configured message
        ?>
        <div class="text-center">
            <h3>Your instructor has not added an interactive video yet.</h3>
        </div>
        <?php
    }
} else {
    // Video has been set so go to video page.
    $_SESSION["videoId"] = $videoId;

    $finished = $IV_DAO->isStudentFinished($videoId, $USER->id);
    $_SESSION["finished"] = $finished;

    if ($USER->instructor) {
        header( 'Location: '.addSession('build-video.php') ) ;
        return;
    } else {
        if (!$finished) {
            header( 'Location: '.addSession('play-video.php') ) ;
            return;
        } else {
            header( 'Location: '.addSession('student-results.php') ) ;
            return;
        }
    }

}
$OUTPUT->footerStart();
?>
<script type="text/javascript">
    $(document).ready(function () {
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            // Clear all url inputs on video type switch
            $('input.url-input').val('');
        });
        $("#addVideoForm").on("submit", function (e) {
            if ($("#wwUrl").val() === '' && $("#ytUrl").val() === '') {
                e.preventDefault();
                $("#blankUrlAlert").fadeIn("slow");
            }
        });
    });
</script>
<?php
$OUTPUT->footerEnd();
