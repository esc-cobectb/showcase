<?

namespace Openurbantech\Mapmyride;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class ActivityType extends Api{

	protected $url = '/v7.2/activity_type/';

	public function getCycling(){
		return [
			'Bike Ride' => 11,
			'Road Cycling' => 36,
			'Touring Bike' => 38,
			'Mountain Biking' => 41,
			'Track Cycling' => 44,
			'Unicycling' => 47,
			'Road Cycling, Indoor' => 53,
			'CycloCross' => 60,
			'Hybrid Cycling' => 64,
			'Motorcycle / Scooter' => 223,
			'Bicycle Kicks' => 308,
			'General Track Cycling' => 523,
			'Unicycling, Long Distance' => 544,
			'General Unicycling' => 545,
			'Cyclocross Event/Race' => 549,
			'General Cyclocross' => 550,
			'Road Cycling, Light Intensity' => 629,
		];
	}

	public function isCycling($id){
		$id = (int) $id;
		$cyclingArray = $this->getCycling();
		return array_search($id, $cyclingArray);
	}	

}