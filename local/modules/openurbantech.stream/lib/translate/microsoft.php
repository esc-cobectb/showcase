<?php
namespace Openurbantech\Stream\Translate;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);


class Microsoft
{
	protected $apiKey = '98d78bb6b6msh9975de438498c55p12f3c0jsneeb3ae20d4cf';
	protected $apiHost = 'microsoft-translator-text.p.rapidapi.com';
	public $debug = false;

	public function translate($text, $langFrom = 'en', $langTo = 'ru'){
		$url = 'https://microsoft-translator-text.p.rapidapi.com/translate?to%5B0%5D='.$langTo.'&api-version=3.0&from='.$langFrom.'&profanityAction=NoAction&textType=plain';
		$data = [
			'Text' => $text,
		];
		$httpClient = new \Bitrix\Main\Web\HttpClient();
		$httpClient->setHeader('Content-Type', 'application/json', true);
		$httpClient->setHeader('X-RapidAPI-Key', $this->apiKey, true);
		$httpClient->setHeader('X-RapidAPI-Host', $this->apiHost, true);
		$response = $httpClient->post($url, json_encode([$data]));
		$result = NULL;
		try {
			$json = \Bitrix\Main\Web\Json::decode($response);
			if(isset($json[0]['translations'][0]['text'])){
				$result = $json[0]['translations'][0]['text'];
			} else if($this->debug){
				var_dump($response);
			}
		} catch(\Exception $e){
			if($this->debug){
				var_dump($e->getMessage());
			}
		}
		return $result;
	}


}