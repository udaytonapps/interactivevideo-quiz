<?php
require_once "../../config.php";
require_once('../dao/IV_DAO.php');
require_once('../util/VideoType.php');
require_once "../util/IVUtil.php";
require_once "../model/Question.php";
require_once "../model/Answer.php";

use \Tsugi\Core\LTIX;
use \IV\DAO\IV_DAO;
use \IV\Model\Question;
use \IV\Model\Answer;

$LAUNCH = LTIX::requireData();

$p = $CFG->dbprefix;

$IV_DAO = new IV_DAO($PDOX, $p);

// Temp Build demo questions
$answer11 = new Answer();
$answer11->answerId = 1;
$answer11->questionId = 1;
$answer11->answerText = "True";
$answer11->isCorrect = true;

$answer12 = new Answer();
$answer12->answerId = 2;
$answer12->questionId = 1;
$answer12->answerText = "False";
$answer12->isCorrect = false;

$question1 = new Question();

$question1->questionId = 1;
$question1->questionTime = 10;
$question1->questionText = "Here is the first question that will be returned by this function.";
$question1->correctFeebback = "Great job!";
$question1->incorrectFeebback = "Sorry, the answer was the color yellow.";
$question1->answers["1"] = $answer11;
$question1->answers["2"] = $answer12;

$answer21 = new Answer();
$answer21->answerId = 3;
$answer21->questionId = 2;
$answer21->answerText = "Today is Wednesday.";
$answer21->isCorrect = false;

$answer22 = new Answer();
$answer22->answerId = 4;
$answer22->questionId = 2;
$answer22->answerText = "This is the correct answer.";
$answer22->isCorrect = true;

$answer23 = new Answer();
$answer23->answerId = 5;
$answer23->questionId = 2;
$answer23->answerText = "This answer is also wrong.";
$answer23->isCorrect = false;

$question2 = new Question();

$question2->questionId = 2;
$question2->questionTime = 5;
$question2->questionText = "This is the second question that has three answers associated with it.";
$question2->correctFeebback = "Great job!";
$question2->incorrectFeebback = "Sorry, the answer was the color blue.";
$question2->answers["3"] = $answer21;
$question2->answers["4"] = $answer22;
$question2->answers["5"] = $answer23;

$theQuestions = array();
$theQuestions["1"] = $question1;
$theQuestions["2"] = $question2;

usort($theQuestions, array('IVUtil', 'compareQuestionByTime'));

echo json_encode($theQuestions);

