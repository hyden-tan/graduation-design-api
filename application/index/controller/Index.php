<?php
namespace app\index\controller;
use think\Session;
use think\Db;
use app\index\model\User;
use app\index\model\Questions;
use app\index\model\PracticeData;
use app\index\model\StudyData;
use app\index\model\Test;
use app\index\model\TestQuestions;
use think\Log;

function json($str) {
    return json_encode($str, JSON_UNESCAPED_UNICODE);
}

class Index
{
    public function userLogin() {
        $data = json_decode(file_get_contents('php://input'));
        $user = new User();

        try {
            if ($data->mode == '登录') {
                $u = $user->get(['user_name'=> $data->userName]);
                if (!$u) {
                    return json([ 'code' => 1, 'errMsg' => '用户不存在']);
                }
    
                if ($u->password != $data->password) {
                    return json([ 'code' => 1, 'errMsg' => '密码错误']);
                }
    
                Session::set('userId', $u->id);
                return json([ 'code' => 0, 'message' => '登录成功', 'userId' => $u->id]);
    
            } else {
                $user->user_name = trim($data->userName);
                $user->password = trim($data->password);
                $user->save();
    
                Session::set('userId', $user->id);
                return json([ 'code' => 0, 'message' => '注册成功', 'userId' => $user->id]);
            }   
        } catch(Exception $e) {
            return json([ 'code' => 2, 'message' => $e->message]);
        }
    }

    public function runCode() {
        $data = json_decode(file_get_contents('php://input'));
        $path = "result.txt";
        shell_exec('rm ./code');
        shell_exec('rm ./code.c');
        shell_exec('rm ./code.o');
        shell_exec('rm ./' . $path);

        $practiceDataModel = new PracticeData();
        
        if ($data->userId !== -1 && $data->type === 'practice') {
            $query = ['user_id' => $data->userId, 'question_id' => $data->questionId];
            try {
                $cur = $practiceDataModel->where($query)->find();
                if ($cur) {
                    $practiceDataModel->where($query)->update([
                        "do_count" => $cur->do_count + 1,
                        "answer" => $data->code,
                        "do_date" => date("Y-m-d H:i:s")
                    ]);
                } else {
                    $practiceDataModel->user_id = $data->userId;
                    $practiceDataModel->question_id = $data->questionId;
                    $practiceDataModel->do_count = 1;
                    $practiceDataModel->answer = $data->code;
                    $practiceDataModel->do_date = date("Y-m-d H:i:s");
                    
                    $practiceDataModel->save();
                }
            } catch(Exception $e) {
                return json([ 'code' => 1, 'errMsg' => '服务器端异常！']);
            }
        } else {
            $testQuestionsModel = new TestQuestions();
            try {
                $query = ['test_id' => $data->testId, 'question_id' => $data->questionId];
                $testQuestionsModel->where($query)->update([
                    "code" => $data->code,
                ]);
            } catch(Exception $e) {
                return json([ 'code' => 1, 'errMsg' => '服务器端异常！']);
            } 
        }

        try {
            $codeFile = fopen("code.c", "w");
            fwrite( $codeFile, $data->code);
        } catch(Exception $e) {
            return json([ 'code' => 1, 'errMsg' => '服务器端异常！']);
        }

        exec('gcc -c code.c > result.txt 2>&1 &', $res, $status);
        sleep(1);

        if(file_exists($path)){  
            $size = filesize($path);
            if ($size > 0) {
                $fileCon = "";  
                $fp = fopen($path,"r+");  
                $fileCon = fread($fp,filesize($path)); 
                fclose($fp); 
                return json([ 'code' => 0, 'result' => $fileCon]);
            }
        }

        shell_exec('gcc code.o -o code');
        $result = shell_exec('./code');
        if ($result) {
            return json([ 'code' => 0, 'result' => $result]);
        }
    }

    // 获取所有题目
    public function getQuestions() {
        $question = new Questions();
        
        return json($question->select());
    }
    
    // 根据id获取题目
    public function getQuestion($id) {
        $question = new Questions();

        return json($question->where('id', $id)->find());
    }

    // 完成题目
    public function setQuestionDone() {
        $data = json_decode(file_get_contents('php://input'));
        $testQuestionsModel = new TestQuestions();
        $practiceDataModel = new PracticeData();
        $testModel = new Test();

        if ($data->type === 'test') {
            $testQuestionsModel->where([
                "test_id" => $data->testId,
                "question_id" => $data->questionId
            ])->update([
                "done" => 1
            ]);  

            $res = $testQuestionsModel->where('test_id', $data->testId)->select();

            $done = 0;
            $total = sizeof($res);
            foreach($res as $value) {
                if ($value['done'] === 1) {
                    $done ++;
                }
            }

            $score = (100.0/sizeof($res)) * $done;

            $testModel->where('id', $data->testId)->update([
                'score'=> $score
            ]);

            return json([ 'code' => 0]);   
        } else {
            try {
                $practiceDataModel->where([
                    "user_id" => $data->userId,
                    "question_id" => $data->questionId
                ])->update([
                    "done" => 1
                ]); 
    
                return json([ 'code' => 0]);  
            } catch (Exception $e) {
                return json([ 'code' => 1, 'errMsg' => '服务器端异常！']); 
            }
        }

    }

    // 记录学习时间
    public function recordStudyTime($userId, $chapter, $time, $chapterIndex) {
       $studyDataModel = new StudyData();

       try {
           $query = ['user_id'=>$userId, 'chapter'=>$chapter];
           $cur = $studyDataModel->where($query)->find();

           if ($cur) {
                $studyDataModel->where($query)->update([
                    'time_r'=> $cur->time_r + $time
                ]);
           } else {
                $studyDataModel->user_id = $userId;
                $studyDataModel->time_r = $time;
                $studyDataModel->chapter = $chapter;
                $studyDataModel->chapter_index = $chapterIndex;

                $studyDataModel->save();
           }

           return json([ 'code' => 0 ]);
       } catch (Exception $e) {
           return json([ 'code' => 1, 'errMsg' => '服务器端异常！' ]);
       }
    }

    // 获取用户已完成的考试
    public function getUserTests($userId) {
        $test = new Test();
        $res = $test->where('user_id', $userId)->select();

        return json([ 'code' => 0, 'data' => $res ]);
    }

    // 生成考试
    public function createTest($userId) {
        $praceticeModel = new PracticeData();
        $studyModel = new StudyData();
        $questionsModel = new Questions();
        $testQuestionsModel = new TestQuestions();
        $testModel = new Test();

        $res = $testModel->where('date', '>', date('Y-m-d H:i:s', time() - 2*60*60))
                ->where('complete', 0)
                ->where('user_id', $userId)
                ->find();

        if($res) {
            return json(['code'=>1, 'errMsg'=>'当前有正在进行中的考试！'], JSON_UNESCAPED_UNICODE);
        }

        $range1 = $studyModel->getStudyRange($userId);
        $praceticeData = $praceticeModel->getRangeAndDegree($userId);
        
        $range = $range1 > $praceticeData['range'] ? $range1 : $praceticeData['range']; 
        $degree = $praceticeData['degree'] + 1 > 5 ? 5 : $praceticeData['degree'] + 1; 

        $res = $questionsModel->where('cls', '<=', $range)->where('degree', '<=', $degree)->select();
        $testQestions;

        if (sizeof($res) <= 5) {
            $testQestions = $res;
        } else {
            shuffle($res);
            $testQestions = array_slice($res, 0, 5);
        }

        $testData = [
            'user_id' => $userId,
            'date' => date('Y-m-d H:i:s')
        ];

        $testId = $testModel->insertGetId($testData);

        try {
            foreach($testQestions as $question) {
                $testQuestionsModel->insert([
                    'test_id' => $testId,
                    'question_id' => $question['id']
                ]);
            }
            return json(['code' => 0, 'test_id' => $testId]);
        } catch (Exception $e) {
            return json(['code' => 1, 'errMsg' => $e->getMessage()]);
        }
        
    }

    // 获取某场考试所有题目
    public function getTestQuestions($testId) {
        $query = "SELECT q.id, q.title, tq.done
            FROM test_questions AS tq LEFT JOIN questions q
            ON tq.question_id = q.id
            WHERE tq.test_id = $testId ORDER BY q.degree ASC";
        
        $res = Db::query($query);

        return json($res);
    }

    // 获取代码
    public function getCode() {
        $data = json_decode(file_get_contents('php://input'));
        $testQuestionsModel = new TestQuestions();
        $practiceDataModel = new PracticeData();
       
        if ($data->type === 'test') {
            $res = $testQuestionsModel->where([
                'test_id'=>$data->testId,
                'question_id'=>$data->questionId
            ])->find();

            return json($res['code']);
        } else {
            $res = $practiceDataModel->where([
                'user_id'=>$data->userId,
                'question_id'=>$data->questionId
            ])->find();

            if ($res) {
                return json($res['answer']);
            } else {
                return '';
            }
        }
    }
}
