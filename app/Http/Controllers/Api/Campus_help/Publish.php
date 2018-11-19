<?php
namespace App\Http\Controllers\Api\Campus_help;

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
    public function post(Request $request){
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

        $uid = Redis::hget('uid',$uidIndex);
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
            if ($isChoosedImg === 1) {
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
    public function postImagesUpload(Request $request){
        $referer = checkReferer();
        if(!$referer){
            return ['error'=>'origin:非法访问'];
        }

        $sendType = $request->sendType;
        if (!$sendType){
            return ['res'=>'非法访问！'];
        }
        $num = $request->num;
        if (!$num){
            return ['res'=>'非法访问！'];
        }
        $postedId = $request->postedId;
        if (!$postedId){
            return ['res'=>'发帖失败'];
        }
        //定义存放上传过来的文件的路径,要真是存在的
        $file_dir = './images/campus_help/published/';

        $wantCreatedDir = $file_dir.base64_decode($postedId);
        if (!file_exists($wantCreatedDir)) {
            mkdir($wantCreatedDir,0777);
            chmod($wantCreatedDir, 0777);
        }

        //传过来的文件的name属性
        $ipt_name = 'postImg';

        //取所有上传的文件
        $files = $_FILES[$ipt_name];

        ob_clean();
        //创建以用户ID命名的、以识别图片是哪个用户发表的目录

        // $uploadFile = $file_dir .$files['name'][$i];
        // $uploadFile = $createdDir.'/'.$files['name'];
        $size = $files['size'];

        //限制上传的图片大小为2M
        if ($size >2*1024*1024) {
            return ['res'=>'图片大小超过2M'];
        }

        $filename = basename($files['name']);
        //对文件名进行GB2312编码，原来是UTF-8，防止中文文件名乱码
        $pos = strrpos($filename, '.');

        //*******取文件后缀,带.号**********
        $suffix = substr($filename,$pos);

        $enc_name = iconv('UTF-8', 'GB2312',$wantCreatedDir.'/'.$_POST['num'].$suffix);

        //将上传的文件移动到指定文件夹
        $res = move_uploaded_file($files['tmp_name'], $enc_name);
        $arr = array();
        $arr['msg'] = $res ? '上传成功' : '上传失败';
        return $arr;
    }
}