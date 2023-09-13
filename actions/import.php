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

    $IV_DAO->importVideo($prevId, $CONTEXT->id, $LINK->id);

    header( 'Location: '.addSession('../build-video.php') ) ;
    return;
} else {
    header( 'Location: '.addSession('../index.php') ) ;
    return;
}
