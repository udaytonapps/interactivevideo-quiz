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

    // Record new response
    $answerIds = $_POST["answers"];
    foreach ($answerIds as $answerId) {
        $IV_DAO->recordResponse($userId, $questionId, $answerId);
    }
    $response_arr["status"] = 'success';
} else {
    $response_arr["status"] = 'error';
}
echo (json_encode($response_arr));