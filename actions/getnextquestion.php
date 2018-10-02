<?php
require_once "../../config.php";
require_once('../dao/IV_DAO.php');
require_once('../util/VideoType.php');
require_once "../util/IVUtil.php";
require_once "../model/Question.php";
require_once "../model/Answer.php";

use \Tsugi\Core\LTIX;
use \IV\DAO\IV_DAO;
use \IV\Model\Question;
use \IV\Model\Answer;

$LAUNCH = LTIX::requireData();

$p = $CFG->dbprefix;

$IV_DAO = new IV_DAO($PDOX, $p);

if (isset($_SESSION["videoId"])) {
    $videoId = $_SESSION["videoId"];

    $questionsArray = array();

    $questions = $IV_DAO->getSortedQuestionsForVideo($videoId);

    foreach ($questions as $question) {
        $newQuestion = new Question();
        $newQuestion->questionId = $question["question_id"];
        $newQuestion->videoId = $question["video_id"];
        $newQuestion->questionTime = $question["q_time"];
        $newQuestion->questionText = $question["q_text"];
        $newQuestion->correctFeedback = $question["correct_fb"];
        $newQuestion->incorrectFeedback = $question["incorrect_fb"];
        $newQuestion->randomize = $question["randomize"];
        $newQuestion->answers = Array();

        $questionsArray[] = $newQuestion;
    }

    echo json_encode($questionsArray);
}
