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

if ($USER->instructor && isset($_GET["student"])) {
    $userId = $_GET["student"];
} else {
    $userId = $USER->id;
}

if (isset($_SESSION["videoId"])) {
    $videoId = $_SESSION["videoId"];

    $finished = $IV_DAO->isStudentFinished($videoId, $USER->id);
    $_SESSION["finished"] = $finished;
} else {
    header( 'Location: '.addSession('index.php') ) ;
    return;
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

$video = $IV_DAO->getVideoInfoById($videoId);

$videoTitle = $LTI->link->settingsGet("videotitle", false);

if (!$videoTitle) {
    $LTI->link->settingsSet("videotitle", $video["video_title"]);
    $videoTitle = $video["video_title"];
}

$questionsArray = array();

$questions = $IV_DAO->getSortedQuestionsForVideo($videoId);

echo ('<div class="container-fluid">');

if ($USER->instructor) {
    $displayName = $IV_DAO->findDisplayName($userId);
    ?>
    <ul class="breadcrumb">
        <li><a href="results.php">All Results</a></li>
        <li class="active"><?php echo $displayName ?></li>
    </ul>
    <?php
}

echo ('<div class="row"><div class="col-sm-8">
            <h3 class="video-title">'.$videoTitle.'</h3>');

$questionNumber = 0;
$totalCorrect = 0;
foreach ($questions as $question) {
    $questionNumber++;

    $listContent = '';

    // Get answers for question
    $answers = $IV_DAO->getSortedAnswersForQuestion($question["question_id"]);
    $correct = true;
    if ($question["q_type"] == "1") {
        foreach ($answers as $answer) {
            $listContent = $listContent . '<li class="list-group-item">';
            $response = $IV_DAO->getResponse($userId, $question["question_id"], $answer["answer_id"]);
            if ($answer["is_correct"] == 1 && $response) {
                // Correct answer and was chosen. Show in UI
                $listContent = $listContent . '<span class="fa fa-check text-success"></span>';
            } else if ($answer["is_correct"] == 0 && $response) {
                // Incorrect answer was chosen. Mark as wrong in UI
                $listContent = $listContent . '<span class="fa fa-times text-danger"></span>';
                $correct = false;
            } else if ($answer["is_correct"] == 1 && !$response) {
                // Correct answer wasn't chosen. Don't show in UI but mark as wrong
                $correct = false;
            }
            if ($answer["is_correct"] == 1) {
                $listContent = $listContent . ' <span class="text-success"><strong>'.$answer["a_text"].'</strong></span></li>';
            } else {
                $listContent = $listContent . ' <span>'.$answer["a_text"].'</span></li>';
            }

        }
    } else if ($question["q_type"] == "4") {
        foreach ($answers as $answer) {
            $listContent = $listContent . '<li class="list-group-item">';
            $response = $IV_DAO->getResponse($userId, $question["question_id"], $answer["answer_id"]);
            if ($response) {
                // Answer and was chosen. Show in UI
                $listContent = $listContent . '<span class="fa fa-check text-success"></span> <span class="text-success"><strong>'.$answer["a_text"].'</strong></span></li>';
            } else {
                $listContent = $listContent . ' <span>'.$answer["a_text"].'</span></li>';
            }
        }
    }

    if ($question["q_type"] == "1") {
        $type = "Multiple Choice";
    } else if ($question["q_type"] == "2") {
        $type = "Short Answer";
        $response = $IV_DAO->getShortAnswerResponse($userId, $question["question_id"]) ? $IV_DAO->getShortAnswerResponse($userId, $question["question_id"])["response"] : "";
        $listContent = $listContent . '<li class="list-group-item">' . $response . '</li>';
    } else if ($question["q_type"] == "3") {
        $type = "Info Card";
    } else if ($question["q_type"] == "4") {
        $type = "Multiple Choice Survey (Ungraded)";
    } else {
        $type = '';
    }

    echo ('<div>
                    <h4 class="question-header">');
    if ($correct) {
        $totalCorrect++;
        echo('<span class="badge check-badge"><span class="fa fa-check text-success"></span><span class="sr-only">Correct</span></span>');
    } else {
        echo('<span class="badge cancel-badge"><span class="fa fa-times text-danger"></span><span class="sr-only">Incorrect</span></span>');
    }
    echo('<span class="label label-default pull-right">'.IVUtil::formatQuestionTime($question["q_time"]).'</span> Question '.$questionNumber.' <small class="text-muted">'.$type.'</small></h4>
                    <div class="question-results">
                    <p><strong>'.$question["q_text"].'</strong></p>
                    <ul class="list-group">'.$listContent.'</ul>
               </div>
           </div>');
}

$IV_DAO->markStudentNumberCorrect($videoId, $userId, $totalCorrect);

echo ('</div>
            <div class="col-sm-4 text-right">
            <h4 class="score">Score: <span>'.$totalCorrect.' / '.$questionNumber.'</span> correct</h4>
            <a href="'.$video["video_url"].'" target="_blank" title="View Video">View video without questions</a>
            </div></div>');

$OUTPUT->footerStart();
$OUTPUT->footerEnd();