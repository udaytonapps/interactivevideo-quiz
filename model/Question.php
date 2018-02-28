<?php
namespace IV\Model;

class Question {

    public $questionId;
    public $videoId;
    public $questionTime;
    public $questionText;
    public $correctFeebback;
    public $incorrectFeebback;
    public $answers = array();
}