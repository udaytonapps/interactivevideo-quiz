<?php
$menu = new \Tsugi\UI\MenuSet();
$menu->setHome('Interactive Video', 'index.php');

if ($USER->instructor) {
    $menu->addRight('<span class="fas fa-user-graduate" aria-hidden="true"></span> Student View', 'play-video.php');
    $menu->addRight('<span class="fas fa-table" aria-hidden="true"></span> Results', "results.php");
    $menu->addRight('<span class="fas fa-edit" aria-hidden="true"></span> Build', 'build-video.php');
} else {
    $menu->addRight('<span class="fas fa-user-graduate" aria-hidden="true"></span> Play Video', 'play-video.php');
    if (isset($_SESSION["finished"]) && $_SESSION["finished"]) {
        $menu->addRight('<span class="fas fa-table" aria-hidden="true"></span> Results', "results.php");
    }
}