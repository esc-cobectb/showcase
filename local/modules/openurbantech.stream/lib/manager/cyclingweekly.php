<?php
namespace Openurbantech\Stream\Manager;

use Bitrix\Main,
	Bitrix\Main\Web\HttpClient,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


class Cyclingweekly extends \Openurbantech\Stream\Manager
{

	function __construct($sourceObject, $elementManager = null, $streamManager = null) 
	{
    	parent::__construct($sourceObject, $elementManager = null, $streamManager = null);
   	}

	// public function request()
 //    {
 //        $client = new HttpClient();
 //        $url = $this->getSourceUrl();
 //        return $client->get($url);
 //    }

	public function processContent()
	{
		$this->content = $this->request();
	}

	public function processItems($content)
	{
		$items = [];

		$doc = new \DOMDocument();
		$doc->loadHTML($content);

		// var_dump($doc);

		$xpath = new \DOMXpath($doc);

		$elements = $xpath->query("//a[@class='listing__link']");
		foreach ($elements as $index => $node) {
			$url = $node->getAttribute("href");
			if(!empty($url)){
				$items[$index]['url'] = $url;
			}
		}

		$elements = $xpath->query("//a[@class='listing__link']//h2");
		foreach ($elements as $index => $node) {
			$title = trim($node->nodeValue);
			if(!empty($title) && !empty($items[$index])){
				$items[$index]['title'] = $title;
			}
		}

		$elements = $xpath->query("//a[@class='listing__link']//time");
		foreach ($elements as $index => $node) {
			$date = $node->getAttribute('datetime');
			if(!empty($date) && !empty($items[$index])){
				$datetime = new \DateTime($date);
				$items[$index]['date'] = $datetime->format('d.m.Y H:i:s');
			}
		}
		$this->setItems($items);
	}
}