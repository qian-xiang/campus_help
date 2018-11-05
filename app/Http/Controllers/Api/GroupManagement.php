<?php
namespace App\Http\Controllers\Api;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
//用户表
use App\Experience_qianxiang_group_management;
use App\Formal_qianxiang_group_management;

class GroupManagement extends Controller
{
    public function checkExperienceUser(Request $request){
        $qq = $request->qq;     
        // // $res = Qianxiang_group_management::where('qq','847882429')->get();  二维数组
        // //$res = Qianxiang_group_management::where('qq','3397099208')->first(); 一维数组
        $res = Experience_qianxiang_group_management::where(['qq'=>$qq])->select('start_time','duration')->first();
        if($res===null){
            return json_encode([
                'status'=>'fail',
                'msg'=>'您尚未体验浅香群管，是否现在就进行体验？'
            ]);
        }
        $res = json_decode($res,true);
        $experienceDuration = $res['duration'];
        //过期时间戳
        $expireTimestamp = strtotime("+$experienceDuration day",$res['start_time']);
        $slaveTime = floor(($expireTimestamp-time())/(60*60));
        if(time()>$expireTimestamp){
            return json_encode([
                'status'=>'fail',
                'msg'=>'抱歉，您的体验时间已到期，请联系作者QQ847882429购买正式会员以继续进行使用。'
            ]);
        }
        // $currentTime-start_time>duration*24*60*60
        //体验未到期或者尚未进行体验，开始进行判断
        //判断某个记录是否存在  返回true或者false
        return json_encode([
            'status'=>'success',
            'msg'=>'欢迎回来，您当前的体验时间还剩：'.$slaveTime.'小时。'
        ]);
        
    }

    //检验正式用户时长是否过期
    public function checkFormalUser(Request $request){
        $qq = $request->qq;        
        // // $res = Qianxiang_group_management::where('qq','847882429')->get();  二维数组
        // //$res = Qianxiang_group_management::where('qq','3397099208')->first(); 一维数组
        $res = Formal_qianxiang_group_management::where(['qq'=>$qq])->select('start_time','duration')->first();
        if($res===null){
            return json_encode([
                'status'=>'fail',
                'msg'=>'经检测，您不是浅香群管的会员，请先联系插件作者：QQ847882429获取授权。资费：10元/月'
            ]);
        }
        $res = json_decode($res,true);
        //购买时长 月
        $buyDuration = $res['duration'];
        //过期时间戳
        $expireTimestamp = strtotime("+$buyDuration month",$res['start_time']);
        if(time()>$expireTimestamp){
            return json_encode([
                'status'=>'fail',
                'msg'=>'抱歉，您的会员时长已到期，请联系插件作者：QQ847882429进行续费。'
            ]);
        }
        //剩余时长：天
        $laveTime = floor(($expireTimestamp - time())/(60*60*24));
        // 将过期时间通过AES加密返回给浅香群管客户端
        $key = 'qianxiangqunguan';
        $method = 'AES-128-CBC';
        $iv = $key;
        $encryptedData = openssl_encrypt($expireTimestamp,$method,$key,0,$iv);
        return json_encode([
            'status'=>'success',
            'msg'=>'会员时长可用,您当前剩余时长为：'.$laveTime.'天',
            'data'=>$encryptedData
        ]);
    }
    public function addExperience(Request $request){
        //经AES-128-ECB以及base64加密过
        $encryptedTimestamp = $request->param;
        $decryptedTimestamp = openssl_decrypt($encryptedTimestamp,'AES-128-ECB','qianxiangqunguan',0);
        if(time()==$decryptedTimestamp){
            //体验时长
            $experienceTime = 3;
            $experience_qianxiang_group_management = new Experience_qianxiang_group_management;
            $experience_qianxiang_group_management->qq = $request->qq;
            $experience_qianxiang_group_management->duration = $experienceTime;
            $experience_qianxiang_group_management->start_time = time();

            $res = $experience_qianxiang_group_management->save();
            $res = $res ? '添加体验权限成功！您获得'.$experienceTime.'天的体验机会。':'获取体验机会失败，请稍后重试。若多次遭遇失败，请联系作者。';
            return $res;
        }
        return '非法访问！';
    }


}