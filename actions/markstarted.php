<?php
require_once "../../config.php";
require_once('../dao/IV_DAO.php');

use \Tsugi\Core\LTIX;
use \IV\DAO\IV_DAO;

$LAUNCH = LTIX::requireData();

$p = $CFG->dbprefix;

$IV_DAO = new IV_DAO($PDOX, $p);

$userId = $USER->id;
$videoId = $_SESSION["videoId"];

$currentTime = new DateTime('now', new DateTimeZone($CFG->timezone));
$currentTime = $currentTime->format("Y-m-d H:i:s");

$IV_DAO->createFinishRecordIfNotExist($videoId, $userId);

$IV_DAO->markStudentAsStarted($videoId, $userId);
$IV_DAO->setStudentStartedAt($videoId, $userId, $currentTime);
