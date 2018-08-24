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
        $context = $this->PDOX->rowDie($query, $arr);
        return $context["video_id"];
    }

    function createVideo($context_id, $link_id, $user_id, $video_url, $video_type, $video_title) {
        $query = "INSERT INTO {$this->p}iv_video (link_id, context_id, user_id, video_url, video_type, video_title) VALUES (:linkId, :contextId, :userId, :videoUrl, :videoType, :videoTitle);";
        $arr = array(':linkId' => $link_id, ':contextId' => $context_id, ':userId' => $user_id, ':videoUrl' => $video_url, ':videoType' => $video_type, ':videoTitle' => $video_title);
        $this->PDOX->queryDie($query, $arr);
        return $this->PDOX->lastInsertId();
    }

    function updateVideoTitle($video_id, $video_title) {
        $query = "UPDATE {$this->p}iv_video SET video_title = :videoTitle WHERE video_id = :videoId;";
        $arr = array(':videoTitle' => $video_title, ':videoId' => $video_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function deleteVideoAndQuestions($video_id) {
        $query = "DELETE FROM {$this->p}iv_video WHERE video_id = :videoId;";
        $arr = array(':videoId' => $video_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function getVideoInfoById($video_id) {
        $query = "SELECT video_type, video_url, video_title FROM {$this->p}iv_video WHERE video_id = :videoId;";
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

    function recordResponse($user_id, $question_id, $answer_id) {
        $query = "INSERT INTO {$this->p}iv_response (user_id, question_id, answer_id) VALUES (:userId, :questionId, :answerId);";
        $arr = array(":userId" => $user_id, ":questionId" => $question_id, ":answerId" => $answer_id);
        $this->PDOX->queryDie($query, $arr);
        return $this->PDOX->lastInsertId();
    }

    function deleteUserResponsesForQuestion($user_id, $question_id) {
        $query = "DELETE FROM {$this->p}iv_response WHERE user_id = :userId AND question_id = :questionId;";
        $arr = array(":userId" => $user_id, ":questionId" => $question_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function getResponse($user_id, $question_id, $answer_id) {
        $query = "SELECT * FROM {$this->p}iv_response WHERE user_id = :userId AND question_id = :questionId AND answer_id = :answerId;";
        $arr = array(':userId' => $user_id, ':questionId' => $question_id, ':answerId' => $answer_id);
        return $this->PDOX->rowDie($query, $arr);
    }

    function getStudentsWithResponses($video_id) {
        $query = "SELECT DISTINCT r.user_id FROM {$this->p}iv_response r JOIN {$this->p}iv_question q ON r.question_id = q.question_id WHERE q.video_id = :videoId;";
        $arr = array(':videoId' => $video_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function getStudents($video_id) {
        $query = "SELECT DISTINCT user_id FROM {$this->p}iv_finished WHERE video_id = :videoId;";
        $arr = array(':videoId' => $video_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function markStudentAsStarted($video_id, $user_id) {
        $query = "UPDATE {$this->p}iv_finished SET started = 1 where video_id = :videoId AND user_id = :userId;";
        $arr = array(':videoId' => $video_id, ':userId' => $user_id);
        $this->PDOX->queryDie($query, $arr);
        return $this->PDOX->lastInsertId();
    }

    function markStudentAsFinished($video_id, $user_id) {
        $query = "UPDATE {$this->p}iv_finished SET finished = 1 where video_id = :videoId AND user_id = :userId;";
        $arr = array(':videoId' => $video_id, ':userId' => $user_id);
        $this->PDOX->queryDie($query, $arr);
        return $this->PDOX->lastInsertId();
    }

    function markStudentNumberCorrect($video_id, $user_id, $num_correct) {
        $query = "UPDATE {$this->p}iv_finished SET num_correct = :num_correct where video_id = :videoId AND user_id = :userId;";
        $arr = array(':videoId' => $video_id, ':userId' => $user_id, ':num_correct' => $num_correct);
        $this->PDOX->queryDie($query, $arr);
    }

    function hasStudentStarted($video_id, $user_id) {
        $query = "SELECT started FROM {$this->p}iv_finished WHERE video_id = :videoId AND user_id = :userId;";
        $arr = array(":videoId" => $video_id, ":userId" => $user_id);
        $started = $this->PDOX->rowDie($query, $arr);
        return $started["started"];
    }

    function isStudentFinished($video_id, $user_id) {
        $query = "SELECT finished FROM {$this->p}iv_finished WHERE video_id = :videoId AND user_id = :userId;";
        $arr = array(":videoId" => $video_id, ":userId" => $user_id);
        $finished = $this->PDOX->rowDie($query, $arr);
        return $finished["finished"];
    }

    function numCorrectForStudent($video_id, $user_id) {
        $query = "SELECT num_correct FROM {$this->p}iv_finished WHERE video_id = :videoId AND user_id = :userId;";
        $arr = array(":videoId" => $video_id, ":userId" => $user_id);
        $num_correct = $this->PDOX->rowDie($query, $arr);
        return $num_correct["num_correct"];
    }

    function findDisplayName($user_id) {
        $query = "SELECT displayname FROM {$this->p}lti_user WHERE user_id = :user_id;";
        $arr = array(':user_id' => $user_id);
        $context = $this->PDOX->rowDie($query, $arr);
        return $context["displayname"];
    }

    function countQuestions($video_id)
    {
        $query = "SELECT COUNT(*) as Count FROM {$this->p}iv_question WHERE video_id = :video_id;";
        $arr = array(':video_id' => $video_id);
        $count = $this->PDOX->rowDie($query, $arr);
        return $count["Count"];
    }

    function getTsugiUserId($userId) {
        $query = "SELECT * FROM {$this->p}lti_user WHERE user_key = :userId;";
        $arr = array(':userId' => $userId);
        $ltiUser = $this->PDOX->rowDie($query, $arr);
        if ($ltiUser !== false) {
            return $ltiUser["user_id"];
        } else {
            return false;
        }
    }

    function findVideosForImport($user_id) {
        $query = "SELECT v.*, c.title as sitetitle FROM {$this->p}iv_video v join {$this->p}lti_context c on v.context_id = c.context_id WHERE v.user_id = :userId ";
        $arr = array(':userId' => $user_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function createFinishRecordIfNotExist($videoId, $userId) {
        $query = "SELECT * FROM {$this->p}iv_finished WHERE video_id = :videoId AND user_id = :userId;";
        $arr = array(":videoId" => $videoId, ":userId" => $userId);
        $finished = $this->PDOX->rowDie($query, $arr);
        if (!$finished) {
            $insert = "INSERT INTO {$this->p}iv_finished (video_id, user_id) VALUES (:videoId, :userId)";
            $this->PDOX->queryDie($insert, $arr);
        }
    }

}
