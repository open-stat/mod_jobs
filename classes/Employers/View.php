<?php
namespace Core2\Mod\Jobs\Employers;
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
    public function getTable(string $base_url): Table\Db {

        $table = new Table\Db($this->resId);
        $table->setTable("mod_jobs_employers");
        $table->setPrimaryKey('id');
        $table->setEditUrl("{$base_url}&edit=TCOL_ID");
        $table->hideCheckboxes();
        $table->showColumnManage();

        $table->setQuery("
            SELECT je.id,
                   je.title,
                   je.unp,
                   je.region,
                   je.url,
                   je.description
            FROM mod_jobs_employers AS je
            ORDER BY je.title DESC
        ");

        $table->addFilter("je.title", $table::FILTER_TEXT, $this->_("Название работодателя"));

        $table->addSearch($this->_("УНП"),      "je.unp",         $table::SEARCH_TEXT);
        $table->addSearch($this->_("Регион"),   "je.region",      $table::SEARCH_TEXT);
        $table->addSearch($this->_("Описание"), "je.description", $table::SEARCH_TEXT);


        $table->addColumn($this->_("Название работодателя"), 'title',       $table::COLUMN_TEXT);
        $table->addColumn($this->_("УНП"),                   'unp',         $table::COLUMN_TEXT);
        $table->addColumn($this->_("Регион"),                'region',      $table::COLUMN_TEXT);
        $table->addColumn($this->_('Ссылка на страницу'),    'url',         $table::COLUMN_HTML, 150);
        $table->addColumn($this->_('Описание'),              'description', $table::COLUMN_TEXT)->hide();

        $rows = $table->fetchRows();
        foreach ($rows as $row) {

            $url      = $row->url->getValue();
            $row->url = "<a href=\"{$url}\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";
        }

        return $table;
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract $employer
     * @return Table\Db
     * @throws Table\Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTableVacancy(\Zend_Db_Table_Row_Abstract $employer): Table\Db {

        $table = new Table\Db($this->resId. 'xxx_vacancy');
        $table->setTable("mod_jobs_employers_vacancies");
        $table->setPrimaryKey('id');
        $table->setEditUrl("index.php?module=jobs&edit=TCOL_ID");
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
                   jev.date_close
            
            FROM mod_jobs_employers_vacancies AS jev
                LEFT JOIN mod_jobs_employers AS je ON jev.employer_id = je.id
                LEFT JOIN mod_jobs_sources AS js ON jev.source_id = js.id
            WHERE jev.employer_id = ?
            ORDER BY jev.id DESC
        ", [
            $employer->id
        ]);

        $table->addFilter("CONCAT_WS('|', jev.title, jev.tags)", $table::FILTER_TEXT, $this->_("Название вакансии, теги"));

        $table->addSearch($this->_("Регион"),            "jev.region",          $table::SEARCH_TEXT);
        $table->addSearch($this->_("Адрес"),             "jev.address",         $table::SEARCH_TEXT);
        $table->addSearch($this->_("Краткое описание"),  "jev.description",     $table::SEARCH_TEXT);
        $table->addSearch($this->_("Зп от BYN"),         "jev.salary_min_byn",  $table::SEARCH_NUMBER);
        $table->addSearch($this->_("Зп до BYN"),         "jev.salary_max_byn",  $table::SEARCH_NUMBER);


        $table->addColumn($this->_("Название вакансии"),      'title',            $table::COLUMN_TEXT);
        $table->addColumn($this->_("Регион"),                 'region',           $table::COLUMN_TEXT);
        $table->addColumn($this->_('Ссылка на вакансию'),     'url',              $table::COLUMN_HTML, 150);
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

            $url      = $row->url->getValue();
            $row->url = "<a href=\"{$url}\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";

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
     * @param \Zend_Db_Table_Row_Abstract $employer
     * @return \editTable
     */
    public function getEdit(\Zend_Db_Table_Row_Abstract $employer): \editTable {

        $edit = new \editTable($this->resId);
        $edit->table = 'mod_jobs_employers';
        $edit->readOnly = true;

        $edit->SQL = [
            [
                'id'          => $employer->id,
                'title'       => $employer->title,
                'unp'         => $employer->unp,
                'url'         => $employer->url,
                'region'      => $employer->region,
                'description' => $employer->description,
            ],
        ];


        $edit->addControl($this->_("Название работодатель"),  "TEXT");
        $edit->addControl($this->_("УНП"),                    "TEXT");
        $edit->addControl($this->_('Ссылка на работодателя'), "LINK");
        $edit->addControl($this->_("Регион"),                 "TEXT");
        $edit->addControl($this->_("Краткое описание"),       "TEXT");


        $edit->firstColWidth = "200px";
        return $edit;
    }
}
