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
    ?>
    <h3>Video Results</h3>
    <div class="row">
        <div class="col-sm-12">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th class="col-md-3">Student Name</th>
                            <th class="col-md-1 text-center">Started</th>
                            <th class="col-md-1 text-center">Finished</th>
                            <th class="col-md-1 text-center">Correct</th>
                            <th class="col-md-2 text-left">Start Time</th>
                            <th class="col-md-2 text-left">Finish Time</th>
                            <th class="col-md-2 text-left">Last Activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rosterStudents = LTIX::getRosterMembers($LTI);
                        if ($rosterStudents) {
                            // If there is a roster, student list will be populated from it (such as when launched from LMS)
                            usort($rosterStudents, array('IVUtil', 'compareStudentsLastName'));
                            foreach ($rosterStudents as $student) {
                                if (in_array($student->sakai_ext->sakai_role, ['Student', 'Learner'])) {
                                    $userId = $IV_DAO->getTsugiUserId($student->lti11_legacy_user_id);
                                    // Display name is populated from the roster data
                                    $displayName = $student->family_name . ', ' . $student->given_name;
                                    generateTableRows($userId, $videoId, $displayName);
                                }
                            }
                        } else {
                            // Otherwise, just populate student list from db data
                            $students = $IV_DAO->getStudents($videoId);
                            foreach ($students as $student) {
                                $userId = $student["user_id"];
                                // Displayname is populated from the db data
                                $displayName = $IV_DAO->findDisplayName($userId);
                                generateTableRows($userId, $videoId, $displayName);
                            }
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php
} else {
    header( 'Location: '.addSession('play-video.php') ) ;
    return;
}

function generateTableRows($userId, $videoId, $displayName) {
    global $IV_DAO;
    $startedVideo = $IV_DAO->hasStudentStarted($videoId, $userId);
    $finishedVideo = $IV_DAO->isStudentFinished($videoId, $userId);
    $num_correct = $IV_DAO->numCorrectForStudent($videoId, $userId);
    $question_count = $IV_DAO->countQuestions($videoId);
    $startedAt = $IV_DAO->getStudentStartedAt($videoId, $userId);
    $finishedAt = $IV_DAO->getStudentFinishedAt($videoId, $userId);
    $updatedAt = $IV_DAO->getStudentUpdatedAt($videoId, $userId);
    $timeArr = array();
    
    if ($startedAt) {
        $startedAt = strtotime($startedAt);
        array_push($timeArr, $startedAt);
    }
    if ($finishedAt) {
        $finishedAt = strtotime($finishedAt);
        array_push($timeArr, $finishedAt);
    }
    if ($updatedAt) {
        $updatedAt = strtotime($updatedAt);
        array_push($timeArr, $updatedAt);
    }
    if (count($timeArr) > 0) {
        $updatedAt = max($timeArr);
    }
    
    if ($num_correct == null) {
        $num_correct = 0;
    }

    ?>
        <tr>
        <?php
        // Started Video
        if ($startedVideo) { ?>
            <td><a href="student-results.php?student=<?= $userId; ?>"><?= $displayName; ?></a></td>
            <td class="text-center"><span class="fa fa-lg fa-check text-success"></span></td>
        <?php } else { ?>
            <td><p><?= $displayName; ?></p></td>
            <td class="text-center"><span class="fa fa-lg fa-times text-danger"></span></td>
        <?php }
        // Finished Video
        if ($finishedVideo) { ?>
            <td class="text-center"><span class="fa fa-lg fa-check text-success"></span></td>
        <?php } else { ?>
            <td class="text-center"><span class="fa fa-lg fa-times text-danger"></span></td>
        <?php } ?>
            <td style="text-align: center"><?= ($num_correct . '/' . $question_count); ?></td>
        <?php
        // Started At
        if ($startedAt) { ?>
            <td class="text-left"><span><?= date("m/d/y g:i a", $startedAt); ?></span></td>
        <?php } else { ?>
            <td class="text-center"><span>-</span></td>
        <?php }
        // Finished At
        if ($finishedAt) { ?>
            <td class="text-left"><span><?= date("m/d/y g:i a", $finishedAt); ?></span></td>
            <?php } else { ?>
            <td class="text-center"><span>-</span></td>
        <?php }
        // Updated At
        if ($updatedAt) { ?>
            <td class="text-left"><span><?= date("m/d/y g:i a", $updatedAt); ?></span></td>
            <?php } else { ?>
            <td class="text-center"><span>-</span></td>
        <?php } ?>
        </tr>
    <?php
}

function generateTableRows($userId, $videoId, $displayName) {
    global $IV_DAO;
    $startedVideo = $IV_DAO->hasStudentStarted($videoId, $userId);
    $finishedVideo = $IV_DAO->isStudentFinished($videoId, $userId);
    $num_correct = $IV_DAO->numCorrectForStudent($videoId, $userId);
    $question_count = $IV_DAO->countQuestions($videoId);
    $startedAt = $IV_DAO->getStudentStartedAt($videoId, $userId);
    $finishedAt = $IV_DAO->getStudentFinishedAt($videoId, $userId);
    $updatedAt = $IV_DAO->getStudentUpdatedAt($videoId, $userId);
    $timeArr = array();
    
    if ($startedAt) {
        $startedAt = strtotime($startedAt);
        array_push($timeArr, $startedAt);
    }
    if ($finishedAt) {
        $finishedAt = strtotime($finishedAt);
        array_push($timeArr, $finishedAt);
    }
    if ($updatedAt) {
        $updatedAt = strtotime($updatedAt);
        array_push($timeArr, $updatedAt);
    }
    if (count($timeArr) > 0) {
        $updatedAt = max($timeArr);
    }
    
    if ($num_correct == null) {
        $num_correct = 0;
    }

    ?>
        <tr>
        <?php
        // Started Video
        if ($startedVideo) { ?>
            <td><a href="student-results.php?student=<?= $userId; ?>"><?= $displayName; ?></a></td>
            <td class="text-center"><span class="fa fa-lg fa-check text-success"></span></td>
        <?php } else { ?>
            <td><p><?= $displayName; ?></p></td>
            <td class="text-center"><span class="fa fa-lg fa-times text-danger"></span></td>
        <?php }
        // Finished Video
        if ($finishedVideo) { ?>
            <td class="text-center"><span class="fa fa-lg fa-check text-success"></span></td>
        <?php } else { ?>
            <td class="text-center"><span class="fa fa-lg fa-times text-danger"></span></td>
        <?php } ?>
            <td style="text-align: center"><?= ($num_correct . '/' . $question_count); ?></td>
        <?php
        // Started At
        if ($startedAt) { ?>
            <td class="text-left"><span><?= date("m/d/y g:i a", $startedAt); ?></span></td>
        <?php } else { ?>
            <td class="text-center"><span>-</span></td>
        <?php }
        // Finished At
        if ($finishedAt) { ?>
            <td class="text-left"><span><?= date("m/d/y g:i a", $finishedAt); ?></span></td>
            <?php } else { ?>
            <td class="text-center"><span>-</span></td>
        <?php }
        // Updated At
        if ($updatedAt) { ?>
            <td class="text-left"><span><?= date("m/d/y g:i a", $updatedAt); ?></span></td>
            <?php } else { ?>
            <td class="text-center"><span>-</span></td>
        <?php } ?>
        </tr>
    <?php
}

$OUTPUT->footerStart();
$OUTPUT->footerEnd();