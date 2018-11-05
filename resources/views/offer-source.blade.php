<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>备考宝典-投稿</title>
    <link rel="stylesheet" href="{{URL::asset('css/bootstrap.min.css')}}">

</head>
<body>
    <div class="container-fluid" style="height: 100vh;display:flex;justify-content: center;align-items: center;">
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6" style="height:80vh;margin-left:auto;margin-right: auto;display: flex;flex-direction:column;align-items: center;justify-content: space-around;">
            <img src="{{URL::asset('images/logo.jpg')}}" style="max-height:100px;max-width:100px;">
            <h3>备考宝典</h3>           
            <form action="{{url('/beikaobaodian/search/accept_source')}}" method="POST" style="height: 60%;width: 60%;display: flex;flex-direction: column;justify-content: space-around;align-items: center">
                {{ csrf_field() }}
                <div class="form-inline">
                    <div class="form-group">
                      <label for="email">QQ&nbsp;&nbsp;邮箱：</label>
                      <input type="text" class="form-control" name="email" id="email" placeholder="请输入QQ邮箱">
                    </div>
                </div>
                
                <div class="form-inline">
                    <div class="form-group">
                        <label for="source_name">资源名称：</label>
                        <input type="text" class="form-control" name="source_name" id="source_name" placeholder="请输入资源名称">
                    </div>
                </div>

                <div class="form-inline">
                    <div class="form-group">
                        <label for="source_href">资源地址：</label>
                        <input type="text" class="form-control" name="source_href" id="source_href" placeholder="请输入资源地址/链接">
                    </div>
                </div>
                <button type="submit" class="btn btn-default">下一步</button>
            </form>
        </div>
    </div>
</body>
</html>