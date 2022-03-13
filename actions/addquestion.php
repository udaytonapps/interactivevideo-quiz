<?php
require_once "../../config.php";
require_once('../dao/IV_DAO.php');
require_once('../util/VideoType.php');

use \Tsugi\Core\LTIX;
use \IV\DAO\IV_DAO;

$LAUNCH = LTIX::requireData();

$p = $CFG->dbprefix;

$IV_DAO = new IV_DAO($PDOX, $p);

if ( $USER->instructor ) {

    $videoId = $_SESSION["videoId"];

    // Add the Question
    $questionTime = $_POST['videoTime'];
    $questionText = $_POST['questionText'];
    $questionType = $_POST['questionType'] ?? 1;
    $randomize = isset($_POST['randomize']) ? 1 : 0;
    $correctFeedback = isset($_POST['correctFeedback']) ? $_POST['correctFeedback'] : "";
    $incorrectFeedback = isset($_POST['incorrectFeedback']) ? $_POST['incorrectFeedback'] : "";

    if ($_POST["questionId"] == -1) {
        // This is a new question
        $questionId = $IV_DAO->addQuestion($videoId, $questionTime, $questionType, $questionText, $correctFeedback, $incorrectFeedback, $randomize);
    } else {
        // Update existing question
        $questionId = $_POST["questionId"];
        $IV_DAO->updateQuestion($questionId, $questionTime, $questionType, $questionText, $correctFeedback, $incorrectFeedback, $randomize);
    }

    // First delete "removed" answers
    if (isset($_POST["answersToRemove"]))
    $toRemove = explode(",", $_POST["answersToRemove"]);
    foreach ($toRemove as $ansId) {
        $IV_DAO->deleteAnswer($questionId, $ansId);
    }

    // This loop assumes the form correctly updated the answer orders to 1-6 without skipping any
    // indexes. Once an unset answer is found the rest will be discarded.
    for ($i = 1; $i <= 6; $i++) {
        if (isset($_POST['answer'.$i])) {
            $answerOrder = $i;
            $isCorrect = in_array ($i, $_POST['correctAnswer']) ? 1 : 0;
            $answerText = $_POST['answer'.$i];

            if ($_POST["answerId".$i] == -1) {
                // New answer
                $IV_DAO->addAnswer($questionId, $answerOrder, $isCorrect, $answerText);
            } else {
                // Update existing answer
                $IV_DAO->updateAnswer($_POST["answerId".$i], $answerOrder, $isCorrect, $answerText);
            }
        } else {
            // No more answers delete the rest from the database
            break;
        }
    }
}
