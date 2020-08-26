<?php
require_once "../../config.php";
require_once('../dao/IV_DAO.php');

use \Tsugi\Core\LTIX;
use \IV\DAO\IV_DAO;

$LAUNCH = LTIX::requireData();

$p = $CFG->dbprefix;

$IV_DAO = new IV_DAO($PDOX, $p);

if (isset($_SESSION["videoId"])) {

    $userId = $USER->id;
    $videoId = $_SESSION["videoId"];

    $IV_DAO->createFinishRecordIfNotExist($videoId, $userId);

    $questions = $IV_DAO->getSortedQuestionsForVideo($videoId);

    $questionNumber = 0;
    $totalCorrect = 0;
    foreach ($questions as $question) {
        $questionNumber++;

        // Get answers for question
        $answers = $IV_DAO->getSortedAnswersForQuestion($question["question_id"]);
        $correct = true;
        foreach ($answers as $answer) {
            $response = $IV_DAO->getResponse($userId, $question["question_id"], $answer["answer_id"]);
            if ($answer["is_correct"] == 0 && $response) {
                // Incorrect answer was chosen.
                $correct = false;
            } else if ($answer["is_correct"] == 1 && !$response) {
                // Correct answer wasn't chosen.
                $correct = false;
            }
        }

        if ($correct) {
            $totalCorrect++;
        }
    }

    $IV_DAO->markStudentNumberCorrect($videoId, $userId, $totalCorrect);
}
return;