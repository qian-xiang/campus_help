<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Campus_accusation extends Model
{
    protected $connection = 'mysql';
    /**
     * 与模型关联的数据表。
     *
     * @var string
     */
    protected $table = 'campus_accusation';

    //指定主键
    protected $primaryKey = 'accusation_id';
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
    protected $fillable = ['accusation_id','accusation_post_id',
        'accusation_unique_id','accusation_type','accusation_content','accusation_status'];
}
