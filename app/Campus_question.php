<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Campus_question extends Model
{

    protected $connection = 'mysql';
    /**
     * 与模型关联的数据表。
     *
     * @var string
     */
    protected $table = 'campus_question';
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
    protected $fillable = ['question_id','question_content','question_answer'];

}
