<?php
namespace Openurbantech\Strava;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Athlete extends Api
{
	public $defaultPattern = 'athlete';
	protected $activityTypes = [
		'Ride',
		'EBikeRide',
		'Handcycle',
		'Velomobile',
	];
	
	public function getAuthorized(){
		$result = false;
		$sPatternName = $this->defaultPattern;
		if(!empty($sPatternName)){
			$sURL = $this->getUrl($sPatternName);
			$sURL = $this->replaceParams($arParams,$sURL);
			$result = $this->call($sURL);
		}
		return $result;
	}

	public function prepareNameForDB($athlete)
	{
		$firstname = $this->convert($athlete->firstname);
		$lastname = $this->convert($athlete->lastname);
		$letter = $lastname[0];
		return sprintf('%s %s.', $firstname, $letter);
	}

	public function getActivities($params = [], $all = false)
	{
		$result = [];
		$params['per_page'] = !empty($params['per_page']) ? $params['per_page'] : 100; 
        $params['page'] = !empty($params['page']) ? $params['page'] : 1;
        $active = true; 
        while($active)
        {
            $active = false;
			$sURL = $this->getUrl('athlete_activities', $params);
			$response = $this->call($sURL);
			if(empty($response->errors))
            {
                if(!empty($response))
                {
                    foreach($response as $entry)
                    {
                        $entry->name = $this->convert($entry->name);
                        $result[] = $entry;
                    }
                    if($all)
                    {
                        $active = (count($response) == $params['per_page']);
                        $params['page']++;
                    }
                }
            }
            else 
            {
                $this->lastError = $response->errors;
            }
		}
		return $result;
	}

	public function getRides($params = [], $all = false)
	{
		$result = [];
		$activitiesArray = $this->getActivities($params, $all);
		foreach ($activitiesArray as $activity) 
		{
			if(in_array($activity->type, $this->activityTypes))
			{
				$result[] = $activity;
			}
		}
		return $result;
	}

	public function sync($ownerID){
		$result = null;
		if($ownerID > 0){
			$userAuthData = \Openurbantech\Strava\Auth::getAuthByOwnerID($ownerID);
			$userAuthData = \Openurbantech\Strava\Auth::actualize($userAuthData);
			$authManager = new \Openurbantech\Strava\Auth();
			$oauthEntity = $authManager->getEntityOAuth();
			if(!empty($userAuthData['OATOKEN'])) {
				$this->setClientToken($userAuthData['OATOKEN']);
				$rides = $this->getRides([]);
				$result = $rides;
				if(\Bitrix\Main\Loader::includeModule('openurbantech.server')){
					$queueTable = new \Openurbantech\Server\Model\QueueTable();
					foreach ($rides as $ride) {
						$queueTable = new \Openurbantech\Server\Model\QueueTable();
						$queueData = [
							'APP_NAME' => 'Strava',
							'APP_OBJECT_ID' => $ride->id,
							'APP_RIDER_ID' => $ride->athlete->id,
							'CREATED' => new \Bitrix\Main\Type\DateTime(),
							'UPDATED' => new \Bitrix\Main\Type\DateTime(),
						];
						$queueTable->add($queueData);
					}
				}
			}
		}
		return $result;
	}

	public function getQueue($ownerID, $page = 1){
		$result = null;
		$params = [];
		if($ownerID > 0){
			$userAuthData = \Openurbantech\Strava\Auth::getAuthByOwnerID($ownerID);
			$userAuthData = \Openurbantech\Strava\Auth::actualize($userAuthData);
			$authManager = new \Openurbantech\Strava\Auth();
			$oauthEntity = $authManager->getEntityOAuth();
			if(!empty($userAuthData['OATOKEN'])) {
				$this->setClientToken($userAuthData['OATOKEN']);
				
				$result = [];
				$params['per_page'] = 100; 
		        $params['page'] = $page;
		        $active = true; 
	            $active = false;
				$sURL = $this->getUrl('athlete_activities', $params);
				$response = $this->call($sURL);
				if(empty($response->errors))
	            {
	                if(!empty($response))
	                {
	                    foreach($response as $entry)
	                    {
	                        $result[] = [
	                        	'APP_OBJECT_TYPE' => $entry->type,
	                        	'APP_OBJECT_ID' => $entry->id,
								'APP_RIDER_ID' => $entry->athlete->id,
	                        ];
	                    }
	                }
	            }
	            else 
	            {
	                $this->lastError = $response->errors;
	            }
			}
		}
		return $result;
	}

}