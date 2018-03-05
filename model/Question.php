<?php
namespace IV\Model;

class Question {

    public $questionId;
    public $videoId;
    public $questionTime;
    public $questionText;
    public $correctFeedback;
    public $incorrectFeedback;
    public $randomize;
    public $answers = array();
}