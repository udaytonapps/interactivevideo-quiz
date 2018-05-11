<?php
$instructorMenu = array(
    "results.php" => '<span aria-hidden="true" class="fa fa-lg fa-table"></span> Video Results'
);
$studentMenu = array(
    'play-video.php' => '<span aria-hidden="true" class="fa fa-lg fa-play-circle"></span> Play Mode'
);

if($USER->instructor) {
    $menu = $instructorMenu;
} else {
    if ($_SESSION["finished"]) {
        $studentMenu['student-results.php'] = '<span aria-hidden="true" class="fa fa-lg fa-table"></span> Results';
    }
    $menu = $studentMenu;
}
?>
<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <a class="navbar-brand" href="index.php">Interactive Video</a>
        </div>
        <ul class="nav navbar-nav">
            <?php foreach( $menu as $menupage => $menulabel ) : ?>
                <li<?php if($menupage == basename($_SERVER['PHP_SELF'])){echo ' class="active"';} ?>>
                    <a href="<?php echo $menupage ; ?>">
                        <?php echo $menulabel ; ?>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
</nav>
