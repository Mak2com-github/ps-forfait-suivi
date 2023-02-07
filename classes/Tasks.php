<?php

class Tasks extends ObjectModel
{
    /*
     * @var array
     */
    private $loadedData = [];
    public $id_pstask;
    public $id_psforfait;
    public $title;
    public $total_time;
    public $description;
    public $created_at;
    public $updated_at;

    public static $definition = [
        'table'     =>  'tasks',
        'primary'   =>  'id_pstask',
        'multilang' =>  true,
        'fields'    =>  [
            // Standard fields
            'id_psforfait'    =>  ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'total_time'     =>  ['type' => self::TYPE_DATE, 'validate' => 'isPhpDateFormat', 'required' => true],
            'created_at'     =>  ['type' => self::TYPE_DATE, 'validate' => 'isPhpDateFormat', 'required' => false],
            'updated_at'     =>  ['type' => self::TYPE_DATE, 'validate' => 'isPhpDateFormat', 'required' => false],
            // Lang fields
            'title'     =>  ['type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 255, 'required' => true],
            'description'     =>  ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
        ]
    ];
}