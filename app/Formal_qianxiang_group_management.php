<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Formal_qianxiang_group_management extends Model
{
    protected $connection = 'mysql_kuq';
    /**
     * 与模型关联的数据表。
     *
     * @var string
     */
    protected $table = 'formal_qianxiang_group_management';
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
    protected $fillable = ['qq','duration','start_time'];
}
