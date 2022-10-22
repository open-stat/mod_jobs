<?php
namespace Core2\Mod\Jobs\Index;
use Core2\Classes\Table;


require_once DOC_ROOT . "core2/inc/classes/Common.php";
require_once DOC_ROOT . "core2/inc/classes/Alert.php";
require_once DOC_ROOT . "core2/inc/classes/class.edit.php";
require_once DOC_ROOT . "core2/inc/classes/Table/Db.php";


/**
 * @property \ModProductsController $modProducts
 */
class View extends \Common {


    /**
     * @param string $base_url
     * @return Table\Db
     * @throws Table\Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTableActive(string $base_url): Table\Db {

        $table = new Table\Db($this->resId . 'xxx_active');
        $table->setTable("mod_jobs_employers_vacancies");
        $table->setPrimaryKey('id');
        $table->setEditUrl("{$base_url}&edit=TCOL_ID");
        $table->hideCheckboxes();
        $table->showColumnManage();

        $table->setQuery("
            SELECT jev.id,
                   jev.title,
                   jev.region,
                   jev.url,
                   jev.tags,
                   jev.salary_min_byn,
                   jev.salary_max_byn,
                   jev.salary_min,
                   jev.salary_max,
                   UPPER(jev.currency) AS currency,
                   jev.address,
                   CONCAT_WS(', ', jev.lat, jev.lng) AS coordinates,
                   jev.description,
                   jev.description_full,
                   jev.date_publish,
                   jev.date_close,
                   
                   je.title AS employers_title, 
                   je.unp AS employers_unp, 
                   je.url AS employers_url
            
            FROM mod_jobs_employers_vacancies AS jev
                LEFT JOIN mod_jobs_employers AS je ON jev.employer_id = je.id
                LEFT JOIN mod_jobs_sources AS js ON jev.source_id = js.id
            WHERE jev.date_close IS NULL
            ORDER BY jev.id DESC
        ");

        $table->addFilter("CONCAT_WS('|', jev.title, jev.tags)", $table::FILTER_TEXT, $this->_("Название вакансии, теги"));

        $table->addSearch($this->_("Работодатель"),      "je.title",            $table::SEARCH_TEXT);
        $table->addSearch($this->_("Регион"),            "jev.region",          $table::SEARCH_TEXT);
        $table->addSearch($this->_("Адрес"),             "jev.address",         $table::SEARCH_TEXT);
        $table->addSearch($this->_("Краткое описание"),  "jev.description",     $table::SEARCH_TEXT);
        $table->addSearch($this->_("Зп от BYN"),         "jev.salary_min_byn",  $table::SEARCH_NUMBER);
        $table->addSearch($this->_("Зп до BYN"),         "jev.salary_max_byn",  $table::SEARCH_NUMBER);


        $table->addColumn($this->_("Название вакансии"),      'title',            $table::COLUMN_TEXT);
        $table->addColumn($this->_("Работодатель"),           'employers_title',  $table::COLUMN_TEXT);
        $table->addColumn($this->_("УНП"),                    'employers_unp'  ,  $table::COLUMN_TEXT)->hide();
        $table->addColumn($this->_("Регион"),                 'region',           $table::COLUMN_TEXT);
        $table->addColumn($this->_('Ссылка на вакансию'),     'url',              $table::COLUMN_HTML, 150);
        $table->addColumn($this->_('Ссылка на работодателя'), 'employers_url',    $table::COLUMN_HTML, 150)->hide();
        $table->addColumn($this->_("Теги"),                   'tags',             $table::COLUMN_TEXT);
        $table->addColumn($this->_("Зп от BYN"),              'salary_min_byn',   $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("Зп до BYN"),              'salary_max_byn',   $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("Зп от"),                  'salary_min',       $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("Зп до"),                  'salary_max',       $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("Валюта"),                 'currency',         $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Адрес"),                  'address',          $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Координаты"),             'coordinates',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Краткое описание"),       'description',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Дата публикации"),        'date_publish',     $table::COLUMN_DATE, 120);


        $rows = $table->fetchRows();
        foreach ($rows as $row) {

            $url           = $row->url->getValue();
            $employers_url = $row->employers_url->getValue();

            $row->url           = "<a href=\"{$url}\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";
            $row->employers_url = "<a href=\"{$employers_url}\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";

            if ($row->salary_min_byn->getValue()) {
                $row->salary_min_byn = \Tool::commafy($row->salary_min_byn->getValue()) . " <small class=\"text-muted\">BYN</small>";
                $row->salary_min_byn->setAttr('class', 'text-right');
            }

            if ($row->salary_max_byn->getValue()) {
                $row->salary_max_byn = \Tool::commafy($row->salary_max_byn->getValue()) . " <small class=\"text-muted\">BYN</small>";
                $row->salary_max_byn->setAttr('class', 'text-right');
            }

            $row->salary_min = \Tool::commafy($row->salary_min->getValue());
            $row->salary_min->setAttr('class', 'text-right');

            $row->salary_max = \Tool::commafy($row->salary_max->getValue());
            $row->salary_max->setAttr('class', 'text-right');
        }

        return $table;
    }


    /**
     * @param string $base_url
     * @return Table\Db
     * @throws Table\Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTableClosed(string $base_url): Table\Db {

        $table = new Table\Db($this->resId. 'xxx_closed');
        $table->setTable("mod_jobs_employers_vacancies");
        $table->setPrimaryKey('id');
        $table->setEditUrl("{$base_url}&edit=TCOL_ID");
        $table->hideCheckboxes();
        $table->showColumnManage();

        $table->setQuery("
            SELECT jev.id,
                   jev.title,
                   jev.region,
                   jev.url,
                   jev.tags,
                   jev.salary_min_byn,
                   jev.salary_max_byn,
                   jev.salary_min,
                   jev.salary_max,
                   jev.currency,
                   jev.address,
                   CONCAT_WS(', ', jev.lat, jev.lng) AS coordinates,
                   jev.description,
                   jev.description_full,
                   jev.date_publish,
                   jev.date_close,
                   
                   je.title AS employers_title, 
                   je.unp AS employers_unp, 
                   je.url AS employers_url
            
            FROM mod_jobs_employers_vacancies AS jev
                LEFT JOIN mod_jobs_employers AS je ON jev.employer_id = je.id
                LEFT JOIN mod_jobs_sources AS js ON jev.source_id = js.id
            WHERE jev.date_close IS NOT NULL
            ORDER BY jev.id DESC
        ");

        $table->addFilter("CONCAT_WS('|', jev.title, jev.tags)", $table::FILTER_TEXT, $this->_("Название вакансии, теги"));

        $table->addSearch($this->_("Работодатель"),      "je.title",            $table::SEARCH_TEXT);
        $table->addSearch($this->_("Регион"),            "jev.region",          $table::SEARCH_TEXT);
        $table->addSearch($this->_("Адрес"),             "jev.address",         $table::SEARCH_TEXT);
        $table->addSearch($this->_("Краткое описание"),  "jev.description",     $table::SEARCH_TEXT);
        $table->addSearch($this->_("Зп от BYN"),         "jev.salary_min_byn",  $table::SEARCH_NUMBER);
        $table->addSearch($this->_("Зп до BYN"),         "jev.salary_max_byn",  $table::SEARCH_NUMBER);


        $table->addColumn($this->_("Название вакансии"),      'title',            $table::COLUMN_TEXT);
        $table->addColumn($this->_("Работодатель"),           'employers_title',  $table::COLUMN_TEXT);
        $table->addColumn($this->_("УНП"),                    'employers_unp'  ,  $table::COLUMN_TEXT)->hide();
        $table->addColumn($this->_("Регион"),                 'region',           $table::COLUMN_TEXT);
        $table->addColumn($this->_('Ссылка на вакансию'),     'url',              $table::COLUMN_HTML, 150);
        $table->addColumn($this->_('Ссылка на работодателя'), 'employers_url',    $table::COLUMN_TEXT, 150)->hide();
        $table->addColumn($this->_("Теги"),                   'tags',             $table::COLUMN_TEXT);
        $table->addColumn($this->_("Зп от BYN"),              'salary_min_byn',   $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("Зп до BYN"),              'salary_max_byn',   $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("Зп от"),                  'salary_min',       $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("Зп до"),                  'salary_max',       $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("Валюта"),                 'currency',         $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Адрес"),                  'address',          $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Координаты"),             'coordinates',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Краткое описание"),       'description',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Дата публикации"),        'date_publish',     $table::COLUMN_DATE, 120);


        $rows = $table->fetchRows();
        foreach ($rows as $row) {

            $url           = $row->url->getValue();
            $employers_url = $row->employers_url->getValue();

            $row->url           = "<a href=\"{$url}\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";
            $row->employers_url = "<a href=\"{$employers_url}\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";

            $row->salary_min_byn = \Tool::commafy($row->salary_min_byn->getValue()) . " <small class=\"text-muted\">BYN</small>";
            $row->salary_min_byn->setAttr('class', 'text-right');

            $row->salary_max_byn = \Tool::commafy($row->salary_max_byn->getValue()) . " <small class=\"text-muted\">BYN</small>";
            $row->salary_max_byn->setAttr('class', 'text-right');

            $row->salary_min = \Tool::commafy($row->salary_min->getValue());
            $row->salary_min->setAttr('class', 'text-right');

            $row->salary_max = \Tool::commafy($row->salary_max->getValue());
            $row->salary_max->setAttr('class', 'text-right');
        }

        return $table;
    }


    /**
     * @param string             $base_url
     * @param \Zend_Db_Table_Row $vacancy
     * @return Table\Db
     * @throws Table\Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTableEmployerVacancy(string $base_url, \Zend_Db_Table_Row $vacancy): Table\Db {

        $table = new Table\Db("{$this->resId}xxx_employer");
        $table->hideCheckboxes();
        $table->setEditUrl("{$base_url}&edit=TCOL_ID");
        $table->hideCheckboxes();
        $table->showColumnManage();

        $table->setQuery("
            SELECT jev.id,
                   jev.title,
                   jev.region,
                   jev.url,
                   jev.tags,
                   jev.salary_min_byn,
                   jev.salary_max_byn,
                   jev.salary_min,
                   jev.salary_max,
                   jev.currency,
                   jev.address,
                   CONCAT_WS(', ', jev.lat, jev.lng) AS coordinates,
                   jev.description,
                   jev.description_full,
                   jev.date_publish,
                   jev.date_close,
                   
                   je.title AS employers_title, 
                   je.unp AS employers_unp, 
                   je.url AS employers_url
            
            FROM mod_jobs_employers_vacancies AS jev
                LEFT JOIN mod_jobs_employers AS je ON jev.employer_id = je.id
                LEFT JOIN mod_jobs_sources AS js ON jev.source_id = js.id
            WHERE je.id = ?
            ORDER BY jev.id DESC
        ", [
            $vacancy->employer_id
        ]);

        $table->addFilter("CONCAT_WS('|', jev.title, jev.tags)", $table::FILTER_TEXT, $this->_("Название вакансии, теги"));

        $table->addSearch($this->_("Работодатель"),      "je.title",            $table::SEARCH_TEXT);
        $table->addSearch($this->_("Регион"),            "jev.region",          $table::SEARCH_TEXT);
        $table->addSearch($this->_("Адрес"),             "jev.address",         $table::SEARCH_TEXT);
        $table->addSearch($this->_("Краткое описание"),  "jev.description",     $table::SEARCH_TEXT);
        $table->addSearch($this->_("Зп от BYN"),         "jev.salary_min_byn",  $table::SEARCH_NUMBER);
        $table->addSearch($this->_("Зп до BYN"),         "jev.salary_max_byn",  $table::SEARCH_NUMBER);


        $table->addColumn($this->_("Название вакансии"),      'title',            $table::COLUMN_TEXT);
        $table->addColumn($this->_("Работодатель"),           'employers_title',  $table::COLUMN_TEXT);
        $table->addColumn($this->_("УНП"),                    'employers_unp'  ,  $table::COLUMN_TEXT)->hide();
        $table->addColumn($this->_("Регион"),                 'region',           $table::COLUMN_TEXT);
        $table->addColumn($this->_('Ссылка на вакансию'),     'url',              $table::COLUMN_HTML, 150);
        $table->addColumn($this->_('Ссылка на работодателя'), 'employers_url',    $table::COLUMN_TEXT, 150)->hide();
        $table->addColumn($this->_("Теги"),                   'tags',             $table::COLUMN_TEXT);
        $table->addColumn($this->_("Зп от BYN"),              'salary_min_byn',   $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("Зп до BYN"),              'salary_max_byn',   $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("Зп от"),                  'salary_min',       $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("Зп до"),                  'salary_max',       $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("Валюта"),                 'currency',         $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Адрес"),                  'address',          $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Координаты"),             'coordinates',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Краткое описание"),       'description',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Дата публикации"),        'date_publish',     $table::COLUMN_DATE, 120);


        $rows = $table->fetchRows();
        foreach ($rows as $row) {

            $url           = $row->url->getValue();
            $employers_url = $row->employers_url->getValue();

            $row->url           = "<a href=\"{$url}\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";
            $row->employers_url = "<a href=\"{$employers_url}\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";

            if ($row->salary_min_byn->getValue()) {
                $row->salary_min_byn = \Tool::commafy($row->salary_min_byn->getValue()) . " <small class=\"text-muted\">BYN</small>";
                $row->salary_min_byn->setAttr('class', 'text-right');
            }

            if ($row->salary_max_byn->getValue()) {
                $row->salary_max_byn = \Tool::commafy($row->salary_max_byn->getValue()) . " <small class=\"text-muted\">BYN</small>";
                $row->salary_max_byn->setAttr('class', 'text-right');
            }

            $row->salary_min = \Tool::commafy($row->salary_min->getValue());
            $row->salary_min->setAttr('class', 'text-right');

            $row->salary_max = \Tool::commafy($row->salary_max->getValue());
            $row->salary_max->setAttr('class', 'text-right');
        }

        return $table;
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract $vacancy
     * @return \editTable
     */
    public function getEdit(\Zend_Db_Table_Row_Abstract $vacancy): \editTable {

        $edit = new \editTable($this->resId);
        $edit->table = 'mod_jobs_employers_vacancies';
        $edit->readOnly = true;

        $edit->SQL = [
            [
                'id'               => $vacancy->id,
                'title'            => $vacancy->title,
                'region'           => $vacancy->region,
                'url'              => $vacancy->url,
                'tags'             => $vacancy->tags,
                'salary_min_byn'   => $vacancy->salary_min_byn,
                'salary_max_byn'   => $vacancy->salary_max_byn,
                'salary_min'       => $vacancy->salary_min,
                'salary_max'       => $vacancy->salary_max,
                'currency'         => $vacancy->currency,
                'address'          => $vacancy->address,
                'lat'              => $vacancy->lat,
                'lng'              => $vacancy->lng,
                'date_publish'     => $vacancy->date_publish,
                'date_close'       => $vacancy->date_close,
                'description'      => $vacancy->description,
                'description_full' => $vacancy->description_full,
            ],
        ];


        $edit->addControl($this->_("Название вакансии"),      "TEXT");
        $edit->addControl($this->_("Работодатель"),           "TEXT");
        $edit->addControl($this->_("УНП"),                    "TEXT");
        $edit->addControl($this->_("Регион"),                 "TEXT");
        $edit->addControl($this->_('Ссылка на вакансию'),     "LINK");
        $edit->addControl($this->_('Ссылка на работодателя'), "LINK");
        $edit->addControl($this->_("Теги"),                   "TEXT");
        $edit->addControl($this->_("Зп от BYN"),              "MONEY");
        $edit->addControl($this->_("Зп до BYN"),              "MONEY");
        $edit->addControl($this->_("Зп от"),                  "MONEY");
        $edit->addControl($this->_("Зп до"),                  "MONEY");
        $edit->addControl($this->_("Валюта"),                 "TEXT");
        $edit->addControl($this->_("Адрес"),                  "TEXT");
        $edit->addControl($this->_("Широта"),                 "TEXT");
        $edit->addControl($this->_("Долгота"),                "TEXT");
        $edit->addControl($this->_("Краткое описание"),       "TEXT");
        $edit->addControl($this->_("Дата публикации"),        "TEXT");


        $edit->firstColWidth = "200px";
        return $edit;
    }
}
