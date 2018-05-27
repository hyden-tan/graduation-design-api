<?php
namespace  app\index\model;
use think\Model;
use think\Session;
use think\Db;
use think\Log;


class StudyData extends Model{
    protected $table="study_data";

    public function getStudyRange($userId) {
        try {
            $res =  $this->where(['user_id' => $userId])->select();
            if ($res) {
                $range = 0;
                foreach($res as $value) {
                    if ($value['chapter_index'] > $range) {
                        $range = $value['chapter_index']; 
                    }
                }

                return $range;
            } else {
                return 0;
            }
        } catch (Exception $e) {
            return 0;
        }
    }
    
}
