<?php

namespace Openurbantech\Stream\Telegram;

class Stream 
{
    protected $streamManager;
    protected $chatID = '-1001275306744';

    function __construct()
    {
        if(\Bitrix\Main\Loader::includeModule('openurbantech.telegram'))
        {
            $streamManager = new \Openurbantech\Telegram\Stream();
            $streamManager->setChatID($this->chatID);
            $this->streamManager = $streamManager;
        }
    }

    public function getChatID()
    {
        return $this->streamManager->getChatID();
    }

    public function setChatID($chatID)
    {
        $this->streamManager->setChatID($chatID);
    }

    public function send($message = '', $parameters = [])
    {
        $result = null;
        if(\Bitrix\Main\Loader::includeModule('openurbantech.telegram'))
        {
            $result = $this->streamManager->send($message, $parameters);
        }
        return $result;
    }
   
}