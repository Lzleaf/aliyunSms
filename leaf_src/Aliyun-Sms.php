<?php
namespace Leaf_ALIYUNSMS;
require_once(__DIR__.'/../src/mns-autoloader.php');
use AliyunMNS\Client;
use AliyunMNS\Model\BatchSmsAttributes;
use AliyunMNS\Model\MessageAttributes;
use AliyunMNS\Exception\MnsException;
use AliyunMNS\Requests\PublishMessageRequest;
class AliyunSMS
{
    protected $config;
    protected $tel_code;
    public function __construct($tel_code)
    {
        $this->config = require_once(__DIR__.'/Config.php');
        $this->tel_code = $tel_code;
    }

    public function send($mobile,$code)
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
        $batchSmsAttributes = new BatchSmsAttributes($this->config['sign_name'], $this->tel_code);
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
}
?>