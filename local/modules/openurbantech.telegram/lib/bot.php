<?php

namespace Openurbantech\Telegram;

class Bot 
{

    protected $token = '';
    protected $name = '';
    protected $username = '';
    protected $apiURL = 'https://api.telegram.org/bot';
    protected $chatID = '';
    protected $parsemode = 'Markdown';
    protected $debug = false;

    static $useProxy = false;
    static $proxyConfig = [
        CURLOPT_PROXY => '',
        CURLOPT_PROXYPORT => '',
        CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5,
        CURLOPT_PROXYUSERPWD => '',
    ];

    function __construct()    {
       $this->setToken(\Bitrix\Main\Config\Option::get('openurbantech.telegram', 'telegram_bot_token'));
       $this->setName(\Bitrix\Main\Config\Option::get('openurbantech.telegram', 'telegram_bot_name'));
    }

    public function getToken(){
        return $this->token;
    }

    public function setToken($token = ''){
        $this->token = $token;
    }

    public function getDebug(){
        return $this->debug;
    }

    public function setDebug($debug = false){
        $this->debug = $debug;
    }

    public function getName(){
        return $this->name;
    }

    public function setName($name = ''){
        $this->name = $name;
    }

    public function getUsername(){
        return $this->username;
    }

    public function setUsername($username = ''){
        $this->username = $username;
    }

    public function getApiURL(){
        return $this->apiURL;
    }

    public function setApiURL($apiURL = ''){
        $this->apiURL = $apiURL;
    }

    public function getChatID(){
        return $this->chatID;
    }

    public function setChatID($chatID = ''){
        $this->chatID = $chatID;
    }

    public function getParsemode(){
        return $this->parsemode;
    }

    public function setParsemode($parsemode = ''){
        $this->parsemode = $parsemode;
    }

    public function getRequestURL($method = ''){
        return sprintf('%s%s/%s',$this->getApiURL(), $this->getToken(), $method);
    }

    public function prepareText($text){
        return $text;
    }

    public function sendMessage($text = '', $parameters = []){
        $result = '';
        if(!empty($text)){

            $url = $this->getRequestURL('sendMessage');
            $parameters['chat_id'] = $this->getChatID();
            $parameters['parse_mode'] = $this->getParsemode();
            $parameters['text'] = $this->prepareText($text);
            $result = $this->executeRequest($url, $parameters);
        }
        return $result;
    }

    public function sendDocument($documentUrl = '', $caption = '', $parameters = []){
        $result = '';
        if(!empty($documentUrl))
        {

            $url = $this->getRequestURL('sendDocument');
            $parameters['document'] = $documentUrl;
            $parameters['chat_id'] = $this->getChatID();
            $parameters['parse_mode'] = $this->getParsemode();
            if(!empty($documentUrl))
            {
                $parameters['caption'] = $this->prepareText($caption);
            }
            $result = $this->executeRequest($url, $parameters);
        }
        return $result;
    }

    public function sendPhoto($photoUrl = '', $caption = '', $parameters = []){
        $result = '';
        if(!empty($photoUrl))
        {

            $url = $this->getRequestURL('sendPhoto');
            $parameters['photo'] = $photoUrl;
            $parameters['chat_id'] = $this->getChatID();
            $parameters['parse_mode'] = $this->getParsemode();
            if(!empty($photoUrl))
            {
                $parameters['caption'] = $this->prepareText($caption);
            }
            $result = $this->executeRequest($url, $parameters);
        }
        return $result;
    }

    private function executeRequest($url, $parameters = array()){
        $curl_options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CUSTOMREQUEST  => $http_method,
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        );
        $headers = array(
            'Pragma: no-cache',
        );

        $curl_options[CURLOPT_HTTPHEADER] = $headers;
        if (is_array($parameters)) {
            $url .= '?' . http_build_query($parameters, null, '&');
        } elseif ($parameters) {
            $url .= '?' . $parameters;
        }

        $curl_options[CURLOPT_URL] = $url;
        if($this->getDebug())
        {
            return $url;
        }

        if(static::$useProxy)
        {
            foreach (static::$proxyConfig as $key => $value)
            {
                $curl_options[$key] = $value;
            }
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);

        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        if ($curl_error = curl_error($ch)) {
            $response = '';
        } else {
            try {
                $response = \Bitrix\Main\Web\Json::decode($result);
            } catch (\Exception $e) {
                $response['ERROR'] = $e->getMessage();
            }
        }
        curl_close($ch);

        return $response;
    }

}