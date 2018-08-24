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
$time = $_POST["time"];
$oldTime = $IV_DAO->getWatchTime($videoId, $userId);

if($time > $oldTime){
    $IV_DAO->updateWatchTime($videoId, $userId, $time);
}
