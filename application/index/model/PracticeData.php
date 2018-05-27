<?php
namespace  app\index\model;
use think\Model;
use think\Session;
use think\Db;
use think\Log;


class PracticeData extends Model{
    protected $table="practice_data";

    public function getRangeAndDegree($userId) {
        try {
            $query = "SELECT q.cls AS 'range', q.degree 
                        FROM practice_data AS p LEFT JOIN questions AS q 
                        ON p.question_id = q.id 
                        WHERE p.user_id = $userId AND p.done = 1";

            $res = $this->query($query);

            if ($res) {
                $data = $res[0];
                foreach($res as $value) {
                    if ($value['range'] > $data['range']) {
                        $data['range'] = $value['range'];
                    }

                    if ($value['degree'] > $data['degree']) {
                        $data['degree'] = $value['degree'];
                    }
                }

                return $data;
            } else {
                return [
                    'range' => 0,
                    'degree' => 2, 
                ];
            }
        } catch(Exception $e) {
            return [
                'range' => 0,
                'degree' => 2,
            ];
        }
    }
    
}
