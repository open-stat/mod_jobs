<?php
use Core2\Mod\Jobs;

require_once DOC_ROOT . "core2/inc/classes/Common.php";
require_once DOC_ROOT . "core2/inc/classes/Alert.php";
require_once DOC_ROOT . "core2/inc/classes/Panel.php";

require_once 'classes/autoload.php';


/**
 * @property JobsCategories                    $dataJobsCategories
 * @property JobsCategoriesSummary             $dataJobsCategoriesSummary
 * @property JobsProfessions                   $dataJobsProfessions
 * @property JobsProfessionsSummary            $dataJobsProfessionsSummary
 * @property JobsCurrency                      $dataJobsCurrency
 * @property JobsEmployers                     $dataJobsEmployers
 * @property JobsEmployersVacancies            $dataJobsEmployersVacancies
 * @property JobsEmployersVacanciesCategories  $dataJobsEmployersVacanciesCategories
 * @property JobsEmployersVacanciesProfessions $dataJobsEmployersVacanciesProfessions
 * @property JobsEmployersVacanciesActivity    $dataJobsEmployersVacanciesActivity
 * @property JobsPages                         $dataJobsPages
 * @property JobsResume                        $dataJobsResume
 * @property JobsResumeCategories              $dataJobsResumeCategories
 * @property JobsResumeProfessions             $dataJobsResumeProfessions
 * @property JobsResumeActivity                $dataJobsResumeActivity
 * @property JobsSources                       $dataJobsSources
 * @property JobsSummary                       $dataJobsSummary
 */
class ModJobsController extends Common {

    /**
     * @return string
     * @throws Exception
     */
    public function action_vacancies(): string {

        $base_url = 'index.php?module=jobs&action=vacancies';

        $view     = new Jobs\Index\View();
        $panel    = new Panel('tab');
        $content  = [];

        try {
            if ( ! empty($_GET['edit'])) {
                $vacancy = $this->dataJobsEmployersVacancies->find($_GET['edit'])->current();

                if (empty($vacancy)) {
                    throw new Exception('Указанная вакансия не найдена');
                }

                $employer     = $this->dataJobsEmployers->find($vacancy->employer_id)->current();
                $date_publish = $vacancy->date_publish
                    ? (new \DateTime($vacancy->date_publish))->format('d.m.Y H:i:s')
                    : '';

                $description = "$date_publish | {$employer->title}";


                $panel->setTitle($vacancy->title, $description, $base_url . ($vacancy->date_close ? '&tab=closed' : ''));


                $panel->addTab('Вакансия',              'vacancy',            "{$base_url}&edit={$vacancy->id}");
                $panel->addTab('Вакансии работодателя', 'employer_vacancies', "{$base_url}&edit={$vacancy->id}");

                switch ($panel->getActiveTab()) {
                    case 'vacancy':            $content[] = $view->getEdit($vacancy)->render(); break;
                    case 'employer_vacancies': $content[] = $view->getTableEmployerVacancy($base_url, $vacancy)->render();break;
                }

            } else {
                $panel->addTab('Активные', 'active', $base_url);
                $panel->addTab('Закрытые', 'closed', $base_url);

                switch ($panel->getActiveTab()) {
                    case 'active': $content[] = $view->getTableActive($base_url)->render(); break;
                    case 'closed': $content[] = $view->getTableClosed($base_url)->render();break;
                }
            }

        } catch (\Exception $e) {
            $content[] = Alert::danger($e->getMessage(), 'Ошибка');
        }

        $panel->setContent(implode('', $content));
        return $panel->render();
    }


    /**
     * @return string
     * @throws Exception
     */
    public function action_employers(): string {

        $base_url = 'index.php?module=jobs&action=employers';

        $view     = new Jobs\Employers\View();
        $panel    = new Panel('tab');
        $content  = [];

        try {
            if ( ! empty($_GET['edit'])) {
                $employer = $this->dataJobsEmployers->find($_GET['edit'])->current();

                if (empty($employer)) {
                    throw new Exception('Указанный работодатель не найден');
                }

                $description = trim("{$employer->unp} {$employer->region}");
                $panel->setTitle($employer->title, $description, $base_url);

                $base_url .= "&edit={$employer->id}";

                $panel->addTab('Работодатель', 'vacancy',            $base_url);
                $panel->addTab('Вакансии',     'employer_vacancies', $base_url);

                switch ($panel->getActiveTab()) {
                    case 'vacancy':            $content[] = $view->getEdit($employer)->render(); break;
                    case 'employer_vacancies': $content[] = $view->getTableVacancy($employer)->render();break;
                }

            } else {
                $content[] = $view->getTable($base_url)->render();
            }

        } catch (\Exception $e) {
            $content[] = Alert::danger($e->getMessage(), 'Ошибка');
        }

        $panel->setContent(implode('', $content));
        return $panel->render();
    }


    /**
     * @return string
     * @throws Exception
     */
    public function action_resume(): string {

        $base_url = 'index.php?module=jobs&action=resume';

        $view     = new Jobs\Resume\View();
        $panel    = new Panel('tab');
        $content  = [];

        try {
            if ( ! empty($_GET['edit'])) {
                $resume = $this->dataJobsResume->find($_GET['edit'])->current();

                if (empty($resume)) {
                    throw new Exception('Указанное резюме не найдено');
                }

                $panel->setTitle($resume->title, $base_url);
                $content[] = $view->getEdit($resume)->render();

            } else {
                $panel->addTab('Активные', 'active', $base_url);
                $panel->addTab('Закрытые', 'closed', $base_url);

                switch ($panel->getActiveTab()) {
                    case 'active': $content[] = $view->getTableActive($base_url)->render(); break;
                    case 'closed': $content[] = $view->getTableClose($base_url)->render();break;
                }
            }

        } catch (\Exception $e) {
            $content[] = Alert::danger($e->getMessage(), 'Ошибка');
        }

        $panel->setContent(implode('', $content));
        return $panel->render();
    }


    /**
     * @param float         $price
     * @param string        $currency_from
     * @param Datetime|null $date
     * @return float
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws Exception
     */
    public function getConvertCurrency(float $price, string $currency_from, \Datetime $date = null): float {

        if ($currency_from == 'BYN') {
            return $price;
        }


        $date_rate = $date ?: new \DateTime();
        $currency  = $this->dataJobsCurrency->getRowByCurrencyDate($currency_from, $date_rate);

        if (empty($currency)) {
            $nbrb_currencies = (new Jobs\Index\NbrbApi())->getCurrency();

            if (empty($nbrb_currencies)) {
                throw new \Exception('Не удалось получить курсы валют');
            }

            foreach ($nbrb_currencies as $currency_row) {
                $currency = $this->dataJobsCurrency->getRowByCurrencyDate($currency_from, $date_rate);

                if (empty($currency)) {
                    $currency = $this->dataJobsCurrency->createRow([
                        'abbreviation' => $currency_row['abbreviation'],
                        'rate'         => $currency_row['rate'],
                        'scale'        => $currency_row['scale'],
                        'date_rate'    => $date_rate->format("Y-m-d")
                    ]);
                    $currency->save();
                }
            }

            $currency = $this->dataJobsCurrency->getRowByCurrencyDate($currency_from, $date_rate);
        }

        return round(($price / $currency->scale) * $currency->rate, 2);
    }
}