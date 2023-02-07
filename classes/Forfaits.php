<?php

class Forfaits extends ObjectModel
{
    /**
     * @var array
     */
    private $loadedData = [];
    public $id_psforfait;
    public $title;
    public $total_time;
    public $description;
    public $created_at;
    public $updated_at;

    public static $definition = [
        'table'     =>  'forfaits',
        'primary'   =>  'id_psforfait',
        'multilang' =>  true,
        'fields'    =>  [
            // Standard fields
            'total_time'     =>  ['type' => self::TYPE_DATE, 'validate' => 'isPhpDateFormat', 'required' => true],
            'created_at'     =>  ['type' => self::TYPE_DATE, 'validate' => 'isPhpDateFormat', 'required' => false],
            'updated_at'     =>  ['type' => self::TYPE_DATE, 'validate' => 'isPhpDateFormat', 'required' => false],
            // Lang fields
            'title'     =>  ['type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 255, 'required' => true],
            'description'     =>  ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
        ],
    ];

}