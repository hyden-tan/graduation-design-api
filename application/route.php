<?php

use think\Route;

// 用户登录、注册
Route::post('user', 'index/Index/userLogin');

// 编译运行程序
Route::post('run', 'index/Index/runCode');

// 获取题库 
Route::get('get_questions', 'index/Index/getQuestions');

// 根据题目id获取题目
Route::get('get_question', 'index/Index/getQuestion');

// 设置完成题目
Route::get('set_question_done', 'index/Index/setQuestionDone');
