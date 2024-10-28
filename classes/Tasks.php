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
    public $status;
    public $created_at;
    public $updated_at;

    public static $definition = [
        'table'     =>  'tasks',
        'primary'   =>  'id_pstask',
        'multilang' =>  true,
        'fields'    =>  [
            // Standard fields
            'id_psforfait'    =>  ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'total_time'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'current_time'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'created_at'     =>  ['type' => self::TYPE_DATE, 'validate' => 'isPhpDateFormat', 'required' => false],
            'updated_at'     =>  ['type' => self::TYPE_DATE, 'validate' => 'isPhpDateFormat', 'required' => false],
            // Lang fields
            'title'     =>  ['type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 255, 'required' => true],
            'description'     =>  ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
        ]
    ];

    public static function getAllTasks()
    {
        $id_lang = (int)Context::getContext()->language->id;
        $default_lang = Configuration::get('PS_LANG_DEFAULT');

        $sql = 'SELECT t.*, 
                   COALESCE(tl.title, tld.title) AS title
            FROM ' . _DB_PREFIX_ . 'tasks t
            LEFT JOIN ' . _DB_PREFIX_ . 'tasks_lang tl 
                ON (t.id_pstask = tl.id_pstask AND tl.id_lang = ' . $id_lang . ')
            LEFT JOIN ' . _DB_PREFIX_ . 'tasks_lang tld 
                ON (t.id_pstask = tld.id_pstask AND tld.id_lang = ' . (int)$default_lang . ')
            ORDER BY t.created_at DESC';

        $tasks = Db::getInstance()->executeS($sql);

        return is_array($tasks) ? $tasks : [];
    }


    public static function isTimeFormat($time)
    {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time);
    }

    public static function convertTimeToSeconds($time)
    {
        if (!self::isTimeFormat($time)) {
            throw new Exception('Le format du temps est invalide. Le format attendu est HH:mm.');
        }

        list($hours, $minutes) = explode(':', $time);

        $hours = (int)$hours;
        $minutes = (int)$minutes;

        return $hours * 3600 + $minutes * 60;
    }


    public static function convertSecondsToTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function displayCurrentStatus($current, $row)
    {
        if ($current == 1) {
            return '<span style="color:green; font-size: 18px;">●</span>';
        } else {
            return '<span style="color:red; font-size: 18px;">●</span>';
        }
    }
}