<?php
namespace Openurbantech\News\Game;

class Year 
{
    protected $highloadID = 2;
    protected $manager;
    protected $maxSteps = 5;
    protected $maxScore = 100;
    protected $maxShoulder = 20;
    protected $resultImageFolder = '/special/cycling-years/results/';
    protected $resultImageTemplate = '/special/cycling-years/game_over_screen_%s.jpg';
    protected $resultImageDefault = '/special/cycling-years/game_over.jpg';
    protected $resultImageFont = '/special/cycling-years/Montserrat-Bold.ttf';
    protected $resultImageSalt = 'years2023';

    public function getHighloadID(){
       return $this->highloadID;
    }

    public function getManager(){
       return $this->manager;
    }

    public function getMaxSteps(){
       return $this->maxSteps;
    }

    protected function getFieldName($name){
       return 'UF_'.$name;
    }

    function __construct($highloadID = null){
        if(\Bitrix\Main\Loader::includeModule("highloadblock") && $highloadID > 0){
            $this->highloadID = $highloadID;
            $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($this->highloadID)->fetch(); 
            $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock); 
            $dataClass = $entity->getDataClass();
            $this->manager = new $dataClass;
        } else {
            $this->manager = new \Openurbantech\News\Model\ItemTable();
        }
    }

    public function getByID($id){
       $manager = $this->getManager();
       return $manager->getByID($id);
    }

    public function calculateMaxScores() {
        return $this->maxScore * $this->maxSteps;
    }

    public function prepareResultImageName($score) {
        return md5($score.'_'.$this->resultImageSalt);
    }

    public function getResultImage($score){
        $resultImageName = $this->prepareResultImageName($score);
        $template = '';
        switch(true){
            case $score == 500:
                $template = sprintf($this->resultImageTemplate, 500);
                break;
            case $score >= 400:
                $template = sprintf($this->resultImageTemplate, 400);
                break;
            case $score >= 300:
                $template = sprintf($this->resultImageTemplate, 300);
                break;
            default:
                $template = sprintf($this->resultImageTemplate, 200);
                break;
        }
        $tmpFilePath = sprintf('%s%s.jpg', $this->resultImageFolder, $resultImageName);
        $tempFile = $_SERVER["DOCUMENT_ROOT"].$tmpFilePath;
        if(file_exists($tmpFile)){
            return $tmpFilePath;
        }
        $arWatermark = array(
           'position' => 'mc',
           'type' => 'text',
           'size' => 'real',
           'alpha_level' => 100,
           'text' => $score,
           'color' => 'ffffff',
           "coefficient" => "7",
            "size" => 'big',
           "font" => $_SERVER["DOCUMENT_ROOT"].$this->resultImageFont,
        );
        $origImgPath = $_SERVER["DOCUMENT_ROOT"].$template;
        $result = \CFile::ResizeImageFile(
            $origImgPath,    
            $tempFile,
            [
                'width'=>900,
                'height'=>600
            ],
            BX_RESIZE_IMAGE_PROPORTIONAL,
            $arWatermark,
            100,
            false
        );
        if($result){
            return $tmpFilePath;
        } else {
            return $this->resultImageDefault;
        }
    }

      public function getPart($exclude = [], $retrospective = [], $partsCount = 3){
         $manager = $this->getManager();
         $params = [
            'order' => [
               $this->getFieldName('YEAR') => 'ASC',
            ],
            'group' => [
               $this->getFieldName('YEAR'),
            ],
            'select' => [
               $this->getFieldName('YEAR'),
               new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(%s)', [$this->getFieldName('YEAR')]),
            ],
         ];
         
         if(!empty($retrospective)){
            $params['filter']['!ID'] = $retrospective;
         } else if(!empty($exclude)){
            $params['filter']['!ID'] = $exclude;
         }

         $rows = $manager->getList($params);
         $years = [];
         while($row = $rows->fetch()){
            $years[] = $row[$this->getFieldName('YEAR')];
         }
         if(!empty($retrospective) && (count($years) < $this->maxSteps)){
            return $this->getPart($exclude, [], $partsCount);
         }
         $size = ceil(count($years) / $partsCount);
         $years = array_chunk($years, $size);
         $array = $years[count($params['filter']['!ID']) % $partsCount];
         return [$array[0], $array[count($array) -1]];
      }

      public function getRandom($exclude = [], $retrospective = []){
         $result = null;
         $manager = $this->getManager();
         $params = [
            'runtime' => [
               'RAND'=> [
                    'data_type' => 'float', 
                    'expression' => [
                       'RAND()'
                    ]
               ]
            ],        
            'order' => [
               'RAND'=>'ASC'
            ],
            'limit' => 1,
      ];
      if(!empty($retrospective)){
         $params['filter']['!ID'] = $retrospective;
      } else if(!empty($exclude)){
         $params['filter']['!ID'] = $exclude;
      }
      if(!empty($exclude)){
         $part = $this->getPart($exclude, $retrospective);
         $params['filter']['>='.$this->getFieldName('YEAR')] = $part[0];
         $params['filter']['<='.$this->getFieldName('YEAR')] = $part[1];
      }
      $rows = $manager->getList($params);
      if($row = $rows->fetch()){
         $result = $row;
      }
      return $result;
      }

      public function getRandomImage($exclude = [], $retrospective = []){
         $result = [
            'ID' => '',
            'IMAGE' => '',
         ];
         if($row = $this->getRandom($exclude, $retrospective)){
            $result['ID'] = (int)$row['ID'];
            $result['IMAGE'] = \CFile::getPath($row[$this->getFieldName('IMAGE')]);
         }
         return $result;
      }

      public function getBounds(){
         $result = null;
         $params = [
            'runtime' => [
               new \Bitrix\Main\Entity\ExpressionField('MIN', 'MIN(%s)', [$this->getFieldName('YEAR')]),
               new \Bitrix\Main\Entity\ExpressionField('MAX', 'MAX(%s)', [$this->getFieldName('YEAR')]),
            ],
            'select' => [
               'MIN', 'MAX'
            ],
         ];
         $manager = $this->getManager();
         $rows = $manager->getList($params);
         if($row = $rows->fetch()){
         $result = [(int)$row['MIN'], (int)$row['MAX']];
      }
      return $result;
      }

      public function calculateMaxScore($year){
         // $bounds = $this->getBounds();
         // return max($year-$bounds[0], $bounds[1] - $year);
         return $this->maxScore;
      }

      public function calculateScore($predictYear, $realYear){
         $delta = abs($predictYear - $realYear);
         if($delta < $this->maxShoulder){
            $maxScore = $this->calculateMaxScore($realYear);
            $score = (1 - ($delta / $this->maxShoulder)) * $maxScore;
            return (int) $score;
         }
         return 0;
      }

      public function getScore($id, $predictYear){
         $result = null;
         $rows = $this->getByID($id);
         if($row = $rows->fetch()){
            $result['SCORE'] = (int)$this->calculateScore($predictYear, $row[$this->getFieldName('YEAR')]);
            $result['PREDICTED_YEAR'] = (int)$predictYear;
            $result['REAL_YEAR'] = (int)$row[$this->getFieldName('YEAR')];
            $result['DESCRIPTION'] = $row[$this->getFieldName('DESCRIPTION')];
         }
         return $result;
      }

}