<?php
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Campus_posted;
//use App\Campus_user;

class Publish extends Controller
{
    /**
     * Publish constructor.
     * @param Request $request
     */
    public function publish(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'origin:非法访问'];
        }
        if ($request->method()!=='POST'){
            return ['error'=>'method:非法访问'];
        }

        $form = $request->toArray();
        $arr = array();

        foreach ($form as $key => $value) {
            //**********判断传来的数据是否为空*******
            if ($key!='isChoosedImg') {
                if (is_null($value) || strlen(trim($value)) == 0) {
                    return ['error'=>'请填写信息完整'];
                }
            }
        }

        $uidIndex = $form['uniqueId'];
        if (!$uidIndex){
            return ['error'=>'非法访问:uniqueId'];
        }

        //*********通过索引获取用户的唯一ID********************

        $uid = Redis::hget('uid',$uniqueId);
        if (!$uid){
            return ['error'=>'用户ID索引不存在'];
        }

        if ($form['isChoosedImg'] == 'true') {
            $isChoosedImg = 1;
        }else{
            $isChoosedImg = 0;
        }

        //这里是处理未收到图片路径的处理
        $postTime = time();

        $campus_posted = new Campus_posted();
        $campus_posted->posted_unique_id = $uid;
        $campus_posted->posted_title = $form['title'];
        $campus_posted->posted_content = $form['content'];
        $campus_posted->posted_school = $form['school'];
        $campus_posted->posted_is_upload_img = $isChoosedImg;
        $campus_posted->posted_sort = $form['sort'];
        $campus_posted->posted_contact = $form['contactsType'].'：'.$form['contacts'];
        $campus_posted->posted_reward = $form['reward'];
        $campus_posted->posted_reward = $isChoosedImg;
        $campus_posted->posted_status = 0;
        $campus_posted->posted_time = $postTime;

        $result = $campus_posted->save();
//        $sql = "insert into campus_posted (posted_id,posted_unique_id,posted_title,posted_content,posted_school,posted_is_upload_img,posted_sort,posted_reward,posted_contact,posted_status,posted_time) values(0,?,?,?,?,?,?,?,?,0,$postTime)";

        if ($result) {
            //*******判断图片路径是否存在
            if ($isChoosedImg == 1) {
                $arr['msg'] = '帖子信息发表完毕，接下来准备上传图片...';
                //将刚刚插入记录的ID返回并使用base64加密，加强信息安全
                $arr['returnId'] = base64_encode($campus_posted->posted_id);
            }else{
                $arr['msg'] = '发帖成功';
            }

        }else{
            $arr['error'] = 'fail：insert';
        }

        return $arr;

    }
}