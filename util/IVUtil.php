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
}