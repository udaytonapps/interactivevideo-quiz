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

// Start of the output
$OUTPUT->header();
?>
    <!-- Our main css file that overrides default Tsugi styling -->
    <link rel="stylesheet" type="text/css" href="styles/main.css">
<?php
$OUTPUT->bodyStart();

include("menu.php");

if ($USER->instructor) {

    echo ('<div class="container-fluid">
            <h3>Video Results</h3>');

    echo ('<div class="row"><div class="col-sm-6"><div class="table-responsive">
            <table class="table table-bordered table-striped">
            <thead><tr><th class="col-md-9">Student Name</th><th class="col-md-3 text-center">Finished Video</th></tr></thead>
            <tbody>');

    $students = $IV_DAO->getStudentsWithResponses($videoId);

    foreach ($students as $student) {

        $userId = $student["user_id"];

        $displayName = $IV_DAO->findDisplayName($userId);

        $finishedVideo = $IV_DAO->isStudentFinished($videoId, $userId);

        echo ('<tr>
                <td><a href="student-results.php?student='.$userId.'">'.$displayName.'</a></td>
                <td class="text-center">');

        if ($finishedVideo) {
            echo ('<span class="fa fa-lg fa-check text-success"></span>');
        } else {
            echo ('<span class="fa fa-lg fa-times text-danger"></span>');
        }

        echo ('</td></tr>');
    }

    echo ("</tbody>
           </table>
           </div></div>
           </div></div>");

} else {
    header( 'Location: '.addSession('play-video.php') ) ;
}

$OUTPUT->footerStart();
$OUTPUT->footerEnd();