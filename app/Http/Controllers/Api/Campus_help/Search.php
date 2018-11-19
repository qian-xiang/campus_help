<?php
namespace App\Http\Controllers\Api\Campus_help;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
//use App\Campus_user;
//use App\Campus_posted;
use Illuminate\Support\Facades\DB;

class Search extends Controller{
    /**
     * 说明：该文件用于在搜索框输入关键词的时候显示搜索的候选词
     */
    public function getKeyWords(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'origin:非法访问'];
        }
        $error='';
        $actionType = $request->actionType;
        if (!$actionType){
            return ['error'=>'非法访问:actionType'];
        }
        $school = $request->school;
        if (!$school){
            return ['error'=>'非法访问:school'];
        }
        $uidIndex = $request->uidIndex;
        if (!$uidIndex){
            return ['error'=>'非法访问:uidIndex'];
        }
        $uid = Redis::hget('uid',$uidIndex);
        if (!$uid) {
            return ['error'=>'用户ID索引不存在'];
        }
        $keyword = $request->keyword;
        if (is_null($keyword)|| strlen(trim($keyword))<=0) {
            return ['error'=>'请输入关键词'];
        }

        switch ($actionType) {
            case 'getKeyWord':
                //设定加载一次显示5个
                $pageContainer = 5;
                // $sql = "select posted_id,user_nickname,user_head_image,posted_title,posted_is_upload_img,posted_sort,posted_status,posted_time from campus_posted INNER JOIN campus_user ON user_unique_id = posted_unique_id where posted_title like ? and posted_school=? limit 0,$pageContainer";
//                $sql = "select posted_id,posted_unique_id,user_nickname,user_head_image,
////posted_title,posted_is_upload_img,posted_sort,posted_reward,posted_status,posted_time
//// from campus_posted inner join campus_user on user_unique_id = posted_unique_id
// where posted_title like ? and posted_school=? limit 0,$pageContainer";
                $result = DB::table('campus_posted')->join('campus_user','campus_user.user_unique_id','=','campus_posted.posted_unique_id')
                    ->where('posted_title','like','%'.$keyword.'%')
                    ->where(['posted_school'=>$school])
                    ->orderBy('campus_posted.posted_status','ASC')
                    ->orderBy('campus_posted.posted_time','DESC')->take($pageContainer)
                    ->get(['posted_id as id','posted_unique_id as unique_id',
                        'user_nickname as nickname','user_head_image as head_image','posted_title as title','posted_is_upload_img as is_upload_img'
                        ,'posted_sort as sort', 'posted_reward as reward','posted_status as status','posted_time as time'])
                    ->toArray();
                break;
            case 'getAllKeyWords':
                $pageContainer = 5;
//                $sql = "select posted_id,posted_unique_id,user_nickname,user_head_image,posted_title,posted_is_upload_img,posted_sort,posted_reward,posted_status,posted_time from campus_posted inner join campus_user on user_unique_id = posted_unique_id where posted_title like ? and posted_school=?";
                $result = DB::table('campus_posted')->join('campus_user','campus_user.user_unique_id','=','campus_posted.posted_unique_id')
                    ->where('posted_title','like','%'.$keyword.'%')
                    ->where(['posted_school'=>$school])
                    ->orderBy('campus_posted.posted_status','ASC')
                    ->orderBy('campus_posted.posted_time','DESC')
                    ->select('posted_id as id','posted_unique_id as unique_id',
                        'user_nickname as nickname','user_head_image as head_image','posted_title as title','posted_is_upload_img as is_upload_img'
                        ,'posted_sort as sort', 'posted_reward as reward','posted_status as status','posted_time as time')
                    ->paginate($pageContainer)
                    ->toArray();
                $next_page_url = $result['next_page_url'];
                $result = $result['data'];
                break;

            default:
                return ['error'=>'非法访问：actionType'];
                break;
        }

        if (count($result) === 0){
            return ['error'=>'没有该关键词的记录'];
        }

        for ($i=0; $len = count($result), $i < $len; $i++) {
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

                            if ($realValue == 1) {
                                $filesArray = array();
                                //列出存储帖子图片的文件夹下的所有图片

                                if ($handle = opendir('./images/published/'.$result[$i]['id'])){
                                    while (($filename = readdir($handle))!==false) {
                                        //*********排除隐藏文件************
                                        if ($filename!=='.' && $filename!=='..') {
                                            $filesArray[] = $filename;
                                        }
                                    }
                                    closedir($handle);
                                }

                                $realValue = $filesArray;

                            }else{

                                $realValue=[];
                            }


                            break;

                        case 'id':

                            //获取该id在Redis中的记录数,该ID在哈希中的值是数组形式 并判断用户ID是否在该帖的【喜欢】中
                            $arr = Redis::hget('favorite', $realValue);
                            $arr = json_decode($arr,true);
                            $isFavorite = $arr == false ? false : in_array($uid, $arr) ? true : false ;
                            $favoriteCount = $arr == false ? 0 : count($arr);

                            $arr = Redis::hget('topRecord', $realValue);
                            $arr = json_decode($arr,true);
                            //判断用户ID是否在该帖的【顶】中
                            $isTopRecord = $arr == false ? false : in_array($uid, $arr) ? true : false ;
                            $topRecordCount = $arr == false ? 0 : count($arr);

                            //*******获取帖子的浏览量**********
                            $arr = Redis::hget('browseCount',$realValue);
                            $arr = json_decode($arr,true);
                            $browseCount = $arr == false ? 0 : count($arr);

                            //*******获取帖子的助力数**********
                            $arr = Redis::hget('help',$uid);
                            if ($arr==false) {
                                $helpCount = 0;

                            }else{
                                $arr = json_decode($arr,true);
                                $helpCount = array_key_exists($realValue,$arr) ? count($arr[$realValue]) : 0;
                            }

                            //*********将记录数添加到数组中************
                            $result[$i]->favoriteCount = $favoriteCount;
                            $result[$i]->isFavorite = $isFavorite;
                            $result[$i]->topRecordCount = $topRecordCount;
                            $result[$i]->isTopRecord = $isTopRecord;
                            $result[$i]->browseCount = $browseCount;
                            $result[$i]->helpCount = $helpCount;

                            break;

                        default:
                            # code...
                            break;
                    }
                if ($key !='unique_id') {

                    $result[$i]->$key = $realValue;

                }
                //**********状态排序数组**************
                $statusSortArray[$i] = $result[$i]->status;
                //**********发帖时间排序数组**************
                $timeSortArray[$i] = $result[$i]->time;
                //**********求助量排序数组**************
                $helpCountSortArray[$i] = $result[$i]->helpCount;
            }

        }
        array_multisort($statusSortArray,SORT_ASC,SORT_NUMERIC,$timeSortArray,SORT_ASC,SORT_NUMERIC,$helpCountSortArray,SORT_DESC,SORT_NUMERIC,$result);
        $response = $next_page_url == null ? ['recordArray'=>$result,'error'=>$error, 'next_page_url'=>$next_page_url] : ['recordArray'=>$result,'error'=>$error];
        return $response;
    }
}