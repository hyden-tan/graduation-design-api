<?php
namespace  app\index\model;
use think\Model;
use think\Session;
use think\Db;
use think\Log;


class User extends Model{
    protected $table="user";
    
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = 'update_at';  
}
