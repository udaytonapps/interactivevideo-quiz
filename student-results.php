<?php
require_once "../config.php";
require_once "dao/IV_DAO.php";

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

// Start of the output
$OUTPUT->header();
?>
    <!-- Our main css file that overrides default Tsugi styling -->
    <link rel="stylesheet" type="text/css" href="styles/main.css">
<?php
$OUTPUT->bodyStart();

include("menu.php");

if (isset($_SESSION["videoId"])) {
    $videoId = $_SESSION["videoId"];

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

    echo ('<div class="row"><div class="col-sm-12">');

    $questionNumber = 0;
    $totalCorrect = 0;
    foreach ($questions as $question) {
        $questionNumber++;

        echo ('<div><h4>Question '.$questionNumber.' <span class="label label-default">'.$question["q_time"].' sec</span></h4><p>'.$question["q_text"].'</p><ul class="list-group">');

        // Get answers for question
        $answers = $IV_DAO->getSortedAnswersForQuestion($question["question_id"]);
        $correct = true;
        foreach ($answers as $answer) {
            echo ('<li class="list-group-item">');
            $response = $IV_DAO->getResponse($userId, $question["question_id"], $answer["answer_id"]);
            if ($answer["is_correct"] == 1 && $response) {
                // Correct answer and was chosen. Show in UI
                echo ('<span class="fa fa-check text-success"></span>');
            } else if ($answer["is_correct"] == 0 && $response) {
                // Incorrect answer was chosen. Mark as wrong in UI
                echo ('<span class="fa fa-times text-danger"></span>');
                $correct = false;
            } else if ($answer["is_correct"] == 1 && !$response) {
                // Correct answer wasn't chosen. Don't show in UI but mark as wrong
                $correct = false;
            }
            if ($answer["is_correct"] == 1) {
                echo (' <span class="text-success"><strong>'.$answer["a_text"].'</strong></span></li>');
            } else {
                echo (' <span>'.$answer["a_text"].'</span></li>');
            }

        }
        if ($correct) {
            $totalCorrect++;
        }
        echo("</ul></div>");
    }

    echo ('<h4>Score: <span class="text-success">'.$totalCorrect.' / '.$questionNumber.'</span> correct</h4></div></div>');

}

$OUTPUT->footerStart();
$OUTPUT->footerEnd();