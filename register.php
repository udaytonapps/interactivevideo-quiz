<?php

$REGISTER_LTI2 = array(
    "name" => "Interactive Video", // Name of the tool
    "FontAwesome" => "fa-play-circle", // Icon for the tool
    "short_name" => "Interactive Video",
    "description" => "This tool allows a user to add questions to a Warpwire video.", // Tool description
    "messages" => array("launch"),
    "hide_from_store" => false,
    "privacy_level" => "public",  // anonymous, name_only, public
    "license" => "Apache",
    "languages" => array(
        "English",
    ),
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
    )
);
