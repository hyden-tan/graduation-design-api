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
Route::post('set_question_done', 'index/Index/setQuestionDone');

// 保存用户学习时间
Route::get('record_study_time', 'index/Index/recordStudyTime' );

// 获取用户已完成的考试
Route::get('get_tests', 'index/Index/getUserTests');

// 组卷
Route::get('create_test', 'index/Index/createTest');

// 获取考试题目
Route::get('get_test_questions', 'index/Index/getTestQuestions');

// 获取提交记录
Route::post('get_code', 'index/Index/getCode');