<?php
namespace Openurbantech\Stream\Manager;

use Bitrix\Main,
	Bitrix\Main\Web\HttpClient,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


class Uci extends \Openurbantech\Stream\Manager
{

	function __construct($sourceObject, $elementManager = null, $streamManager = null) 
	{
    	parent::__construct($sourceObject, $elementManager = null, $streamManager = null);
   	}

	public function processContent()
	{
		$this->content = $this->request();
	}


	public function processItems($content)
	{
		$items = [];

		$json = \Bitrix\Main\Web\Json::decode($content);
		if(!empty($json)){
			foreach ($json['items'] as $item) {
				$datetime = new \DateTime($item['date']);
				$items[] = [
					'url' => 'https://www.uci.org'.$item['url'],
					'title' => $item['title'],
					'date' => $datetime->format('d.m.Y H:i:s'),
				];
			}
		}

		$this->setItems($items);
	}
}