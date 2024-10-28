<?php
require_once _PS_MODULE_DIR_ . '/ps_forfait_suivi/classes/Tasks.php';

class AdminTaskController extends ModuleAdminController
{
    private $forfait_controller;

    /*
     * Instanciation of the class
     * Define basic settings
     */
    public function __construct()
    {
        $this->bootstrap = true; // Manage display in bootstrap mode
        $this->table = Tasks::$definition['table']; // Object Table
        $this->identifier = Tasks::$definition['primary']; // Object primary key
        $this->className = Tasks::class; // Object class
        $this->lang = true; // Flag for language usage

        // Call of the parent function to use traduction
        parent::__construct();
        require_once _PS_MODULE_DIR_ .'ps_forfait_suivi/controllers/admin/AdminForfaitController.php';
        $this->forfait_controller = new AdminForfaitController();

        $this->fields_list = [
            'id_pstask' => [
                'title' => $this->module->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'search' => false,
            ],
            'id_psforfait' => [
                'title' =>  $this->module->l('Forfait relié'),
                'align' =>  'left',
                'callback' =>'getForfaitTitle',
                'callback_object' => 'Forfaits',
                'search' => false,
            ],
            'title' => [
                'title' => $this->module->l('Nom de la tâche'),
                'lang' => true,
                'align' => 'left',
                'name' => 'title',
            ],
            'total_time' => [
                'title' => $this->module->l('Durée'),
                'name' => 'total_time',
                'class' => 'total_time',
                'callback' => 'convertSecondsToTime',
                'callback_object' => 'Tasks',
                'search' => false,
            ],
            'description' => [
                'title' => $this->module->l('Description de la tâche') !== null ? $this->module->l('Description de la tâche') : $this->module->l('N/A'),
                'lang' => true,
                'align' => 'left',
            ],
            'current' => [
                'title' => $this->module->l('Status'),
                'align' => 'center',
                'callback' => 'displayCurrentStatus',
                'callback_object' => 'Tasks',
                'search' => false,
            ],
            'created_at' => [
                'title' => $this->module->l('Date de création'),
                'align' => 'left',
                'search' => false,
            ],
            'updated_at' => [
                'title' => $this->module->l('Date de modification'),
                'align' => 'left',
                'search' => false,
            ]
        ];

        // Add actions on each lines
        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function getFormValues() {
        $fields_list;
        $idShop = $this->context->shop->id;
        $idInfo = Tasks::getTasksByShop($idShop);
        
        Shop::setContext(Shop::CONTEXT_SHOP, $idShop);
        $info = new Tasks((int) $idInfo);

        $fields_list['title'] = $info->text;
        $fields_list['description'] = $idInfo;

        return $fields_list;
    }

    public function renderList()
    {
        $tasks = Tasks::getAllTasks();
        $id_forfait = Db::getInstance()->executeS('SELECT `total_time` FROM `ps_forfaits`');
        $timeForfait = Db::getInstance()->executeS('SELECT `total_time` FROM `ps_forfaits` WHERE `id_psforfait` = ' . (int)$id_forfait);

        if (!empty($timeForfait)) {
            if ($timeForfait[0]['total_time'] === '0') {
                $this->errors[] = $this->l('Le forfait est épuisé ! Temps restant : ') . '00:00';
            } else {
                $remainingTime = Forfaits::convertSecondsToTime($timeForfait[0]['total_time']);
                $this->confirmations[] = $this->l('Le temps disponible sur le forfait est de ') . $remainingTime;
            }
        }

        if (empty($tasks)) {
            $this->displayInformation($this->l('Aucune tâche disponible.'));

            $this->fields_list = [
                'id_pstask' => [
                    'title' => $this->module->l('ID'),
                    'align' => 'center',
                    'class' => 'fixed-width-xs',
                ],
                'title' => [
                    'title' => $this->module->l('Nom de la tâche'),
                    'lang' => true,
                    'align' => 'left',
                    'name' => 'title',
                ]
            ];
        }

        return parent::renderList();
    }

    public function renderForm() {
        $this->addJS(_MODULE_DIR_ . 'ps_forfait_suivi/views/js/task-time-input.js');
        $submitName = "addTask";

        if (Tools::isSubmit("addtasks")) {
            $submitName = "addTask";
        }

        if (Tools::isSubmit("updatetasks")) {
            $submitName = "editTask";
        }

        $requete = Db::getInstance()->executeS('SELECT `ps_forfaits`.`id_psforfait` FROM `ps_forfaits` LEFT JOIN `ps_tasks` ON `ps_forfaits`.`id_psforfait` = `ps_tasks`.`id_psforfait`');
        $results = Db::getInstance()->executeS('SELECT `id_psforfait`, `title` FROM `ps_forfaits_lang`');

        $id_forfait = Db::getInstance()->executeS('SELECT `total_time` FROM `ps_forfaits`');
        $timeForfait = Db::getInstance()->executeS('SELECT `total_time` FROM `ps_forfaits` WHERE `id_psforfait` = ' . (int)$id_forfait);

        if (!empty($timeForfait)) {
            $remainingTime = Forfaits::convertSecondsToTime($timeForfait[0]['total_time']);
            $this->confirmations[] = $this->l('Le temps disponible sur le forfait est de ') . $remainingTime;
        }
        $options = array();

        foreach ($results as $result) {
            $options[] = array(
                'id_psforfait' => $result['id_psforfait'],
                'title' => $result['title'],
                'name' => 'title'
            );
        }

        $timeForfait = Db::getInstance()->executeS('SELECT `total_time` FROM `ps_forfaits` WHERE `id_psforfait`');

        $this->fields_form = [
            'legend' => [
                'title' => $this->module->l('Ajouter une Tâche'),
                'icon' => 'icon-cog',
                'method' => 'post',
            ],
            'input' => [
                [
                    'type' => 'select',
                    'options' => [
                        'query' => $options,
                        'id' => 'id_psforfait',
                        'name' => 'title'
                    ],
                    'name' => 'id_psforfait',
                    'required' => true,
                    'label' => $this->module->l('Sélectionner un forfait'),
                    'hint' => $this->module->l('Forfait sur lequel la tâche sera déduite')
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Nom'),
                    'name' => 'title',
                    'class' => 'tasks-title',
                    'size' => 255,
                    'required' => true,
                    'empty_message' => $this->module->l('Titre de la tâche'),
                    'lang' => true,
                    'hint' => $this->module->l('Renseignez le titre de la tâche')
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Temps total de la tâche (HH:mm)'),
                    'name' => 'total_time',
                    'required' => true,
                    'desc' => $this->l('Entrez le temps au format HH:mm.'),
                    'hint' => $this->l('Le temps sera converti en secondes pour les calculs.'),
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->module->l('Description'),
                    'name' => 'description',
                    'class' => 'tasks-desc',
                    'required' => true,
                    'empty_message' => $this->module->l('Renseignez la description de la tâche'),
                    'lang' => true,
                    'rows' => 10,
                    'cols' => 100,
                    'autoload_rte' => true,
                    'hint' => $this->l('Caractères Invalides :') . ' <>;=#{}'
                ],
                [
                    'type' => 'hidden',
                    'name' => 'created_at',
                    'id' => 'created_at',
                    'required' => false,
                ],
                [
                    'type' => 'hidden',
                    'name' => 'updated_at',
                    'id' => 'updated_at',
                    'required' => false,
                ]
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'name' => $submitName,
            ]
        ];

        if ($this->object->id) {
            $this->object->total_time = Tasks::convertSecondsToTime($this->object->total_time);
        }

        return parent::renderForm();
    }

    public function postProcess()
    {

        if (Tools::isSubmit("addTask")) {
            $total_time = Tools::getValue('total_time');

            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $total_time)) {
                $this->errors[] = $this->l('Le format du temps doit être de type HH:mm, avec des heures entre 00 et 23 et des minutes entre 00 et 59.');
                return false;
            }

            $_POST['total_time'] = Tasks::convertTimeToSeconds($total_time);
            $this->submitAddTask();
        }

        if (Tools::isSubmit("editTask")) {
            $total_time = Tools::getValue('total_time');

            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $total_time)) {
                $this->errors[] = $this->l('Le format du temps doit être de type HH:mm, avec des heures entre 00 et 23 et des minutes entre 00 et 59.');
                return false;
            }

            $_POST['total_time'] = Tasks::convertTimeToSeconds($total_time);
            $this->submitEditTasks();
        }

        if (Tools::isSubmit('deletetasks')) {
            $this->submitDeleteTasks();
        }
    }

    public function submitAddTask() {
        $actualTime = date('Y-m-d H:i:s');

        $timeForfait = Db::getInstance()->getValue('SELECT `total_time` FROM `ps_forfaits` WHERE `id_psforfait` = '.(int)$_POST['id_psforfait']);

        $timeTache = $_POST['total_time'];

        $timeRestant = $timeForfait - $timeTache;

        if ($timeRestant >= 0) {
            Db::getInstance()->insert(Tasks::$definition['table'], array(
                'id_psforfait' => $_POST['id_psforfait'],
                'total_time' => $timeTache,
                'current' => 1,
                'created_at' => $actualTime,
                'updated_at' => $actualTime
            ));

            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $language = (int) $lang['id_lang'];
                error_log($_POST['title_' . $language]);
                Db::getInstance()->insert(Tasks::$definition['table'] . "_lang", array(
                    'id_pstask' => (int) Db::getInstance()->Insert_ID(),
                    'id_lang' => $language,
                    'title' => pSQL($_POST['title_' . $language]),
                    'description' => pSQL($_POST['description_' . $language]),
                ), 'id_psforfait = '.(int)$_POST['id_psforfait']);
            }

            Db::getInstance()->update(Forfaits::$definition['table'], array(
                'total_time' => $timeRestant,
                'updated_at' => $actualTime
            ), 'id_psforfait = '.(int)$_POST['id_psforfait']);
        } else {
            $this->errors[] = $this->l('Le temps de la tâche dépasse le temps restant du forfait.');
        }
    }

    public function submitEditTasks() {
        $actualTime = date('Y-m-d H:i:s');

        $timeForfait = Db::getInstance()->getValue('SELECT `total_time` FROM `ps_forfaits` WHERE `id_psforfait` = '.(int)$_POST['id_psforfait']);
        $actualTimeTask = Db::getInstance()->getValue('SELECT `total_time` FROM `ps_tasks` WHERE `id_pstask` = '.(int)$_POST['id_pstask']);
        $actualTaskStatus = Db::getInstance()->getValue('SELECT `current` FROM `ps_tasks` WHERE `id_pstask` = '.(int)$_POST['id_pstask']);
        $timeTache = $_POST['total_time'];

        $timeRemains = $timeForfait;

        if ($actualTaskStatus === "1") {
            if ((string)$timeTache !== $actualTimeTask) {
                $timeRemains = $timeForfait - $timeTache;
            }
        }

        if ($actualTaskStatus === "1") {
            if ($timeRemains >= 0) {
                Db::getInstance()->update(Tasks::$definition['table'], array(
                    'id_psforfait' => $_POST['id_psforfait'],
                    'total_time' => $timeTache,
                    'created_at' => $actualTime,
                    'updated_at' => $actualTime
                ), 'id_pstask = '.(int)$_POST['id_pstask']);

                $languages = Language::getLanguages();
                foreach ($languages as $lang) {
                    $language = (int) $lang['id_lang'];
                    error_log($_POST['title_' . $language]);
                    Db::getInstance()->update(Tasks::$definition['table'] . "_lang", array(
                        'id_lang' => $language,
                        'title' => pSQL($_POST['title_' . $language]),
                        'description' => pSQL($_POST['description_' . $language]),
                    ), 'id_pstask = '.(int)$_POST['id_pstask']);
                }

                Db::getInstance()->update(Forfaits::$definition['table'], array(
                    'total_time' => $timeRemains,
                    'updated_at' => $actualTime
                ), 'id_psforfait = '.(int)$_POST['id_psforfait']);
            } else {
                $this->errors[] = $this->l('Le temps de la tâche dépasse le temps restant du forfait.');
            }
        }

        if ($actualTaskStatus === "0") {
            Db::getInstance()->update(Tasks::$definition['table'], array(
                'id_psforfait' => $_POST['id_psforfait'],
                'total_time' => $timeTache,
                'created_at' => $actualTime,
                'updated_at' => $actualTime
            ), 'id_pstask = '.(int)$_POST['id_pstask']);

            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $language = (int) $lang['id_lang'];
                error_log($_POST['title_' . $language]);
                Db::getInstance()->update(Tasks::$definition['table'] . "_lang", array(
                    'id_lang' => $language,
                    'title' => pSQL($_POST['title_' . $language]),
                    'description' => pSQL($_POST['description_' . $language]),
                ), 'id_pstask = '.(int)$_POST['id_pstask']);
            }
        }
    }


    public function submitDeleteTasks()
    {
        $id_tache = (int)$_GET['id_pstask'];

        $task = Db::getInstance()->getRow('SELECT `id_psforfait`, `total_time`, `current` FROM `' . _DB_PREFIX_ . 'tasks` WHERE `id_pstask` = ' . $id_tache);

        if (empty($task)) {
            return;
        }

        $id_psforfait = (int)$task['id_psforfait'];
        $temps_tache = (int)$task['total_time'];
        $current = (int)$task['current'];

        if ($current === 1) {
            $temps_forfait = (int)Db::getInstance()->getValue('SELECT `total_time` FROM `' . _DB_PREFIX_ . 'forfaits` WHERE `id_psforfait` = ' . $id_psforfait);

            $temps_total = $temps_tache + $temps_forfait;

            Db::getInstance()->update('forfaits', array(
                'total_time' => $temps_total
            ), 'id_psforfait = ' . $id_psforfait);
        }

        Db::getInstance()->delete(Tasks::$definition['table'], 'id_pstask = ' . $id_tache);

        Db::getInstance()->delete(Tasks::$definition['table'] . '_lang', 'id_pstask = ' . $id_tache);
    }

    public function initPageHeaderToolbar()
    {
        $id_psforfait = Db::getInstance()->getValue('SELECT `id_psforfait` FROM `' . _DB_PREFIX_ . 'forfaits` ORDER BY `id_psforfait`');

        if ($id_psforfait) {
            $timeForfait = Db::getInstance()->getValue('SELECT `total_time` FROM `' . _DB_PREFIX_ . 'forfaits` WHERE `id_psforfait` = ' . (int)$id_psforfait);

            if ($timeForfait > 0) {
                $this->page_header_toolbar_btn['Nouveau'] = array(
                    'href'  =>  self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                    'desc'  =>  $this->module->l('Ajout nouvelle tâche'),
                    'icon'  =>  'process-icon-new'
                );
            }
        }

        parent::initPageHeaderToolbar();
    }
}