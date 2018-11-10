<?php
namespace App\Http\Controllers\Api\Campus_help;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Campus_user;

class Owner extends Controller{
    /**
        * 说明：该文件获取用户学校、邮箱信息
    */
    public function getUserInformation(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'origin:非法访问'];
        }
        //*******uid索引**************
        $uidIndex = $request->uidIndex;
        if (is_null($uidIndex) || strlen(trim($uidIndex))==0) {
            return ['error'=>'uidIndex接收异常'];
        }

        //***********获取uid**************
        $uid = Redis::hget('uid',$uidIndex);
        if (!$uid){
            return ['error'=>'用户ID索引失效'];
        }

//        $sql = "select user_school,user_email from campus_user where user_unique_id=?";
        $result = Campus_user::where(['user_unique_id'=>$uid])->first(['user_school','user_email'])->toArray();
        dump($result);
        $newArray = array();
        foreach ($result as $arr) {
            foreach ($arr as $key => $value) {
                $newKey = substr($key, strlen('user_'));
                $newArray[$newKey] = is_null($value) ? '' : $value;
                //邮箱加密传输返回
                if ($newKey=='email') {
                    $newArray[$newKey] = is_null($value) ? base64_encode('') : base64_encode($value);
                }
            }
        }
        unset($result);
        return ['msg'=>$newArray];

    }
}