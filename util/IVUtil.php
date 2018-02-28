<?php

class IVUtil {

    public static function compareQuestionByTime($question1, $question2) {

        if ($question1->questionTime == $question2->questionTime) {
            return 0;
        }
        return ($question1->questionTime < $question2->questionTime) ? -1 : 1;
    }
}