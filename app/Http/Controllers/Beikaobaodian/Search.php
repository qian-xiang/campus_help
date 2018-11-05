<?php

namespace App\Http\Controllers\Beikaobaodian;
//这是一个测试注释
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Source;
use Mail;
class Search extends Controller
{
    public function search_source(Request $request){
        $keyword = $request->input('keyword');
        if (is_null($keyword)||strlen(trim($keyword))==0) {
            return ['status'=>'failed','msg'=>'输入内容为空！请重新输入！'];
        }
        return view('offer-source');
    }

    public function accept_source(Request $request){
        // 表单验证

        $source = new Source;

        $source->source_offer_email = $request->email;
        $source->source_name = $request->source_name;
        $source->source_href = $request->source_href;
        $source->source_time = time();

        $res = $source->save();
        dd($res);
        return '插入流程完成';

    }
    public function send_email(Request $request){
        
        $data = [  
            'email'=>'1172287756@qq.com', //接收邮件邮箱  
            'name'=>'1172287756',   
            'uid'=>1,                       //这两个参数可又可无,不用修改即可  
            'activationcode'=>'213131'  
            ];  
            Mail::send('sendemail', $data, function($message) use($data)   //activeemail是执行代码的表单页面  
            {  
                $message->to($data['email'], $data['name'])->subject('这是一封测试邮件');  
            });  
        
        return '发送邮件流程完成';

    }
    
}
