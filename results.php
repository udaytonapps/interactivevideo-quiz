<?php
require_once "../config.php";
require_once "dao/IV_DAO.php";

use \Tsugi\Core\LTIX;
use \IV\DAO\IV_DAO;

// Retrieve the launch data if present
$LTI = LTIX::requireData();

$p = $CFG->dbprefix;

$IV_DAO = new IV_DAO($PDOX, $p);

// Start of the output
$OUTPUT->header();
?>
    <!-- Our main css file that overrides default Tsugi styling -->
    <link rel="stylesheet" type="text/css" href="styles/main.css">
<?php
$OUTPUT->bodyStart();

include("menu.php");

if ($USER->instructor) {
    echo ("Comming soon.");
} else {
    header( 'Location: '.addSession('play-video.php') ) ;
}

$OUTPUT->footerStart();
$OUTPUT->footerEnd();