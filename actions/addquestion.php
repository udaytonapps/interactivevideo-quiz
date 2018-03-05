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

if ( $USER->instructor ) {

    $videoId = $_SESSION["videoId"];

    // Add the Question
    $questionTime = $_POST['videoTime'];
    $questionText = $_POST['questionText'];
    $randomize = isset($_POST['randomize']);
    $correctFeedback = isset($_POST['correctFeedback']) ? $_POST['correctFeedback'] : "";
    $incorrectFeedback = isset($_POST['incorrectFeedback']) ? $_POST['incorrectFeedback'] : "";

    $questionId = $IV_DAO->addQuestion($videoId, $questionTime, $questionText, $correctFeedback, $incorrectFeedback);

    // Use the Question ID to add the Answers

}
