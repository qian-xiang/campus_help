<?php
namespace App\Http\Controllers\Api\Campus_help;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Campus_posted;
use App\Campus_user;
use App\Campus_accusation;
use Illuminate\Support\Facades\DB;

class Index extends Controller
{
    /**
     * 说明：用于处理首页显示请求数据和助客数据
     */
    public function showData(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'origin:非法访问'];
        }
        $uidIndex = $request->uidIndex;
        if (!$uidIndex){
            return ['error'=>'非法访问:uidIndex'];
        }
        $school = $request->school;
        if (!$school){
            return ['error'=>'非法访问:school'];
        }

        $uid = Redis::hget('uid',$uidIndex);
        if (!$uid){
            return ['error'=>'用户ID索引不存在'];
        }
        //设定加载一次显示5个
        $pageContainer = 5;

        /*        $sql = "select posted_id,posted_unique_id,user_nickname,user_head_image,
        posted_title,posted_is_upload_img,posted_sort,posted_reward,posted_status,posted_time
        from campus_posted inner join campus_user on user_unique_id = posted_unique_id
        where posted_school=? order by posted_status asc,posted_time desc limit 0,$recordCount";*/
//        $result = Campus_posted::where(['posted_school'=>$school])->orderBy('posted_status','ASC')
//            ->orderBy('posted_time','DESC')->paginate($pageContainer,['posted_id as id','posted_unique_id as unique_id',
//                'user_nickname as nickname','user_head_image as head_image','posted_title as title','posted_is_upload_img as is_upload_img'
//                ,'posted_sort as sort', 'posted_reward as reward','posted_status as status','posted_time as time'])
//            ->campus_user->toArray();
        $result = DB::table('campus_posted')->where(['campus_posted.posted_school'=>$school])->join('campus_user','campus_user.user_unique_id','=','campus_posted.posted_unique_id')
            ->select('posted_id as id','posted_unique_id as unique_id',
                'user_nickname as nickname','user_head_image as head_image','posted_title as title','posted_is_upload_img as is_upload_img'
                ,'posted_sort as sort', 'posted_reward as reward','posted_status as status','posted_time as time')
            ->orderBy('campus_posted.posted_status','ASC')
            ->orderBy('campus_posted.posted_time','DESC')->paginate($pageContainer)->toArray();
        $next_page_url = $result['next_page_url'];
        $result = $result['data'];
        if (count($result) === 0){
            return ['error'=>'没有您当前所在学校的帖子。若已登录，请确认是否在个人中心->我的资料设置要浏览的了学校名称。'];
        }

        $error='';
        $receive = array();

        for ($i=0; $i < count($result); $i++) {
            foreach ($result[$i] as $key => $realValue) {
                switch ($key) {
                    case 'time':
                        //计算帖子距离当天的时间  格式：几天前
                        $currentTime = time();
                        //向下取整
                        $realValue = floor(($currentTime - $realValue)/(60*60*24));
                        //格式化时间
                        // $realValue = date('Y-m-d',$realValue);
                        $realValue = $realValue <= 0 ? '今天' : $realValue.'天前';
                        break;
                    case 'status':
                        $realValue = $realValue == 1 ? '已结帖':'未结帖';
                        break;
                    case 'is_upload_img':
                        if ($realValue !== 1) {
                            $realValue=[];
                        }else{
                            $filesArray = array();
                            //列出存储帖子图片的文件夹下的所有图片
                            if ($handle = opendir('./images/campus_help/published/'.$result[$i]->id)){
                                while (($filename = readdir($handle))!==false) {
                                    //*********排除隐藏文件************
                                    if ($filename!=='.' && $filename!=='..') {
                                        $filesArray[] = $filename;
                                    }
                                }
                                closedir($handle);
                            }
                            $realValue = $filesArray;
                        }
                        break;
                    case 'id':
                        //获取该id在Redis中的记录数,该ID在哈希中的值是数组形式 并判断用户ID是否在该帖的【喜欢】中
                        $arr = Redis::hget('favorite', $realValue);
                        $arr = json_decode($arr,true);
                        if (!$arr) {
                            $isFavorite = false;
                        }else{
                            //判断用户ID是否在该帖的【喜欢】中
                            $isFavorite = in_array($uid, $arr) ? true : false ;
                        }
                        $favoriteCount = $arr === null ? 0 : count($arr);
                        $arr = Redis::hget('topRecord', $realValue);
                        $arr = json_decode($arr,true);
                        //判断用户ID是否在该帖的【顶】中
                        if (!$arr) {
                            $isTopRecord = false;
                        }else{
                            //判断用户ID是否在该帖的【喜欢】中
                            $isTopRecord = in_array($uid, $arr) ? true : false ;
                        }
                        $topRecordCount = $arr === null ? 0 : count($arr);
                        //*******获取帖子的浏览量**********
                        $arr = Redis::hget('browseCount',$realValue);
                        $arr = json_decode($arr,true);
                        $browseCount = $arr === null ? 0 : count($arr);

                        //*******获取帖子的助力数**********
                        $arr = Redis::hget('help',$result[$i]->unique_id);

                        if ($arr===null) {
                            $helpCount = 0;
                        }else{
                            $arr = json_decode($arr,true);
                            $helpCount = array_key_exists($realValue,$arr) ? count($arr[$realValue]) : 0 ;
                        }
                        //*********将记录数添加到数组中************
                        $receive[$i]['favoriteCount'] = $favoriteCount;
                        $receive[$i]['isFavorite'] = $isFavorite;
                        $receive[$i]['topRecordCount'] = $topRecordCount;
                        $receive[$i]['isTopRecord'] = $isTopRecord;
                        $receive[$i]['browseCount'] = $browseCount;
                        $receive[$i]['helpCount'] = $helpCount;
                        break;
                    default:
                        # code...
                        break;
                }
                if ($key !='unique_id') {

                    $receive[$i][$key] = $realValue;

                }
            }
            //**********状态排序数组**************
            $statusSortArray[$i] = $receive[$i]['status'];
            //**********发帖时间排序数组**************
            $timeSortArray[$i] = $receive[$i]['time'];
            //**********求助量排序数组**************
            $helpCountSortArray[$i] = $receive[$i]['helpCount'];
        }
        array_multisort($statusSortArray,SORT_ASC,SORT_NUMERIC,$timeSortArray,SORT_ASC,SORT_NUMERIC,$helpCountSortArray,SORT_DESC,SORT_NUMERIC,$receive);
        return ['recordArray'=>$receive,'error'=>$error ,'next_page_url'=>$next_page_url];
    }

    public function  handleWx(Request $request){

        $referer = checkReferer();
        if (!$referer){
            return ['error'=>'非法访问:referer'];
        }
        $requestType = $request->requestType;
        if (!$requestType){
            return ['error'=>'非法访问：requestType'];
        }
        $userCode = $request->userCode;
        if (!$userCode){
            return ['error'=>'非法访问：userCode'];
        }
        $nickName = $request->nickName;
        if (!$nickName){
            return ['error'=>'用户昵称异常'];
        }
        $headImage = $request->headImage;
        if (!$headImage){
            return ['error'=>'用户头像异常'];
        }

        $str = '12345678=';
        //开始获取用户的openId
        $ch = curl_init();//初始化curl
        if (!$ch){
            return ['error'=>'系统错误：curl'];
        }
        $wantSendDataArray = [
            CURLOPT_URL => 'https://api.weixin.qq.com/sns/jscode2session?appid=12345678&secret='.base64_decode($str).'&js_code='.$userCode.'&grant_type=authorization_code',
            CURLOPT_HEADER=>false,
            //作为源码输出而不是直接输出网页
            CURLOPT_RETURNTRANSFER=>true
        ];
        curl_setopt_array($ch,$wantSendDataArray);
        $result = curl_exec($ch);//获取数据
        $result = json_decode($result,true);
        curl_close($ch);//关闭curl

        //判断用户唯一Id是否在用户表存在，如果不存在，则插入该ID、昵称和头像,邮箱和学校置为空字符串
        $uniqid = md5($result['openid']);

        //	$sql = "select user_unique_id,user_nickname,user_head_image from campus_user where user_unique_id='$uniqid'";
        $result = Campus_user::where(['user_unique_id'=>$uniqid])->get(['user_unique_id','user_nickname','user_head_image'])->toArray();
        $campus_user = new Campus_user();

        if (count($result)==0) {
            //不存在则插入
            $campus_user->user_unique_id = $uniqid;
            $campus_user->user_nickname = $nickName;
            $campus_user->user_head_image = $headImage;
            $res_save = $campus_user->save();
            if (!$res_save){
                return ['error'=>'保存账户信息出现错误'];
            }

        }else{
            $errorType = '';
            //如果用户昵称存在，判断用户昵称和头像是否发生了修改。若是已修改，则及时更新数据库里的用户名和头像
            if ($result[0]['user_nickname']!=$nickName) {
                $campus_user->nickName = $nickName;
                $errorType = '昵称';
            }

            if ($result[0]['user_head_image']!=$headImage) {
//                $sql = "insert into campus_user(user_head_image) values(?)";
                $campus_user->headImage = $headImage;
                $errorType = '头像';
            }

            if ($errorType!='') {
                $result = $campus_user->save();
                if (!$result) {
                    return ['error'=>'更新'.$errorType.'出现异常'];
                }
            }

        }

        //*******生成唯一字符串，毫秒级的************
        $fields = md5(uniqid('wx',true));
        // $redis->hset('wxLoginStatus',$fields,json_encode($result));
        // 采用hash方式存储uid
        Redis::hset('uid',$fields,$uniqid);
        //设置键的过期时间，不一定非要设置，后面看是否需要设置过期时间  现在设置5天时间
        // $redis->expire($fields,5*24*60*60);

        //若之前用户已存在uid，则删除redis中的uid
        if ($request->uidIsExist === 'true') {
            //对receivedUid进行合法验证
            $receivedUid = $request->wantSendUid;
            if (!$receivedUid){
                return ['error'=>'uid接收异常:1'];
            }
            if ($receivedUid=='none') {
                return ['error'=>'uid接收异常:1'];
            }elseif(strlen(trim($receivedUid))==0){
                return ['error'=>'uid接收异常:2'];
            }
            //*******开始删除该键*********
            // $redis->delete($receivedUid);
            Redis::hdel('uid',$receivedUid);
        }

        //******uid为唯一的可查询到关键信息的索引***********
        return ['uidIndex'=>$fields];

    }

    public  function  checkSchool(Request $request){
        //*********判断来源是否合法****************
        if(!checkReferer()){
            return ['error'=>'非法访问:Reference'];
        }
        $uidIndex = $request->uidIndex;
        if (!$uidIndex){
            return ['error'=>'非法访问:uidIndex->1'];
        }
        if (is_null($uidIndex) || strlen(trim($uidIndex))==0) {
            return ['error'=>'非法访问:uidIndex->2'];
        }

        //*********通过索引获取用户的唯一ID********************
        $uid = Redis::hget('uid',$uidIndex);
        if (!$uid){
            return ['error'=>'用户ID索引不存在'];
        }
//       $sql = "select user_school from campus_user where user_unique_id=?";
        $user_school = Campus_user::where(['user_unique_id'=>$uid])->first(['user_school'])->toArray();
        $school = $user_school['user_school'];
        if (!$school){
            return ['error'=>'检测到您尚未在【个人中心】的【我的资料】设置学校名称，请前往设置。'];
        }
        return ['school'=>$school];
    }
    public function submitAccusation(Request $request){
        //*********判断来源是否合法****************
        if(!checkReferer()){
            return ['error'=>'非法访问:Reference'];
        }

        $requestType = $request->requestType;
        if (!$requestType){
            return ['error'=>'非法访问：requestType'];
        }
        $accusationType = $request->accusationType;
        if (!$accusationType){
            return ['error'=>'非法访问：accusationType'];
        }
        $paperId = $request->paperId;
        if (!$paperId){
            return ['error'=>'非法访问：paperId'];
        }
        $uidIndex = $request->uidIndex;
        if (!$uidIndex){
            return ['error'=>'非法访问：uidIndex'];
        }

        $uid = Redis::hget('uid',$uidIndex);
        if (!$uidIndex){
            return ['error'=>'用户ID索引不存在'];
        }
        $campus_accusation = new Campus_accusation();
        //*********检验是否已对该帖进行举报**************
//        $sql = "select accusation_post_id,accusation_unique_id from campus_accusation where accusation_post_id=? and accusation_unique_id=?";
        $errorult = Campus_accusation::where(['accusation_post_id'=>$paperId,'accusation_unique_id'=>$uid])
            ->get(['accusation_post_id','accusation_unique_id'])->toArray();
        if (count($errorult)>0) {
            return ['error'=>'您已举报过此贴'];
        }
        //如果举报类型不为【其它】
        if ($accusationType === '其他') {
            //举报类型为【其它】 即要填写举报内容时
            $content = $request->content;

            //如果举报内容为空，则返回报错信息
            if (is_null($content) || strlen(trim($content)) === 0) {
                return ['error'=>'请填写举报内容'];
            }

//            $sql = "insert into campus_accusation (accusation_id,accusation_post_id,accusation_unique_id,accusation_type,accusation_content,accusation_status) values(0,?,?,?,?,0)";
            $campus_accusation->accusation_post_id = $paperId;
            $campus_accusation->accusation_unique_id = $uid;
            $campus_accusation->accusation_type = $accusationType;
            $campus_accusation->accusation_content = $content;
            $campus_accusation->accusation_status = 0;

            $res = $campus_accusation->save();
        }else{
//            $sql = "insert into campus_accusation (accusation_id,accusation_post_id,accusation_unique_id,accusation_type,accusation_status) values(0,?,?,?,0)";
            $campus_accusation->accusation_post_id = $paperId;
            $campus_accusation->accusation_unique_id = $uid;
            $campus_accusation->accusation_type = $accusationType;
            $campus_accusation->accusation_status = 0;

            $res = $campus_accusation->save();
        }
        return $res ? ['msg'=>'举报成功！'] : ['error'=>'系统错误，请重试。若有问题，请联系客服解决。'];
    }
}
