<?php
namespace Openurbantech\Strava;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Point
{

	public static function isInsideBounds($pointLatLng, $boundsArray)
	{
		$result = false;
		if($boundsArray[0] > $boundsArray[2]){
			$top = $boundsArray[0];
			$bottom = $boundsArray[2];
		} else {
			$top = $boundsArray[2];
			$bottom = $boundsArray[0];
		}
		if($boundsArray[1] > $boundsArray[3]){
			$ritgh = $boundsArray[1];
			$left = $boundsArray[3];
		} else {
			$ritgh = $boundsArray[3];
			$left = $boundsArray[1];
		}

		if($pointLatLng[0] >= $bottom && $pointLatLng[0] <= $top)
		{
			if($pointLatLng[1] >= $left && $pointLatLng[1] <= $ritgh)
			{
				$result = true;
			}
		}
		return $result;
	}
}