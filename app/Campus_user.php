<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Campus_user extends Model
{
    protected $connection = 'mysql';
    /**
     * 与模型关联的数据表。
     *
     * @var string
     */
    protected $table = 'campus_user';
    /**
     * 该模型是否被自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;
    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = ['user_id','user_username','user_email','user_school',
        'user_head_img','user_regtime','user_last_time','user_current_time',
        'user_token','user_token_time','user_status','user_reg_ip','user_last_ip','user_current_ip'];
}
