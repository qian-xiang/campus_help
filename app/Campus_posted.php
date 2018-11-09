<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Campus_posted extends Model
{
    protected $connection = 'mysql';
    /**
     * 与模型关联的数据表。
     *
     * @var string
     */
    protected $table = 'campus_posted';
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
    protected $fillable = ['posted_id','posted_unique_id','user_nickname','user_head_image',
        'posted_title','posted_is_upload_img','posted_sort','posted_reward','posted_status','posted_time'];

    /**
     * 获取与用户关联的电话号码
     */
    public function campus_user()
    {
        return $this->hasOne('App\Campus_user','user_unique_id','posted_unique_id');
    }
}
