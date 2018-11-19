<?php
namespace App\Http\Controllers\Api\Campus_help;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Campus_user;
use App\Campus_posted;
use App\Campus_question;
use Illuminate\Support\Facades\DB;

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
    public function showRequiredRecord(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'origin:非法访问'];
        }

        $uidIndex = $request->uidIndex;
        if (!$request->requireType){
            return ['error'=>'非法访问'];
        }

        if (is_null($uidIndex) || strlen(trim($uidIndex))==0) {
            return ['error'=>'$uidIndex接收异常'];
        }

        //***********获取uid**************
        $uid = Redis::hget('uid',$uidIndex);
        if (!$uid){
            return ['error'=>'用户ID索引不存在'];
        }

//        $sql = "select posted_id,posted_title,posted_reward,posted_sort,posted_status,posted_time from campus_posted where posted_unique_id=? order by posted_time desc,posted_status asc";
        $result = Campus_posted::where(['posted_unique_id'=>$uid])->orderBy('posted_time','DESC')
            ->orderBy('posted_status','ASC')->get(['posted_id as id','posted_title as title',
                'posted_reward as reward', 'posted_sort as sort','posted_status as status',
                'posted_time as time'])->toArray();

        if (count($result)==0) {
            return ['error'=>'您尚未发表过求助贴'];
        }
        //创建一个空数组
        $newArray = array();

        foreach ($result as $key => $value) {
//            $newArray[$key]['id'] = $value['posted_id'];
//            $newArray[$key]['title'] = $value['posted_title'];
//            $newArray[$key]['reward'] = $value['posted_reward'];
//            $newArray[$key]['sort'] = $value['posted_sort'];
//            $newArray[$key]['status']= $value['posted_status'] == 0 ? '未结帖' : '已结帖';
            $result[$key]['status'] = $value['status'] == 0 ? '未结帖' : '已结帖';
            $result[$key]['time'] = date('Y-m-d',$value['time']);

            //获取帖子喜欢的数量和浏览量
            $arr = Redis::hget('favorite', $value['id']);
            $arr = json_decode($arr,true);
            $favoriteCount = $arr == null ? 0 : count($arr);

            //*******获取帖子的浏览量**********
            $arr = Redis::hget('browseCount',$value['id']);
            $arr = json_decode($arr,true);
            $browseCount = $arr == null ? 0 : count($arr);

            //*********将记录数添加到数组中************
            $result[$key]['favoriteCount'] = $favoriteCount;
            $result[$key]['browseCount'] = $browseCount;

        }
        return ['msg'=>$result];
    }
    /**
     * 说明：该文件处理用户执行删除请求记录的操作,目前对于删除目录下的图片没有做验证处理,有时间要做下验证
     *
     */
    public function deleteRequiredRecord(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'origin:非法访问'];
        }
        $paperId = $request->paperId;
        if (is_null($paperId) || strlen(trim($paperId))==0) {
            return ['error'=>'参数非法'];
        }

        //开始删除数据库中的记录
//        $sql = "delete from campus_posted where posted_id=?";
        $result = Campus_posted::where(['posted_id'=>$paperId])->delete();

        if ($result ==='操作成功') {
            //若帖子是有图，则删除以帖子ID命名的文件夹
            $imgPath = './images/campus_help/published/'.$_POST['paperId'];
            deleteAllFiles($imgPath);
            //此处应该把删除失败的文件或目录的记录添加到Redis中方便事后处理
        }
        return ['deleteRecord'=>$result];
    }
    /**
     * 说明：该文件处理用户执行确认结帖的请求
     *
     */
    public function confirmFinish(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'origin:非法访问'];
        }
        $paperId = $request->paperId;
        if (is_null($paperId) || strlen(trim($paperId))==0) {
            return ['error'=>'参数非法'];
        }

        //开始更新数据库中的记录
//        $sql = "update campus_posted set posted_status=1 where posted_id=?";
        $result = Campus_posted::where(['posted_id'=>$paperId])->update(['posted_status'=>1]);

        if ($result > 0) {
            return ['success'=>'确认结帖成功'];
        }else{
            return ['error'=>'确认结帖失败'];
        }
    }
    /**
     * 说明：该文件处理用户点击我的喜欢的操作
     */
    public function ownerLove(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'origin:非法访问'];
        }
        //*******uid索引**************
        $uidIndex = $_POST['uidIndex'];
        if (is_null($uidIndex) || strlen(trim($uidIndex))==0) {
            return ['error'=>'uidIndex接收异常'];
        }

        //开始获取Redis中存储的uid
        $uid = Redis::hget('uid',$uidIndex);
        if (!$uid){
            return ['error'=>'用户ID索引失效'];
        }
        $userLove = 'userLove';
        //***********获取uid**************
        $paperArray = Redis::hget($userLove,$uid);
        if (!$paperArray){
            return ['error'=>'您还没有喜欢的帖子'];
        }
        $paperArray = json_decode($paperArray,true);

        if (count($paperArray)==0) {
            return ['error'=>'您还没有喜欢的帖子'];
        }

//        $sql = "select posted_id,posted_title,posted_sort,posted_status,posted_time from campus_posted where posted_id=?";
        $result = Campus_posted::whereIn('posted_id',$paperArray)->get(['posted_id as id','posted_title as title',
        'posted_sort as sort','posted_status as status','posted_time as time'])->toArray();
        if (count($result) == 0) {
            return ['error'=>'您还没有喜欢的帖子'];
        }

        for ($index = 0 ;$len = count($result) , $index < $len ; $index++) {
            foreach ($result[$index] as $key => $value) {
                switch ($key) {
                    case 'status':
                        $result[$index][$key] = $value == 0 ? '未结帖' : '已结帖';
                        break;
                    case 'time':
                        $result[$index][$key] = date('Y-m-d',$value);
                        break;
                    case 'id':
                        //获取帖子喜欢的数量和浏览量
                        $arr = Redis::hget('favorite', $value);
                        $arr = json_decode($arr,true);
                        $favoriteCount = $arr === null ? 0 : count($arr);

                        //*******获取帖子的浏览量**********
                        $arr = Redis::hget('browseCount',$value);
                        $arr = json_decode($arr,true);
                        $browseCount = $arr === null ? 0 : count($arr);

                        //*********将记录数添加到数组中************
                        $result[$index]['favoriteCount'] = $favoriteCount;
                        $result[$index]['browseCount'] = $browseCount;
                        break;

                    default:
                        # code...
                        break;
                }

            }
        }

                return ['msg'=>$result];
    }
    /**
     * 说明：该文件处理用户点击取消喜欢的操作
     */
    public function deleteMyLove(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'origin:非法访问'];
        }
        //*******uid索引**************
        $uidIndex = $request->uidIndex;
        $paperId = $request->paperId;

        if (is_null($uidIndex) || strlen(trim($uidIndex))==0) {
            return ['error'=>'uidIndex接收异常'];
        }

        if (is_null($paperId) || strlen(trim($paperId))==0) {
            return ['error'=>'paperId接收异常'];
        }

        //获取用户唯一ID
        $uid = Redis::hget('uid',$uidIndex);
        if (!$uid){
            return ['error'=>'用户ID索引失效'];
        }
        $userLove = 'userLove';
        //***********获取uid**************
        $paperArray = Redis::hget($userLove,$uid);
        if ($paperArray == null){
            return ['error'=>'您还没有喜欢的帖子'];
        }
        $paperArray = json_decode($paperArray,true);
        if (count($paperArray)==0) {
            return ['error'=>'您还没有喜欢的帖子'];
        }

        //**********如果值存在，删除该值，即求数组和单值数组的差集***********
        if (in_array($paperId, $paperArray)) {
            $arr = array($paperId);
            $paperArray = json_encode(array_diff($paperArray, $arr));
            Redis::hset($userLove,$uid,$paperArray);

            //再删除以帖子ID为索引的哈希记录
            $uidArray = Redis::hget('favorite',$paperId);
            $uidArray = json_decode($uidArray,true);

            if (in_array($uid, $uidArray)) {
                $arr = array($uid);
                $uidArray = json_encode(array_diff($uidArray, $arr));
                Redis::hset('favorite',$paperId,$uidArray);
                return ['msg'=>'取消喜欢成功'];
            }else{
                return ['error'=>'该用户不存在'];
            }

        }else{
            return ['error'=>'该帖不存在'];
        }

    }
    /**
     * 说明：处理用户点击问题帮助的逻辑
     */
    public function getQuestionList(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'origin:非法访问'];
        }
        $requestType = $request->requestType;
        if ($requestType !== 'getQuestionList'){
            return ['error'=>'非法访问：requestType'];
        }

//        $sql = "select question_content,question_answer from campus_question";
        $result = Campus_question::all(['question_content as content','question_answer as answer'])->toArray();
        if (count($result)==0) {
            return ['error'=>'问题列表不存在，请咨询客服。'];
        }
        return ['msg'=>$result];
    }
}