<?php

class IVUtil {

    public static function compareQuestionByTime($question1, $question2) {

        if ($question1->questionTime == $question2->questionTime) {
            return 0;
        }
        return ($question1->questionTime < $question2->questionTime) ? -1 : 1;
    }

    public static function compareAnswerByOrder($answer1, $answer2) {

        if ($answer1->answerOrder == $answer2->answerOrder) {
            return 0;
        }
        return ($answer1->answerOrder < $answer2->answerOrder) ? -1 : 1;
    }

    public static function formatQuestionTime($time) {
        $hours = floor($time / 3600);
        $mins = floor($time / 60 % 60);
        $secs = floor($time % 60);
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        } else {
            return sprintf('%02d:%02d', $mins, $secs);
        }
    }

    // Comparator for student last name used for sorting roster
    public static function compareStudentsLastName($a, $b) {
        return strcmp($a["person_name_family"], $b["person_name_family"]);
    }
}