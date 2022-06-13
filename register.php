<?php

$REGISTER_LTI2 = array(
    "name" => "Interactive Video", // Name of the tool
    "FontAwesome" => "fa-play-circle", // Icon for the tool
    "short_name" => "Interactive Video",
    "description" => "Create video quizzes where questions pop-up during the video at specified times. Available question types include multiple choice, short answer, info card, and multiple choice survey.", // Tool description
    "messages" => array("launch", "launch_grade"),
    "hide_from_store" => false,
    "privacy_level" => "public",  // anonymous, name_only, public
    "license" => "Apache",
    "languages" => array(
        "English"
    ),
    "analytics" => array(
        "internal"
    ),
    "video" => "https://udayton.warpwire.com/w/F_sEAA/",
    "source_url" => "https://github.com/udaytonapps/interactivevideo-quiz",
    // For now Tsugi tools delegate this to /lti/store
    "placements" => array(
        /*
        "course_navigation", "homework_submission",
        "course_home_submission", "editor_button",
        "link_selection", "migration_selection", "resource_selection",
        "tool_configuration", "user_navigation"
        */
    ),
    "screen_shots" => array(
        "images/IV-Start.png",
        "images/IV-Menu.png",
        "images/IV-Student-View-Video.png",
        "images/IV-Second-Video.png",
        "images/IV-Student-Question.png",
        "images/IV-Question-Answered.png",
        "images/IV-Student-Results.png",
        "images/IV-Video-Settings.png"
    )
);
