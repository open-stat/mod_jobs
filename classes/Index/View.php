<?php
namespace Core2\Mod\Jobs\Index;
use Core2\Classes\Table;


require_once DOC_ROOT . "core2/inc/classes/Common.php";
require_once DOC_ROOT . "core2/inc/classes/Alert.php";
require_once DOC_ROOT . "core2/inc/classes/class.edit.php";
require_once DOC_ROOT . "core2/inc/classes/Table/Db.php";


/**
 * @property \ModJobsController $modJobs
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

        $table->addFilter("CONCAT_WS('|', jev.title, jev.tags)", $table::FILTER_TEXT, $this->_("???????????????? ????????????????, ????????"));

        $table->addSearch($this->_("????????????????????????"),      "je.title",            $table::SEARCH_TEXT);
        $table->addSearch($this->_("????????????"),            "jev.region",          $table::SEARCH_TEXT);
        $table->addSearch($this->_("??????????"),             "jev.address",         $table::SEARCH_TEXT);
        $table->addSearch($this->_("?????????????? ????????????????"),  "jev.description",     $table::SEARCH_TEXT);
        $table->addSearch($this->_("???? ???? BYN"),         "jev.salary_min_byn",  $table::SEARCH_NUMBER);
        $table->addSearch($this->_("???? ???? BYN"),         "jev.salary_max_byn",  $table::SEARCH_NUMBER);


        $table->addColumn($this->_("???????????????? ????????????????"),      'title',            $table::COLUMN_TEXT);
        $table->addColumn($this->_("????????????????????????"),           'employers_title',  $table::COLUMN_TEXT);
        $table->addColumn($this->_("??????"),                    'employers_unp'  ,  $table::COLUMN_TEXT)->hide();
        $table->addColumn($this->_("????????????"),                 'region',           $table::COLUMN_TEXT);
        $table->addColumn($this->_('???????????? ???? ????????????????'),     'url',              $table::COLUMN_HTML, 150);
        $table->addColumn($this->_('???????????? ???? ????????????????????????'), 'employers_url',    $table::COLUMN_HTML, 150)->hide();
        $table->addColumn($this->_("????????"),                   'tags',             $table::COLUMN_TEXT);
        $table->addColumn($this->_("???? ???? BYN"),              'salary_min_byn',   $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("???? ???? BYN"),              'salary_max_byn',   $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("???? ????"),                  'salary_min',       $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("???? ????"),                  'salary_max',       $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("????????????"),                 'currency',         $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("??????????"),                  'address',          $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("????????????????????"),             'coordinates',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("?????????????? ????????????????"),       'description',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("???????? ????????????????????"),        'date_publish',     $table::COLUMN_DATE, 120);


        $rows = $table->fetchRows();
        foreach ($rows as $row) {

            $url           = $row->url->getValue();
            $employers_url = $row->employers_url->getValue();

            $row->url           = "<a href=\"{$url}\" rel=\"noreferrer\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";
            $row->employers_url = "<a href=\"{$employers_url}\" rel=\"noreferrer\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";

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

        $table->addFilter("CONCAT_WS('|', jev.title, jev.tags)", $table::FILTER_TEXT, $this->_("???????????????? ????????????????, ????????"));

        $table->addSearch($this->_("????????????????????????"),      "je.title",            $table::SEARCH_TEXT);
        $table->addSearch($this->_("????????????"),            "jev.region",          $table::SEARCH_TEXT);
        $table->addSearch($this->_("??????????"),             "jev.address",         $table::SEARCH_TEXT);
        $table->addSearch($this->_("?????????????? ????????????????"),  "jev.description",     $table::SEARCH_TEXT);
        $table->addSearch($this->_("???? ???? BYN"),         "jev.salary_min_byn",  $table::SEARCH_NUMBER);
        $table->addSearch($this->_("???? ???? BYN"),         "jev.salary_max_byn",  $table::SEARCH_NUMBER);


        $table->addColumn($this->_("???????????????? ????????????????"),      'title',            $table::COLUMN_TEXT);
        $table->addColumn($this->_("????????????????????????"),           'employers_title',  $table::COLUMN_TEXT);
        $table->addColumn($this->_("??????"),                    'employers_unp'  ,  $table::COLUMN_TEXT)->hide();
        $table->addColumn($this->_("????????????"),                 'region',           $table::COLUMN_TEXT);
        $table->addColumn($this->_('???????????? ???? ????????????????'),     'url',              $table::COLUMN_HTML, 150);
        $table->addColumn($this->_('???????????? ???? ????????????????????????'), 'employers_url',    $table::COLUMN_TEXT, 150)->hide();
        $table->addColumn($this->_("????????"),                   'tags',             $table::COLUMN_TEXT);
        $table->addColumn($this->_("???? ???? BYN"),              'salary_min_byn',   $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("???? ???? BYN"),              'salary_max_byn',   $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("???? ????"),                  'salary_min',       $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("???? ????"),                  'salary_max',       $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("????????????"),                 'currency',         $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("??????????"),                  'address',          $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("????????????????????"),             'coordinates',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("?????????????? ????????????????"),       'description',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("???????? ????????????????????"),        'date_publish',     $table::COLUMN_DATE, 120);


        $rows = $table->fetchRows();
        foreach ($rows as $row) {

            $url           = $row->url->getValue();
            $employers_url = $row->employers_url->getValue();

            $row->url           = "<a href=\"{$url}\" rel=\"noreferrer\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";
            $row->employers_url = "<a href=\"{$employers_url}\" rel=\"noreferrer\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";

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

        $table->addFilter("CONCAT_WS('|', jev.title, jev.tags)", $table::FILTER_TEXT, $this->_("???????????????? ????????????????, ????????"));

        $table->addSearch($this->_("????????????????????????"),      "je.title",            $table::SEARCH_TEXT);
        $table->addSearch($this->_("????????????"),            "jev.region",          $table::SEARCH_TEXT);
        $table->addSearch($this->_("??????????"),             "jev.address",         $table::SEARCH_TEXT);
        $table->addSearch($this->_("?????????????? ????????????????"),  "jev.description",     $table::SEARCH_TEXT);
        $table->addSearch($this->_("???? ???? BYN"),         "jev.salary_min_byn",  $table::SEARCH_NUMBER);
        $table->addSearch($this->_("???? ???? BYN"),         "jev.salary_max_byn",  $table::SEARCH_NUMBER);


        $table->addColumn($this->_("???????????????? ????????????????"),      'title',            $table::COLUMN_TEXT);
        $table->addColumn($this->_("????????????????????????"),           'employers_title',  $table::COLUMN_TEXT);
        $table->addColumn($this->_("??????"),                    'employers_unp'  ,  $table::COLUMN_TEXT)->hide();
        $table->addColumn($this->_("????????????"),                 'region',           $table::COLUMN_TEXT);
        $table->addColumn($this->_('???????????? ???? ????????????????'),     'url',              $table::COLUMN_HTML, 150);
        $table->addColumn($this->_('???????????? ???? ????????????????????????'), 'employers_url',    $table::COLUMN_TEXT, 150)->hide();
        $table->addColumn($this->_("????????"),                   'tags',             $table::COLUMN_TEXT);
        $table->addColumn($this->_("???? ???? BYN"),              'salary_min_byn',   $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("???? ???? BYN"),              'salary_max_byn',   $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("???? ????"),                  'salary_min',       $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("???? ????"),                  'salary_max',       $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("????????????"),                 'currency',         $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("??????????"),                  'address',          $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("????????????????????"),             'coordinates',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("?????????????? ????????????????"),       'description',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("???????? ????????????????????"),        'date_publish',     $table::COLUMN_DATE, 120);


        $rows = $table->fetchRows();
        foreach ($rows as $row) {

            $url           = $row->url->getValue();
            $employers_url = $row->employers_url->getValue();

            $row->url           = "<a href=\"{$url}\" rel=\"noreferrer\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";
            $row->employers_url = "<a href=\"{$employers_url}\" rel=\"noreferrer\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";

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
     * @throws \Zend_Db_Table_Exception
     */
    public function getEdit(\Zend_Db_Table_Row_Abstract $vacancy): \editTable {

        $edit = new \editTable($this->resId);
        $edit->table = 'mod_jobs_employers_vacancies';
        $edit->readOnly = true;


        $employer = $this->modJobs->dataJobsEmployers->find($vacancy->employer_id)->current();

        $edit->SQL = [
            [
                'id'               => $vacancy->id,
                'title'            => $vacancy->title,
                'employer_title'   => $employer->title,
                'employer_unp'     => $employer->unp,
                'region'           => $vacancy->region,
                'url'              => $vacancy->url,
                'employer_url'     => $employer->url,
                'tags'             => $vacancy->tags,
                'salary_min_byn'   => $vacancy->salary_min_byn,
                'salary_max_byn'   => $vacancy->salary_max_byn,
                'salary_min'       => $vacancy->salary_min,
                'salary_max'       => $vacancy->salary_max,
                'currency'         => $vacancy->currency,
                'address'          => $vacancy->address,
                'lat'              => $vacancy->lat,
                'lng'              => $vacancy->lng,
                'description'      => $vacancy->description,
                'description_full' => $vacancy->description_full,
                'date_publish'     => $vacancy->date_publish,
                'date_close'       => $vacancy->date_close,
            ],
        ];


        $edit->addControl($this->_("???????????????? ????????????????"),      "TEXT");
        $edit->addControl($this->_("????????????????????????"),           "TEXT");
        $edit->addControl($this->_("??????"),                    "TEXT");
        $edit->addControl($this->_("????????????"),                 "TEXT");
        $edit->addControl($this->_('???????????? ???? ????????????????'),     "LINK");
        $edit->addControl($this->_('???????????? ???? ????????????????????????'), "LINK");
        $edit->addControl($this->_("????????"),                   "TEXT");
        $edit->addControl($this->_("???? ???? BYN"),              "MONEY");
        $edit->addControl($this->_("???? ???? BYN"),              "MONEY");
        $edit->addControl($this->_("???? ????"),                  "MONEY");
        $edit->addControl($this->_("???? ????"),                  "MONEY");
        $edit->addControl($this->_("????????????"),                 "TEXT");
        $edit->addControl($this->_("??????????"),                  "TEXT");
        $edit->addControl($this->_("????????????"),                 "TEXT");
        $edit->addControl($this->_("??????????????"),                "TEXT");
        $edit->addControl($this->_("?????????????? ????????????????"),       "TEXT");
        $edit->addControl($this->_("???????????? ????????????????"),        "TEXT");
        $edit->addControl($this->_("???????? ????????????????????"),        "DATE2");
        $edit->addControl($this->_("???????? ????????????????"),          "DATE2");


        $edit->firstColWidth = "200px";
        return $edit;
    }
}
