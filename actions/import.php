<?php
require_once "../../config.php";
require_once('../dao/IV_DAO.php');
require_once('../util/VideoType.php');

use \Tsugi\Core\LTIX;
use \IV\DAO\IV_DAO;
use \IV\Util\VideoType;

$LAUNCH = LTIX::requireData();

$p = $CFG->dbprefix;

$IV_DAO = new IV_DAO($PDOX, $p);

if ( $USER->instructor && isset($_POST["import-video"])) {
    $prevId = $_POST["import-video"];

    $previousVideo = $IV_DAO->getVideoInfoById($prevId);

    $newId = $IV_DAO->createVideo($CONTEXT->id, $LINK->id, $USER->id, $previousVideo["video_url"], $previousVideo["video_type"], $previousVideo["video_title"]);

    $prevQuestions = $IV_DAO->getSortedQuestionsForVideo($prevId);

    foreach ($prevQuestions as $prevQuestion) {
        $prevAnswers = $IV_DAO->getSortedAnswersForQuestion($prevQuestion["question_id"]);
        // Add question
        $newQuestionId = $IV_DAO->addQuestion($newId, $prevQuestion["q_time"], $prevQuestion["q_type"], $prevQuestion["q_text"], $prevQuestion["correct_fb"], $prevQuestion["incorrect_fb"], $prevQuestion["randomize"]);
        // Add All Answers
        foreach($prevAnswers as $prevAnswer) {
            $IV_DAO->addAnswer($newQuestionId, $prevAnswer["answer_order"], $prevAnswer["is_correct"], $prevAnswer["a_text"]);
        }
    }

    // Import settings from previous link
    $allSettings = $IV_DAO->getAllSettings($previousVideo["link_id"]);
    if (array_key_exists("videotitle", $allSettings)) {
        $LAUNCH->link->settingsSet("videotitle", $allSettings["videotitle"]);
    }
    if (array_key_exists("singleattempt", $allSettings)) {
        $LAUNCH->link->settingsSet("singleattempt", $allSettings["singleattempt"]);
    }
    if (array_key_exists("starttime", $allSettings)) {
        $LAUNCH->link->settingsSet("starttime", $allSettings["starttime"]);
    }
    if (array_key_exists("endtime", $allSettings)) {
        $LAUNCH->link->settingsSet("endtime", $allSettings["endtime"]);
    }

    header( 'Location: '.addSession('../build-video.php') ) ;
    return;
} else {
    header( 'Location: '.addSession('../index.php') ) ;
    return;
}
