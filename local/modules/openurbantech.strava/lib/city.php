<?php
namespace Openurbantech\Strava;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class City {
    
	protected $cities;

    public function getManager()
    {
        return new  \Openurbantech\Strava\Model\CityTable();
    }

    public function getCitiesArray()
    {
        if(empty($this->cities))
        {
            $cities = [];
            $manager = $this->getManager();
            $obCities = $manager->getList();
            while ($city = $obCities->fetch()) 
            {
                $cities[$city['ID']] = $city;
            }
            $this->cities = $cities;
        }
        return $this->cities;
    }

    function getCityIdByPoint($point)
    {
        $cities = $this->getCitiesArray();
        foreach ($cities as $cityId => $city) 
        {
        	$bounds = explode(',', $city['UF_BOUNDS']);
            if(\Openurbantech\Strava\Point::isInsideBounds($point, $bounds))
            {
                return $cityId;
            }
        }
        return null;
    }
}