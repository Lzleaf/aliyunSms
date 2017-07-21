<?php
/**
 * Created by PhpStorm.
 * User: leaf
 * Date: 2017/7/21
 * Time: 下午2:56
 */

namespace Leaf_AliyunObject;
require_once(__DIR__.'/../src/mns-autoloader.php');
use AliyunMNS\Client;
use AliyunMNS\Model\BatchSmsAttributes;
use AliyunMNS\Model\MessageAttributes;
use AliyunMNS\Exception\MnsException;
use AliyunMNS\Requests\PublishMessageRequest;
use OSS\OssClient;
use OSS\Core\OssException;

class AliyunObject
{
    protected $config;
    public function __construct()
    {
        $this->config = require_once(__DIR__.'/Config.php');
    }

    public function send($mobile,$code,$type)
    {

        /**
         * Step 1. 初始化Client
         */
        $client = new Client($this->config['end_point'], $this->config['access_id'], $this->config['access_secret']);
        /**
         * Step 2. 获取主题引用
         */
        $topic = $client->getTopicRef($this->config['topic_name']);
        /**
         * Step 3. 生成SMS消息属性
         */
        // 3.1 设置发送短信的签名（SMSSignName）和模板（SMSTemplateCode）
        $batchSmsAttributes = new BatchSmsAttributes($this->config['sign_name'], $this->config['sms_type'][$type]);
        // 3.2 （如果在短信模板中定义了参数）指定短信模板中对应参数的值
        $batchSmsAttributes->addReceiver($mobile, array("code" => $code));
        $messageAttributes = new MessageAttributes(array($batchSmsAttributes));
        /**
         * Step 4. 设置SMS消息体（必须）
         *
         * 注：目前暂时不支持消息内容为空，需要指定消息内容，不为空即可。
         */
        $messageBody = "sms_message";
        /**
         * Step 5. 发布SMS消息
         */
        $request = new PublishMessageRequest($messageBody, $messageAttributes);
        try
        {
            $res = $topic->publishMessage($request);
            return $res->isSucceed();
        }
        catch (MnsException $e)
        {
            return false;
        }
    }

    public function init()
    {
        $ossClient = new OssClient($this->config['access_id'], $this->config['access_secret'], $this->config['end_point']);
        return $ossClient;
    }

    public function uploadFile($file,$file_name,$bucket){
        $ossClient = $this->init();
        try{
            $file = base64_decode($file);
            $file_name = $file_name.'.jpg';
            $ossClient->putObject($bucket,$file_name,$file);
            return array(100,$file_name);
        }catch (OssException $e){
            return array(101,$e->getMessage());
        }
    }
    public function uploadFiles($files,$file_name_root, $bucket)
    {
        $ossClient = $this->init();
        $str = '';
        foreach ($files as $key => $file) {
            try {
                $file = base64_decode($file);
                $file_name = $file_name_root.'_'.$key.'.jpg';
                $ossClient->putObject($bucket,$file_name,$file);
                if($key == 0){
                    $str .= $file_name;
                }else{
                    $str .= '*/*'.$file_name;
                }
//                array_push($file_paths,$file_name);
            } catch (OssException $e) {
                return array(101,$e->getMessage());
            }
        }
        if(empty($str)){
            return array(102,'上传图片不能为空');
        }
        return array(100,$str);
    }

    public function deleteFile($file_name,$bucket){
        $ossClient = $this->init();
        try{
            $ossClient->deleteObject($bucket,$file_name);
            return array(100,'success');
        }catch (OssException $e){
            return array(101,$e->getMessage());
        }
    }

    public function deleteFiles($file_names,$bucket){
        $ossClient = $this->init();
        try{
            $ossClient->deleteObjects($bucket,$file_names);
            return array(100,'success');
        }catch (OssException $e){
            return array(101,$e->getMessage());
        }
    }

}