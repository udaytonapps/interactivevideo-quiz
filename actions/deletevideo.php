<?php
require_once "../../config.php";
require_once('../dao/IV_DAO.php');
require_once('../util/VideoType.php');

use \Tsugi\Core\LTIX;
use \IV\DAO\IV_DAO;

$LAUNCH = LTIX::requireData();

$p = $CFG->dbprefix;

$IV_DAO = new IV_DAO($PDOX, $p);

if ( $USER->instructor ) {

    if (isset($_SESSION["videoId"])) {
        $IV_DAO->deleteVideoAndQuestions($_SESSION["videoId"]);

        // clear start and end time settings
        $LAUNCH->link->settingsSet("starttime", "00");
        $LAUNCH->link->settingsSet("endtime", "00");

        $_SESSION["videoId"] = -1;
        header( 'Location: '.addSession('../index.php') ) ;
        return;
    }
}
