<?php
namespace App\Http\Controllers\Api\Campus_help;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Campus_posted;

class UserAction extends Controller
{
    public function action(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'origin:非法访问'];
        }
        //********************请求类型*******************
        $requestType = $request->requestType;
        if (!$requestType){
            return ['error'=>'非法访问：requestType'];
        }
        //********************帖子id********************
        $paperId = $request->paperId;
        if (!$paperId){
            return ['error'=>'非法访问：id'];
        }
        //********************用户id********************
        $uidIndex = $request->uidIndex;
        if (!$uidIndex){
            return ['error'=>'非法访问：uidIndex'];
        }

        $userLove = 'userLove';

        $uid = Redis::hget('uid',$uidIndex);
        if (!$uid){
            return ['error'=>'用户ID索引不存在'];
        }
        $actioner = Redis::hget($requestType,$paperId);

        //*********自增和自减的时候*************
        switch ($requestType) {
            case 'favorite':
                $increaseActionType = '添加到喜欢成功';
                $decreaseActionType = '您取消了对该帖的喜欢';

                //以此类推，处理以用户ID为索引的用户喜欢的哈希记录
                $userActioner = Redis::hget($userLove,$uid);
                if (!$userActioner) {
                    //再把帖子ID赋给用户ID，实现查询用户喜欢的帖子的功能
                    $newArray = array($paperId);
                    Redis::hset($userLove,$uid,json_encode($newArray));

                }else{
                    $userActioner = json_decode($userActioner,true);

                    //**********如果值存在，删除该值，即求数组和单值数组的差集***********
                    if (in_array($paperId, $userActioner)) {
                        $arr = array($paperId);
                        $userActioner = json_encode(array_diff($userActioner, $arr));

                    }else{
                        //**********如果值不存在，将该值赋给哈希的键***********
                        $userActioner[] = $paperId;
                        $userActioner = json_encode($userActioner);
                    }

                    Redis::hset($userLove,$uid,$userActioner);
                }
                break;
            case 'topRecord':
                $increaseActionType = '顶上去成功';
                $decreaseActionType = '您取消了顶上该帖';
                break;

            default:
                # code...
                break;
        }


        if (!$actioner) {
            $arr = array($uid);
            //此处用户点击【喜欢】的操作,把用户ID赋给帖子ID，帖子ID的值是一个数组
            Redis::hset($requestType,$paperId,json_encode($arr));

            $count = 1;
            $actionType = $increaseActionType;

        }else{
            //以帖子ID为索引时
            $actioner = json_decode($actioner,true);

            $count = count($actioner);

            //**********如果值存在，删除该值，即求数组和单值数组的差集***********
            if (in_array($uid, $actioner)) {
                $arr = array($uid);
                $actioner = json_encode(array_diff($actioner, $arr));

                $count = $count - 1;
                $actionType = $decreaseActionType;

            }else{
                //**********如果值不存在，将该值赋给哈希的键***********
                $actioner[] = $uid;
                $actioner = json_encode($actioner);
                $count = $count + 1;
                $actionType = $increaseActionType;
            }

            Redis::hset($requestType,$paperId,$actioner);


        }


        return ['count'=>$count,'actionType'=>$actionType];
    }
    public function handleBrowseCount(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'origin:非法访问'];
        }
        //********************请求类型*******************
        $requestType = $request->requestType;
        if ($requestType !== 'browseCount'){
            return ['error'=>'非法访问：requestType'];
        }
        //********************帖子id********************
        $paperId = $request->paperId;
        if (!$paperId){
            return ['error'=>'非法访问：paperId'];
        }
        //********************用户id********************
        $uidIndex = $request->uidIndex;
        if (!$uidIndex){
            return ['error'=>'非法访问：uidIndex'];
        }

        $actioner = Redis::hget($requestType,$paperId);
        $uid = Redis::hGet('uid',$uidIndex);
        if (!$uid){
            return ['error'=>'用户ID索引不存在'];
        }
        if (!$actioner) {
            $arr = array($uid);
            //此处用户点击【喜欢】的操作,把用户ID赋给帖子ID，帖子ID的值是一个数组
            Redis::hset($requestType,$paperId,json_encode($arr));

        }else{
            $actioner = json_decode($actioner,true);

            if (!in_array($uid, $actioner)) {
                //**********如果值不存在，将该值赋给哈希的键***********
                $actioner[] = $uid;
                $actioner = json_encode($actioner);
                Redis::hset($requestType,$paperId,$actioner);
            }

        }

    }

    public function detail(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'origin:非法访问'];
        }

        $requireType = $request->requireType;
        $postedId = $request->id;

        //***************判断是否是来自微信服务器的请求，并且参数是否合法**************

        if ($requireType!='detail' || is_null($postedId)) {

            return ['res'=>'非法访问'];
        };

//        $sql = 'select posted_content,posted_is_upload_img,posted_contact from campus_posted where posted_id = ?';
        $result = Campus_posted::where(['posted_id'=>$postedId])->get(['posted_content as content',
            'posted_is_upload_img as is_upload_img','posted_contact as contact'])->toArray();
        if (count($result)==0) {
            return ['res'=>'该帖不存在'];
        }

        //*********如果用户上传了图片*********
        if ($result[0]['is_upload_img']) {
            $filesArray = array();
            //列出存储帖子图片的文件夹下的所有图片
            if ($handle = opendir('./images/campus_help/published/'.$postedId)){
                while (($filename = readdir($handle))!==false) {
                    //*********排除隐藏文件************
                    if ($filename!=='.' && $filename!=='..' && basename($filename)!=='1') {
                        $filesArray[] = $filename;
                    }
                }
                closedir($handle);
            }

            $result[0]['contentImgSrcArray'] = $filesArray;
        }

        return $result;
    }
}