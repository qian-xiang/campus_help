<?php

namespace App\Http\Controllers\Api\Campus_help;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class Help extends Controller
{
    public function getHelpData(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'Referer:非法访问'];
        }

        $paperId = $request->paperId;
        if (is_null($paperId) || strlen(trim($paperId))==0) {
            return ['error'=>'非法访问：paperId->2'];
        }
        $ownerUidIndex = $request->ownerUidIndex;
        if (!$ownerUidIndex){
            return ['error'=>'非法访问：ownerUidIndex'];
        }

        //*********通过索引获取用户的唯一ID********************

        $ownerUid = Redis::hget('uid',$ownerUidIndex);
        if (!$ownerUid){
            return ['error'=>'用户ID索引不存在'];
        }

        $result = Redis::hget('help',$ownerUid);

        if (!$result) {
            $count = 0;
        }else{
            $result = json_decode($result,true);
            $count = array_key_exists($paperId,$result) ? count($result[$paperId]) : 0 ;
        }

        return ['count'=>$count];
    }
    public function handleHelp(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'Referer:非法访问'];
        }

        $paperId = $request->paperId;
        if (is_null($paperId) || strlen(trim($paperId))==0) {
            return ['error'=>'非法访问：paperId->2'];
        }
        $ownerUidIndex = $request->ownerUidIndex;
        if (!$ownerUidIndex){
            return ['error'=>'非法访问：ownerUidIndex'];
        }
        $uidIndex = $request->uidIndex;
        if (!$uidIndex){
            return ['error'=>'非法访问：uidIndex'];
        }

        //*********通过索引获取用户的唯一ID********************

        $ownerUid = Redis::hget('uid',$ownerUidIndex);
        if (!$ownerUid){
            return ['error'=>'发帖人ID索引不存在'];
        }
        $uid = Redis::hget('uid',$uidIndex);
        if (!$uid){
            return ['error'=>'用户ID索引不存在'];
        }

        $result = Redis::hget('help',$ownerUid);
        $wantAddedArray = array();

        if (!$result) {
            $wantAddedArray[] = $uid;
            //paperId传递过来时已是字符类型
            Redis::hset('help',$ownerUid,json_encode([$paperId=>$wantAddedArray]));
            $count = 1;
        }else{
            $result = json_decode($result,true);
            // array_key_exists($paperId,$result) ? $result[$paperId] : $uid;
            //如果帖子ID键不存在
            if (!array_key_exists($paperId,$result)) {
                //ownerUid->paperId->uid索引数组
                $wantAddedArray[] = $uid;
                $result[$paperId] = $wantAddedArray;
                //将数据处理后，重新存入Redis
                Redis::hset('help',$ownerUid,json_encode($result));
                $count = 1;
            }else{
                //如果帖子ID键存在
                $uidArray = $result[$paperId];
                if (!in_array($uid, $uidArray)) {
                    $uidArray[] = $uid;
                    $result[$paperId] = $uidArray;
                    Redis::hset('help',$ownerUid,json_encode($result));

                    $count = count($uidArray)+1;
                }else{
                    return ['error'=>'您之前已助力过此贴了，请勿重复助力。'];
                }
            }

        }
        return ['count'=>$count,'success'=>'恭喜您助力成功，您是该帖的第'.$count.'位助力者！'];
    }
}
