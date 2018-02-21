<?php

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
    // Nothing yet.
);

// The SQL to create the tables if they don't exist
$DATABASE_INSTALL = array(
    array( "{$CFG->dbprefix}i_video",
        "create table {$CFG->dbprefix}i_video (
    video_id    INTEGER NOT NULL AUTO_INCREMENT,
    link_id     INTEGER NOT NULL,
    context_id  INTEGER NULL,
    
    UNIQUE(link_id, context_id),
    PRIMARY KEY(video_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);
