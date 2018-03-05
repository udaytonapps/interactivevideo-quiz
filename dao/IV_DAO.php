<?php
namespace IV\DAO;

class IV_DAO {

    private $PDOX;
    private $p;

    public function __construct($PDOX, $p) {
        $this->PDOX = $PDOX;
        $this->p = $p;
    }

    function getVideoId($context_id, $link_id) {
        $query = "SELECT video_id FROM {$this->p}iv_video WHERE context_id = :contextId AND link_id = :linkId;";
        $arr = array(':contextId' => $context_id, ':linkId' => $link_id);
        return $this->PDOX->rowDie($query, $arr);
    }

    function createVideo($context_id, $link_id, $user_id, $video_url, $video_type) {
        $query = "INSERT INTO {$this->p}iv_video (link_id, context_id, user_id, video_url, video_type) VALUES (:linkId, :contextId, :userId, :videoUrl, :videoType);";
        $arr = array(':linkId' => $link_id, ':contextId' => $context_id, ':userId' => $user_id, ':videoUrl' => $video_url, ':videoType' => $video_type);
        $this->PDOX->queryDie($query, $arr);
        return $this->PDOX->lastInsertId();
    }

    function deleteVideoAndQuestions($video_id) {
        $query = "DELETE FROM {$this->p}iv_video WHERE video_id = :videoId;";
        $arr = array(':videoId' => $video_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function getVideoInfoById($video_id) {
        $query = "SELECT video_type, video_url FROM {$this->p}iv_video WHERE video_id = :videoId;";
        $arr = array(':videoId' => $video_id);
        return $this->PDOX->rowDie($query, $arr);
    }

    function getSortedQuestionsForVideo($video_id) {
        $query = "SELECT * from {$this->p}iv_question WHERE video_id = :videoId ORDER BY q_time ASC;";
        $arr = array(":videoId" => $video_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function addQuestion($video_id, $question_time, $question_text, $correct_feedback, $incorrect_feedback, $randomize) {
        $query = "INSERT INTO {$this->p}iv_question (video_id, q_time, q_text, correct_fb, incorrect_fb, randomize) VALUES (:videoId, :questionTime, :questionText, :correctFeedback, :incorrectFeedback, :randomize);";
        $arr = array(':videoId' => $video_id, ':questionTime' => $question_time, ':questionText' => $question_text, ':correctFeedback' => $correct_feedback, ':incorrectFeedback' => $incorrect_feedback, ":randomize" => $randomize);
        $this->PDOX->queryDie($query, $arr);
        return $this->PDOX->lastInsertId();
    }

    function updateQuestion($question_id, $question_time, $question_text, $correct_feedback, $incorrect_feedback, $randomize) {
        $query = "UPDATE {$this->p}iv_question SET q_time = :questionTime, q_text = :questionText, correct_fb = :correctFeedback, incorrect_fb = :incorrectFeedback, randomize = :randomize WHERE question_id = :questionId;";
        $arr = array(':questionTime' => $question_time, ':questionText' => $question_text, ':correctFeedback' => $correct_feedback, ':incorrectFeedback' => $incorrect_feedback, ":randomize" => $randomize, ":questionId" => $question_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function deleteQuestion($video_id, $question_id) {
        $query = "DELETE FROM {$this->p}iv_question WHERE video_id = :videoId AND question_id = :questionId;";
        $arr = array(":videoId" => $video_id, ":questionId" => $question_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function getSortedAnswersForQuestion($question_id) {
        $query = "SELECT * from {$this->p}iv_answer WHERE question_id = :questionId ORDER BY answer_order ASC;";
        $arr = array(":questionId" => $question_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function addAnswer($question_id, $answer_order, $is_correct, $answer_text) {
        $query = "INSERT INTO {$this->p}iv_answer (question_id, answer_order, is_correct, a_text) VALUES (:questionId, :answerOrder, :isCorrect, :answerText);";
        $arr = array(":questionId" => $question_id, ":answerOrder" => $answer_order, ":isCorrect" => $is_correct, ":answerText" => $answer_text);
        $this->PDOX->queryDie($query, $arr);
        return $this->PDOX->lastInsertId();
    }

    function updateAnswer($answer_id, $answer_order, $is_correct, $answer_text) {
        $query = "UPDATE {$this->p}iv_answer SET answer_order = :answerOrder, is_correct = :isCorrect, a_text = :answerText WHERE answer_id = :answerId;";
        $arr = array(":answerOrder" => $answer_order, ":isCorrect" => $is_correct, ":answerText" => $answer_text, ":answerId" => $answer_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function deleteAnswer($question_id, $answer_id) {
        $query = "DELETE FROM {$this->p}iv_answer WHERE question_id = :questionId AND answer_id = :answerId;";
        $arr = array(":questionId" => $question_id, ":answerId" => $answer_id);
        $this->PDOX->queryDie($query, $arr);
    }
}
