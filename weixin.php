<?php
header('Content-type:text');
define("TOKEN", "weixin");//自己定义的token 就是个通信的私钥
$wechatObj = new wechatCallbackapiTest();
//$wechatObj->valid();
$wechatObj->responseMsg();
class wechatCallbackapiTest
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }
    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            //这个$event就是事件具体内容
            $event = $postObj->Event;

            $time = time();
            $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <FuncFlag>0<FuncFlag>
            </xml>";

            //当用户关注我们微信平台时，会发送一个订阅的事件，我们就处理这个事件
            switch ($postObj->MsgType) {
                case 'event':
                    //如果是用户订阅事件
                    if($event == "subscribe"){
                        $contentStr = "欢迎订阅但愿人长久66：\r\n\r\n * 菜单如下 \r\n\r\n 1. 输入'新闻',返回新闻条目 \r\n 2.输入'听歌',返回歌曲列表 \r\n 3.发送地理位置，可查询最近的地点 ";
                        //这里我们先返回菜单，填充模板即可
                        $msgType = 'text';
                        $textTpl = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                        echo $textTpl;
                    }
                    break;
                case 'text':
                    if($keyword == "新闻"){
                        //返回新闻列表
                        $newsTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[news]]></MsgType>
                        <ArticleCount>2</ArticleCount>
                        <Articles>
                        <item>
                        <Title><![CDATA[新闻1]]></Title> 
                        <Description><![CDATA[新闻描述1]]></Description>
                        <PicUrl><![CDATA[%s]]></PicUrl>
                        <Url><![CDATA[%s]]></Url>
                        </item>
                        <item>
                        <Title><![CDATA[新闻2]]></Title> 
                        <Description><![CDATA[新闻描述2]]></Description>
                        <PicUrl><![CDATA[%s]]></PicUrl>
                        <Url><![CDATA[%s]]></Url>
                        </item>
                        </Articles>
                        </xml>";

                        $picUrl1 = "http://fengjixuchui.08.wm4p.com/upload/1.jpg";
                        $picUrl2 = "http://fengjixuchui.08.wm4p.com/upload/2.jpg";

                        $Url1 = "http://news.baidu.com";
                        $Url2 = "http://news.qq.com";
                        //开始拼接返回结果
                        $resultStr = sprintf($newsTpl, $fromUsername, $toUsername, $time, $picUrl1, $Url1, $picUrl2, $Url2);
                        echo $resultStr;

                    }else if($keyword == "听歌"){
                        //我们返回这个点播菜单
                        $contentStr = " 欢迎来到点歌系统, 输入编号可点歌 \r\n\r\n 歌曲列表 \r\n\r\n 1.汪峰-北京北京 \r\n 2.汪峰-存在 \r\n 3.汪峰-在雨中 ";
                        //返回
                        $msgType = "text";
                        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                        echo $resultStr;
                    }else if(preg_match("/^[1-9](\d){0,2}$/", $keyword)) {

                        if($keyword == '1'){
                            $desc = "汪峰-北京北京";
                        }else if($keyword == '2'){
                            $desc = "汪峰-存在";
                        }else if($keyword == '3') {
                            $desc = "汪峰-在雨中";
                        }else{
                            $desc = "汪峰-北京北京";
                            $keyword = 1;
                        }
                        //关键的地方来了，如何返回音乐
                        $musicTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[music]]></MsgType>
                        <Music>
                        <Title><![CDATA[汪峰的音乐集合]]></Title>
                        <Description><![CDATA[%s]]></Description>
                        <MusicUrl><![CDATA[%s]]></MusicUrl>
                        <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
                        </Music>
                        </xml>";

                        //得到播放音乐的地址
                        $musicUrl = "http://qiwu.website/Public/Music/{$keyword}.mp3";
                        //填充返回的结果
                        $resultStr = sprintf($musicTpl, $fromUsername, $toUsername, $time, $desc, $musicUrl, $musicUrl);

                        echo $resultStr;

                    }
                    //我们规定，如果用户想查询某个关心的地方，要求用户必须这样输入 cxwz地方名称->正则表达式
                    else if(preg_match("/^cxwz([\x{4e00}-\x{9fa5}]+)/ui", $keyword, $res)) {
                        $address = $res[1];
                        //还要取出这个用户的地理位置
                        //从数据库中循环取出新闻条目
                        $connect = mysql_connect('45.114.10.251', 'a0928205656', '0138306b');
                        mysql_select_db('a0928205656');
                        mysql_query("SET NAMES UTF8");
                        $sql = "SELECT longitude, latitude FROM `members` WHERE wxname='{$fromUsername}' ";
                        $res = mysql_query($sql);
                        if($row = mysql_fetch_array($res)) {
                            $contentStr = "请点击该链接，就可以查询到该地点的信息: \r\n http://api.map.baidu.com/place/search?query=".urlencode($address)."&location={$row['latitude']},{$row['longitude']}&radius=1000&output=html&coord_type=gcj02";
                            $msgType = "text";
                            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                            echo $resultStr;
                        }else {
                            $contentStr = "请输入地理位置，格式为： \r\n cxwz超市";
                            //返回
                            $msgType = "text";
                            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                            echo $resultStr;
                        }

                    }

                    //如果用户输入的文字不对，给一个提示
                    else {
                        //
                        $contentStr = "您输入的格式有问题，请重新输入。";
                        //返回
                        $msgType = "text";
                        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                        echo $resultStr;
                    }
                    break;
                //如果用户上传的是地理位置，则类型是location。
                case 'location':
                    //获取到用户的经度和纬度
                    $Location_Y = $postObj->Location_Y;
                    $Location_X = $postObj->Location_X;
                    $contentStr = "您好！我们已收到您的地理位置 \r\n 经度：{$Location_Y} \r\n 纬度： {$Location_X} \r\n\r\n 请您输入您关心的地方，即可查询！格式为： \r\n 'cxwz超市'";
                    //返回
                    $msgType = "text";
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                    echo $resultStr;
                    //将得到的经度和纬度入库，
                    $connect = mysql_connect('45.114.10.251', 'a0928205656', '0138306b');
                    mysql_select_db('a0928205656');
                    mysql_query("SET NAMES UTF8");
                    $sql = "SELECT wxname FROM `members` WHERE wxname='{$fromUsername}' ";
                    $res = mysql_query($sql);
                    //1.判断如果用户已经存在，更新地理位置
                    if($row = mysql_fetch_assoc($res)) {
                        //更新
                        $sql = "UPDATE `members` SET longitude='{$Location_Y}' , latitude='{$Location_X}', join_time='{$time}' WHERE wxname='{$fromUsername}' ";
                        mysql_query($sql);
                    }else {
                        //如果用户不存在，说明第一次来，添加
                        $sql = "INSERT INTO `members` (wxname, longitude, latitude, join_time) VALUES('{$fromUsername}', '{$Location_Y}', '{$Location_X}', '{$time}') ";
                        mysql_query($sql);
                    }

                    break;
                default:
                    break;
            }
        }else {
            echo '';
            exit;
        }
    }
 
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token =TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
 
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
}
?>