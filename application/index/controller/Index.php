<?php
namespace app\index\controller;
use think\Session;
use think\Db;
use app\index\model\User;
use app\index\model\Questions;
use app\index\model\PracticeData;
use think\Log;

function json($str) {
    return json_encode($str, JSON_UNESCAPED_UNICODE);
}

class Index
{
    public function index()
    {
        return '<style type="text/css">*{ padding: 0; margin: 0; } .think_default_text{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p> ThinkPHP V5<br/><span style="font-size:30px">十年磨一剑 - 为API开发设计的高性能框架</span></p><span style="font-size:22px;">[ V5.0 版本由 <a href="http://www.qiniu.com" target="qiniu">七牛云</a> 独家赞助发布 ]</span></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="ad_bd568ce7058a1091"></think>';
    }

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
        shell_exec('rm ./code');
        shell_exec('rm ./code.c');
        shell_exec('rm ./code.o');

        Log::record($data);
        $practiceDataModel = new PracticeData();

        if ($data->userId !== -1) {
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
        }

        try {
            $codeFile = fopen("code.c", "w");
            fwrite( $codeFile, $data->code);
        } catch(Exception $e) {
            return json([ 'code' => 1, 'errMsg' => '服务器端异常！']);
        }

        $result = shell_exec('gcc -c code.c');

        shell_exec('gcc code.o -o code');
        $result = shell_exec('./code');
        if ($result) {
            return json([ 'code' => 0, 'result' => $result]);
        }

    }

    // 获取所有题目
    public function getQuestions() {
        $question = new Questions();
        
        return json_encode($question->select());
    }
    
    // 根据id获取题目
    public function getQuestion($id) {
        $question = new Questions();

        return json_encode($question->where('id', $id)->find());
    }

    // 完成题目
    public function setQuestionDone($userId, $questionId) {
        $practiceDataModel = new PracticeData();

        try {
            $practiceDataModel->where([
                "user_id" => $userId,
                "question_id" => $questionId
            ])->update([
                "done" => 1
            ]); 

            return json([ 'code' => 0]);  
        } catch (Exception $e) {
            return json([ 'code' => 1, 'errMsg' => '服务器端异常！']); 
        }
    }
}
