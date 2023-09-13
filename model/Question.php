<?php
namespace IV\Model;

class Question {

    public $questionId;
    public $videoId;
    public $questionTime;
    public $questionType;
    public $questionText;
    public $correctFeedback;
    public $incorrectFeedback;
    public $randomize;
    public $answers = array();
}