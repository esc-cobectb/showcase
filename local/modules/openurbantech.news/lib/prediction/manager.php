<?php
namespace Openurbantech\News\Prediction;

class Manager 
{

    protected $manager;

    public function getManager(){
       return $this->manager;
    }

    public function getMaxSteps(){
       return $this->maxSteps;
    }

    protected function getFieldName($name){
       return $name;
    }

    function __construct(){
        $this->manager = new \Openurbantech\News\Model\PredictionTable();
    }

    public function getByID($id){
       $manager = $this->getManager();
       return $manager->getByID($id);
    }

    public function save($data) {
        $result = NULL;
        $manager = $this->getManager();
        $rows = $manager->getList(['filter' => $data]);
        if ($row = $rows->fetch()) {
            $result = $row['ID'];
        } else {
            $data['CREATED'] = new \Bitrix\Main\Type\DateTime();
            $data['UPDATED'] = new \Bitrix\Main\Type\DateTime();
            $saveResult = $manager->add($data);
            if ($saveResult->isSuccess()) {
                $result = $saveResult->getId();
            }
        }
        return $result;
    }
    
    public function makePrediction($userID, $eventCode, $value) {
        $result = $this->getPrediction($userID, $eventCode);
        if(!$result){            
            $data = [
                'USER_ID' => $userID,
                'EVENT_CODE' => $eventCode,
                'VALUE' => $value,
            ];
            $result = $this->save($data);
        }
        return $result;
    }


    public function getPrediction($userID, $eventCode){
        $result = null;
        if($userID > 0 && $eventCode != ''){            
            $manager = $this->getManager();
            $params = [
                'filter' => [
                    'USER_ID' => $userID,
                    'EVENT_CODE' => $eventCode,
                ],
            ];
            $rows = $manager->getList($params);
            if ($row = $rows->fetch()) {
                $result = $row['VALUE'];
            }
        }
        return $result;
    }

}