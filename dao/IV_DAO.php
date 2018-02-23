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

    function getVideoInfoById($video_id) {
        $query = "SELECT video_type, video_url FROM {$this->p}iv_video WHERE video_id = :videoId;";
        $arr = array(':videoId' => $video_id);
        return $this->PDOX->rowDie($query, $arr);
    }
}
