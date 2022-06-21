<?php

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
    // Nothing yet.
);

// The SQL to create the tables if they don't exist
$DATABASE_INSTALL = array(
    array( "{$CFG->dbprefix}iv_video",
        "create table {$CFG->dbprefix}iv_video (
    video_id    INTEGER NOT NULL AUTO_INCREMENT,
    link_id     INTEGER NOT NULL,
    context_id  INTEGER NULL,
    user_id     INTEGER NULL,
    video_url   VARCHAR(4000),
    video_type  INTEGER NOT NULL,
    video_title varchar(255) NOT NULL,
    
    UNIQUE(link_id, context_id),
    PRIMARY KEY(video_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),
    array( "{$CFG->dbprefix}iv_question",
        "create table {$CFG->dbprefix}iv_question (
    question_id   INTEGER NOT NULL AUTO_INCREMENT,
    video_id      INTEGER NOT NULL,
    q_time        INTEGER NOT NULL,
    q_type        TINYINT NOT NULL DEFAULT 1,
    q_text        TEXT NULL,
    randomize     BOOL NOT NULL DEFAULT 0,
    correct_fb    TEXT NULL,
    incorrect_fb  TEXT NULL,
    
    CONSTRAINT `{$CFG->dbprefix}iv_question_ibfk_1`
        FOREIGN KEY (`video_id`)
        REFERENCES `{$CFG->dbprefix}iv_video` (`video_id`)
        ON DELETE CASCADE,
    
    PRIMARY KEY(question_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),
    array( "{$CFG->dbprefix}iv_answer",
        "create table {$CFG->dbprefix}iv_answer (
    answer_id     INTEGER NOT NULL AUTO_INCREMENT,
    question_id   INTEGER NOT NULL,
    answer_order  INTEGER NOT NULL,
    is_correct    BOOL NOT NULL DEFAULT 0,
    a_text        TEXT NULL,
    
    CONSTRAINT `{$CFG->dbprefix}iv_answer_ibfk_1`
        FOREIGN KEY (`question_id`)
        REFERENCES `{$CFG->dbprefix}iv_question` (`question_id`)
        ON DELETE CASCADE,

    PRIMARY KEY(answer_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),
    array( "{$CFG->dbprefix}iv_response",
        "create table {$CFG->dbprefix}iv_response (
    response_id   INTEGER NOT NULL AUTO_INCREMENT,
    user_id       INTEGER NOT NULL,
    question_id   INTEGER NOT NULL,
    answer_id     INTEGER NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}iv_response_ibfk_1`
        FOREIGN KEY (`question_id`)
        REFERENCES `{$CFG->dbprefix}iv_question` (`question_id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `{$CFG->dbprefix}iv_response_ibfk_2`
        FOREIGN KEY (`answer_id`)
        REFERENCES `{$CFG->dbprefix}iv_answer` (`answer_id`)
        ON DELETE CASCADE,
        
    UNIQUE(user_id, question_id, answer_id),
    PRIMARY KEY(response_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),
    array( "{$CFG->dbprefix}iv_shortanswer",
        "create table {$CFG->dbprefix}iv_shortanswer (
    shortanswer_id      INTEGER NOT NULL AUTO_INCREMENT,
    user_id             INTEGER NOT NULL,
    question_id         INTEGER NOT NULL,
    response            TEXT NULL,

    CONSTRAINT `{$CFG->dbprefix}iv_shortanswer_ibfk_1`
        FOREIGN KEY (`question_id`)
        REFERENCES `{$CFG->dbprefix}iv_question` (`question_id`)
        ON DELETE CASCADE,
        
    PRIMARY KEY(shortanswer_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),
    array( "{$CFG->dbprefix}iv_finished",
        "create table {$CFG->dbprefix}iv_finished (
    finished_id       INTEGER NOT NULL AUTO_INCREMENT,
    video_id          INTEGER NOT NULL,
    user_id           INTEGER NOT NULL,
    num_correct       INTEGER NOT NULL DEFAULT 0,
    started           BOOL NOT NULL DEFAULT 0,
    finished          BOOL NOT NULL DEFAULT 0,
    started_at        TIMESTAMP NULL,
    finished_at       TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}iv_finished_ibfk_1`
        FOREIGN KEY (`video_id`)
        REFERENCES `{$CFG->dbprefix}iv_video` (`video_id`)
        ON DELETE CASCADE,
        
    UNIQUE(user_id, video_id),
    PRIMARY KEY(finished_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    // Add question type column
    if (!$PDOX->columnExists('q_type', "{$CFG->dbprefix}iv_question")) {
        $sql = "ALTER TABLE {$CFG->dbprefix}iv_question ADD q_type TINYINT NOT NULL DEFAULT 1";
        echo("Upgrading: " . $sql . "<br/>\n");
        error_log("Upgrading: " . $sql);
        $q = $PDOX->queryDie($sql);
    }

    // Add started_at column
    if (!$PDOX->columnExists('started_at', "{$CFG->dbprefix}iv_finished")) {
        $sql = "ALTER TABLE {$CFG->dbprefix}iv_finished ADD started_at TIMESTAMP NULL";
        echo("Upgrading: " . $sql . "<br/>\n");
        error_log("Upgrading: " . $sql);
        $q = $PDOX->queryDie($sql);
    }

    // Add finished_at column
    if (!$PDOX->columnExists('finished_at', "{$CFG->dbprefix}iv_finished")) {
        $sql = "ALTER TABLE {$CFG->dbprefix}iv_finished ADD finished_at TIMESTAMP NULL";
        echo("Upgrading: " . $sql . "<br/>\n");
        error_log("Upgrading: " . $sql);
        $q = $PDOX->queryDie($sql);
    }

    // Add updated_at column
    if (!$PDOX->columnExists('updated_at', "{$CFG->dbprefix}iv_finished")) {
         $sql = "ALTER TABLE {$CFG->dbprefix}iv_finished ADD updated_at TIMESTAMP NULL";
         echo ("Upgrading: " . $sql . "<br/>\n");
         error_log("Upgrading: " . $sql);
         $q = $PDOX->queryDie($sql);
    }

    // Migrate updated_at columns to iv_finished if the tool user had the intermediary state
    // of having the updated_at on each of the response and short answer tables
    if (
        $PDOX->columnExists('updated_at', "{$CFG->dbprefix}iv_response") &&
        $PDOX->columnExists('updated_at', "{$CFG->dbprefix}iv_shortanswer")
    ) {

        // Migrate the updated_at data
        $sql = "UPDATE {$CFG->dbprefix}iv_finished as t1
        SET updated_at = (
            SELECT DISTINCT a.updated_at
            FROM (
                SELECT * FROM {$CFG->dbprefix}iv_response 
                UNION
                SELECT * FROM {$CFG->dbprefix}iv_shortanswer
            ) AS a
            LEFT JOIN {$CFG->dbprefix}iv_question q
            ON q.question_id = a.question_id
            WHERE a.user_id = t1.user_id AND q.video_id = t1.video_id ORDER BY a.updated_at DESC LIMIT 1
        )";
        $q = $PDOX->queryDie($sql);

        // Remove the old columns once the data was moved
        $sql = "ALTER TABLE {$CFG->dbprefix}iv_response DROP COLUMN updated_at";
        echo ("Upgrading: " . $sql . "<br/>\n");
        error_log("Upgrading: " . $sql);
        $q = $PDOX->queryDie($sql);

        $sql = "ALTER TABLE {$CFG->dbprefix}iv_shortanswer DROP COLUMN updated_at";
        echo ("Upgrading: " . $sql . "<br/>\n");
        error_log("Upgrading: " . $sql);
        $q = $PDOX->queryDie($sql);
    }
    return '202206170841';
};
