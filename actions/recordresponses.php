<?php
require_once "../../config.php";
require_once('../dao/IV_DAO.php');
require_once('../util/VideoType.php');

use \Tsugi\Core\LTIX;
use \IV\DAO\IV_DAO;

$LAUNCH = LTIX::requireData();

$p = $CFG->dbprefix;

$IV_DAO = new IV_DAO($PDOX, $p);

header('Content-type: application/json');

if (isset($_POST["questionId"])) {

    $userId = $USER->id;
    $questionId = $_POST["questionId"];

    // First clear previous response
    $IV_DAO->deleteUserResponsesForQuestion($userId, $questionId);
    $IV_DAO->deleteUserShortAnswerForQuestion($userId, $questionId);

    // Record new response
    if (isset($_POST["answers"]) && is_array($_POST["answers"])) {
        foreach ($_POST["answers"] as $answerId) {
            $IV_DAO->recordResponse($userId, $questionId, $answerId);
        }
    }

    // Record new short answer
    if (isset($_POST["response"]) && $_POST["response"] !== '') {
        $IV_DAO->recordShortAnswer($userId, $questionId, $_POST["response"]);
    }

    $question = $IV_DAO->getQuestionById($questionId);

    if ($question && $question["q_type"] == "1") {
        // Check if correct response
        $answers = $IV_DAO->getSortedAnswersForQuestion($questionId);
        $correctAnswers = array();
        $correct = true;
        foreach ($answers as $answer) {
            if ($answer["is_correct"]) {
                array_push($correctAnswers, $answer["answer_id"]);
            }
            $response = $IV_DAO->getResponse($userId, $questionId, $answer["answer_id"]);
            if ($answer["is_correct"] == 0 && $response) {
                // Incorrect answer was chosen.
                $correct = false;
            } else if ($answer["is_correct"] == 1 && !$response) {
                // Correct answer wasn't chosen.
                $correct = false;
            }
        }

        $response_arr["correctAnswers"] = $correctAnswers;
        $response_arr["correct"] = $correct;
    } else {
        $response_arr["correctAnswers"] = true;
        $response_arr["correct"] = true;
    }

    $response_arr["savestatus"] = 'success';
} else {
    $response_arr["correctAnswers"] = false;
    $response_arr["correct"] = false;
    $response_arr["savestatus"] = 'error';
}
echo (json_encode($response_arr));