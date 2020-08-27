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

    $videoType = NULL;
    $videoUrl = NULL;

    $wwUrl = $_POST['wwUrl'];
    $ytUrl = $_POST['ytUrl'];

    $videoTitle = $_POST['videoTitle'];

    if (!empty($wwUrl)) {
        $videoType = VideoType::Warpwire;
        $videoUrl = $wwUrl;
    } else if (!empty($ytUrl)) {
        $videoType = VideoType::YouTube;
        $videoUrl = $ytUrl;
    }

    if (isset($videoType) && isset($videoUrl)) {
        $_SESSION["videoId"] = $IV_DAO->createVideo($CONTEXT->id, $LINK->id, $USER->id, $videoUrl, $videoType, $videoTitle);
        header( 'Location: '.addSession('../build-video.php') ) ;
        return;
    }
}
