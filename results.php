<?php
require_once "../config.php";
require_once "dao/IV_DAO.php";
require_once "util/IVUtil.php";

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

if ($USER->instructor) {
    echo('<h3>Video Results</h3>');

    echo('<div class="row"><div class="col-sm-12"><div class="table-responsive">
            <table class="table table-bordered table-striped">
            <thead><tr><th class="col-md-4">Student Name</th><th class="col-md-2 text-center">Started</th><th class="col-md-2 text-center">Finished</th><th class="col-md-2 text-center">Correct Answers</th><th class="col-md-2 text-left">Start Time</th><th class="col-md-2 text-left">Finish Time</th><th class="col-md-2 text-left">Last Activity</th></tr></thead>
            <tbody>');

        $hasRosters = LTIX::populateRoster(false);

        if ($hasRosters) {
            $rosterData = $GLOBALS['ROSTER']->data;

            usort($rosterData, array('IVUtil', 'compareStudentsLastName'));

            foreach ($rosterData as $student) {
                if ($student["role"] == 'Learner') {

                    $userId = $IV_DAO->getTsugiUserId($student["user_id"]);
                    $startedVideo = $IV_DAO->hasStudentStarted($videoId, $userId);

                    $finishedVideo = $IV_DAO->isStudentFinished($videoId, $userId);

                    $num_correct = $IV_DAO->numCorrectForStudent($videoId, $userId);

                    $question_count = $IV_DAO->countQuestions($videoId);

                    if($num_correct == null){
                        $num_correct = 0;
                    }

                    if ($startedVideo) {
                        echo ('<tr>
                                <td><a href="student-results.php?student='.$userId.'">'.$student["person_name_family"].', '.$student["person_name_given"].'</a></td>
                                <td class="text-center">
                                <span class="fa fa-lg fa-check text-success"></span>');
                    } else {
                        echo ('<tr>
                                <td><p>'.$student["person_name_family"].', '.$student["person_name_given"].'</p></td>
                                <td class="text-center">
                                <span class="fa fa-lg fa-times text-danger"></span>');
                    }

                    echo ('</td><td class="text-center">');

                    if ($finishedVideo) {
                        echo ('<span class="fa fa-lg fa-check text-success"></span>');
                    } else {
                        echo ('<span class="fa fa-lg fa-times text-danger"></span>');
                    }

                    echo ('</td>
                           <td style="text-align: center">' . $num_correct. '/' . $question_count . '</td>
                           </tr>');
                }
            }
        } else {
            $students = $IV_DAO->getStudents($videoId);

            foreach ($students as $student) {

                $userId = $student["user_id"];

                $displayName = $IV_DAO->findDisplayName($userId);

                $startedVideo = $IV_DAO->hasStudentStarted($videoId, $userId);

                $startedAt = $IV_DAO->getStudentStartedAt($videoId, $userId);

                $finishedAt = $IV_DAO->getStudentFinishedAt($videoId, $userId);

                $updatedAt = $IV_DAO->getStudentUpdatedAt($videoId, $userId);

                $finishedVideo = $IV_DAO->isStudentFinished($videoId, $userId);

                $num_correct = $IV_DAO->numCorrectForStudent($videoId, $userId);

                $question_count = $IV_DAO->countQuestions($videoId);

                if ($num_correct == null) {
                    $num_correct = 0;
                }

                echo('<tr>
                        <td><a href="student-results.php?student=' . $userId . '">' . $displayName . '</a></td>
                        <td class="text-center">');

                if ($startedVideo) {
                    echo('<span class="fa fa-lg fa-check text-success"></span>');
                } else {
                    echo('<span class="fa fa-lg fa-times text-danger"></span>');
                }

                echo('</td><td class="text-center">');

                if ($finishedVideo) {
                    echo('<span class="fa fa-lg fa-check text-success"></span>');
                } else {
                    echo('<span class="fa fa-lg fa-times text-danger"></span>');
                }
                
                echo('</td>
                <td style="text-align: center">' . $num_correct . '/' . $question_count . '</td>');
                
                if ($startedAt) {
                    echo('<td class="text-left"><span>'. date("m/d/y g:i a", strtotime($startedAt)) .'</span></td>');
                } else {
                    echo('<td class="text-center"><span>-</span></td>');
                }
            
                
                if ($finishedAt) {
                    echo('<td class="text-left"><span>'. date("m/d/y g:i a", strtotime($finishedAt)) .'</span></td>');
                } else {
                    echo('<td class="text-center"><span>-</span></td>');
                }
                
                if ($updatedAt) {
                    echo('<td class="text-left"><span>'. date("m/d/y g:i a", strtotime($updatedAt)) .'</span></td>');
                } else {
                    echo('<td class="text-center"><span>-</span></td>');
                }
                
                echo('</tr>');
            }
        }
    echo ("</tbody>
           </table>
           </div></div>
           </div>");

} else {
    header( 'Location: '.addSession('play-video.php') ) ;
    return;
}

$OUTPUT->footerStart();
$OUTPUT->footerEnd();