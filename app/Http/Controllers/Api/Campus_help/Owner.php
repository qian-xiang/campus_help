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
        $email = $result['user_email'] ? $result['user_email'] : '';
        $school = $result['user_school'] ? $result['user_school'] : '';
        if ($email!==''){
            $email = base64_encode($email);
        }

        return ['msg'=>['email'=>$email,'school'=>$school]];
    }
    /**
     * 说明：处理前台【个人中心】传来的修改用户名、QQ邮箱的操作
     */
    public function modifyInformation(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'Referer:非法访问'];
        }
        $error ='';
        $msg = '';
        //*******uid索引**************
        $uidIndex = $request->uidIndex;
        if (is_null($uidIndex) || strlen(trim($uidIndex))==0) {
            return ['error'=>'uidIndex接收异常'];
        }

        $school = $request->school;
        $email = $request->email;

        if (is_null($school)||is_null($email)||strlen(trim($school))==0 ||strlen(trim($email))==0) {
            return ['error'=>'学校或邮箱不能设置为空'];
        }
//        *********获取uid**************
        $uid = Redis::hget('uid',$uidIndex);
        if (!$uid){
            return ['error'=>'用户ID索引失效'];
        }

//        $sql = "update campus_user set user_school=?,user_email=? where user_unique_id=?";
        //查询指定用户唯一ID的记录
        $affected_rows = Campus_user::where(['user_unique_id'=>$uid])->update(['user_school'=>$school,'user_email'=>$email]);
        if ($affected_rows > 0){
            $msg = '修改成功';
        }else{
            $error = '请输入欲修改后的学校和邮箱';
        }
        return ['msg'=>$msg,'error'=>$error];
    }
}