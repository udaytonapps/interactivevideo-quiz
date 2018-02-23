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
?>
    <!-- Our main css file that overrides default Tsugi styling -->
    <link rel="stylesheet" type="text/css" href="styles/main.css">
<?php
$OUTPUT->bodyStart();

include("menu.php");

$videoId = $IV_DAO->getVideoId($CONTEXT->id, $LINK->id);

if (!$videoId) {
    // No video id in the database so ask for URL
    echo('<div class="container-flud">');
    if ($USER->instructor) {
        // Show the enter URL form
        ?>
        <div class="row">
            <div class="col-sm-6 col-sm-offset-3">
                <form method="post" action="actions/addvideo.php">
                    <h3>Create a new interactive video</h3>
                    <p>Add a Warpwire or YouTube video URL below to begin creating your interactive video.</p>
                    <div class="form-group">
                        <label for="videoUrl">Warpwire Video URL</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="wwUrl" name="wwUrl" placeholder="https://udayton.warpwire.com/w/...">
                            <span class="input-group-addon"><img src="images/icon-warpwire-circle-black.png" alt="Warpwire Logo" class="warpwire-logo"></span>
                        </div>
                    </div>
                    <p>OR</p>
                    <div class="form-group">
                        <label for="videoUrl">YouTube Video URL (Coming Soon)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="ytUrl" name="ytUrl" placeholder="https://www.youtube.com/watch?v=..." disabled>
                            <span class="input-group-addon"><span class="fa fa-youtube-play" style="color:#242424;font-size:20px;"></span></span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Create</button>
                </form>
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
    echo('</div>');
} else {
    // Video has been set so go to video page.
    $_SESSION["videoId"] = $videoId["video_id"];
    if ($USER->instructor) {
        header( 'Location: '.addSession('build-video.php') ) ;
    } else {
        header( 'Location: '.addSession('play-video.php') ) ;
    }

}
$OUTPUT->footerStart();
$OUTPUT->footerEnd();
