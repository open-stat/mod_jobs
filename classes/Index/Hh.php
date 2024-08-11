<?php
namespace Core2\Mod\Jobs\Index;
use Core2\Mod\Jobs\Index;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

require_once __DIR__ . '/Source.php';


/**
 * @property \ModProxyController $modProxy
 */
abstract class Hh extends Index\Source {

    protected string $base_url  = 'https://hh.ru';
    private array $cookies      = [];
    private array $cookies_need = [
        '__ddg1_',
        '_xsrf',
        'total_searches',
        'hhtoken',
        'hhuid',
    ];

    private array $months_ru = [
        'январь'   => "01",  'января'   => "01",  'янв' => "01",
        'февраль'  => "02",  'февраля'  => "02",  'фев' => "02",
        'март'     => "03",  'марта'    => "03",  'мар' => "03",
        'апрель'   => "04",  'апреля'   => "04",  'апр' => "04",
        'май'      => "05",  'мая'      => "05",
        'июнь'     => "06",  'июня'     => "06",  'июн' => "06",
        'июль'     => "07",  'июля'     => "07",  'июл' => "07",
        'август'   => "08",  'августа'  => "08",  'авг' => "08",
        'сентябрь' => "09",  'сентября' => "09",  'сен' => "09",
        'октябрь'  => "10",  'октября'  => "10",  'окт' => "10",
        'ноябрь'   => "11",  'ноября'   => "11",  'ноя' => "11",
        'декабрь'  => "12",  'декабря'  => "12",  'дек' => "12",
    ];


    /**
     * @return string
     */
    public function getTitle(): string {

        return $this->getDomain($this->base_url);
    }


    /**
     * Загрузка списка вакансий
     * @param string $category_name
     * @return array
     * @throws \Zend_Config_Exception
     */
    public function loadVacanciesCategory(string $category_name): array {

        $categories    = $this->getCategoriesVacancies();
        $category_urls = $categories[$category_name] ?? [];
        $category_urls = $category_urls['url'] ?? [];

        return $category_urls ? $this->loadList($category_urls) : [];
    }


    /**
     * Загрузка списка вакансий
     * @param string $profession_name
     * @return array
     * @throws \Zend_Config_Exception
     */
    public function loadVacanciesProfessions(string $profession_name): array {

        $professions    = $this->getProfessionsVacancies();
        $profession_urls = $professions[$profession_name] ?? [];
        $profession_urls = $profession_urls['url'] ?? [];

        return $profession_urls ? $this->loadList($profession_urls) : [];
    }


    /**
     * Загрузка страниц резюме и вакансий
     * @param string $url
     * @return array
     */
    public function loadVacancies(string $url): array {

        $pages = $this->loadPages([$url]);
        return current($pages) ?: [];
    }


    /**
     * Загрузка списка резюме
     * @param string $category_name
     * @param array  $options
     * @return array
     * @throws \Zend_Config_Exception
     */
    public function loadResumeCategory(string $category_name, array $options): array {

        $search_status = $options['search_status'] ?? 'active';

        $categories    = $this->getCategoriesResume();
        $category_urls = $categories[$category_name] ?? [];
        $category_urls = $category_urls[$search_status] ?? [];

        return $category_urls ? $this->loadList($category_urls) : [];
    }


    /**
     * Загрузка списка резюме
     * @param string $profession_name
     * @param array  $options
     * @return array
     * @throws \Zend_Config_Exception
     */
    public function loadResumeProfessions(string $profession_name, array $options): array {

        $search_status = $options['search_status'] ?? 'active';

        $professions      = $this->getProfessionsResume();
        $professions_urls = $professions[$profession_name] ?? [];
        $professions_urls = $professions_urls[$search_status] ?? [];

        return $professions_urls ? $this->loadList($professions_urls) : [];
    }


    /**
     * Загрузка страниц резюме и вакансий
     * @param string $url
     * @return array
     */
    public function loadResume(string $url): array {

        $pages = $this->loadPages([$url]);
        return current($pages) ?: [];
    }


    /**
     * @param string $content
     * @param array  $options
     * @return array
     */
    public function parseVacanciesList(string $content, array $options = []): array {

        $dom    = new Crawler($content);
        $result = [
            'total_vacancies'    => null,
            'total_resume'       => null,
            'total_employers'    => null,
            'total_week_invites' => null,
            'content_correct'    => false,
            'vacancies_found'    => null,
            'vacancies'          => [],
        ];

        $header = $this->filter($dom, 'h1.bloko-header-section-3');
        $header = $header->count() > 0 ? $header : $this->filter($dom, '[data-qa="vacancies-total-found"]');

        if ($header->count() > 0) {
            $result['vacancies_found'] = preg_replace('~[^\d]~ui', '', trim($header->text()));
        }


        $not_found = $this->filter($dom, 'h1.bloko-header-section-3, h1[data-qa="title"]');
        if ($not_found->count() > 0) {
            $text = preg_replace("~( |&nbsp;|\h)~ui", ' ', $not_found->text());
            if (preg_match('~По\s+запросу\s+«[^»]+»\s+ничего\s+не\s+найдено~u', $text) ||
                preg_match('~По\s+запросу\s+ничего\s+не\s+найдено~u', $text)
            ) {
                $result['content_correct'] = true;
            }
        }


        $vacancies = $this->filter($dom, '.vacancy-serp-content .serp-item');

        if ($vacancies->count() > 0) {
            $result['content_correct'] = true;
            $result['vacancies']       = $vacancies->each(function (Crawler $vacancy_dom) {

                $title = $this->filter($vacancy_dom, '[data-qa="serp-item__title"]')->first();
                if ($title->getNode(0)->tagName != 'a') {
                    $title = $this->filter($vacancy_dom, '[data-page-analytics-event="vacancy_search_suitable_item"] a')->first();
                }

                $description   = $this->filter($vacancy_dom, '.g-user-content .bloko-text');
                $employer_link = $this->filter($vacancy_dom, '.bloko-link_kind-tertiary')->first();
                $labels        = $this->filter($vacancy_dom, '.search-result-label');
                $labels2       = $this->filter($vacancy_dom, '[class^="label--"]');
                $region        = $this->filter($vacancy_dom, 'div[data-qa="vacancy-serp__vacancy-address"]')->first();
                $money         = $this->filter($vacancy_dom, 'span[data-qa="vacancy-serp__vacancy-compensation"]')->first();

                $salary_min      = null;
                $salary_max      = null;
                $currency_origin = null;

                if ($money->count() > 0) {
                    $money_text = preg_replace("~( |&nbsp;|\h)~ui", '', $money->text());

                    if (preg_match('~(?<min>\d+)\s*–\s*(?<max>\d+)\s*(?<currency>.*)~ui', $money_text, $matches)) {
                        $salary_min      = $matches['min'];
                        $salary_max      = $matches['max'];
                        $currency_origin = trim($matches['currency']);

                    } else {
                        if (preg_match('~(?<min>\d+)\s*(?<currency>.*)~ui', $money_text, $matches)) {
                            $salary_min      = $matches['min'];
                            $salary_max      = $matches['min'];
                            $currency_origin = trim($matches['currency']);
                        }
                    }
                }

                $currency = $currency_origin ? $this->getCurrency($currency_origin) : null;

                $labels_title = [];
                if ($labels->count() > 0) {
                    $labels_title = $labels->each(function (Crawler $label_dom) {
                        $label_text = preg_replace("~( |&nbsp;|\h)~ui", ' ', $label_dom->text());
                        return trim($label_text);
                    });
                }
                if ($labels2->count() > 0) {
                    $labels_title = $labels2->each(function (Crawler $label_dom) {
                        $label_text = preg_replace("~( |&nbsp;|\h)~ui", ' ', $label_dom->text());
                        return trim($label_text);
                    });
                }


                $title_text = null;
                $url        = null;

                if ($title->count() > 0) {
                    $title_text = preg_replace("~( |&nbsp;|\h)~ui", ' ', $title->text());
                    $title_text = trim($title_text, "\t\n\r\0 .,-");

                    $url = $title->attr('href');

                    if ($url) {
                        $url = $this->getDomain($url) ? $url : "{$this->base_url}{$url}";

                        if (mb_strpos($url, '/click?') !== false) {
                            $btn_response = $this->filter($vacancy_dom, '.serp-item-controls a[data-qa="vacancy-serp__vacancy_response"]')->first();

                            if ($btn_response->count() > 0) {
                                $btn_response_href = $btn_response->attr('href');

                                if ($btn_response_href && preg_match('~vacancyId=(\d*)~', $btn_response_href, $matches)) {
                                    $url = "{$this->base_url}/vacancy/{$matches[1]}";
                                }
                            }

                        } else {
                            $url = preg_replace('~\?.*~ui', '', $url);
                        }
                        $url = trim($url);
                    }
                }

                $employer_url   = null;
                $employer_title = null;

                if ($employer_link->count() > 0) {
                    $employer_url = $employer_link->attr('href');
                    $employer_url = $this->getDomain($employer_url) ? $employer_url : "{$this->base_url}{$employer_url}";
                    $employer_url = preg_replace('~\?.*~ui', '', $employer_url);
                    $employer_url = trim($employer_url);

                    $employer_title = preg_replace("~( |&nbsp;|\h)~ui", ' ', $employer_link->text());
                }


                $region_text = '';
                if ($region->count() > 0) {
                    $region_text = explode(',', $region->text());
                    $region_text = trim($region_text[0], ', ');
                }

                return [
                    'title'           => $this->cleanText($title_text),
                    'url'             => $url,
                    'region'          => $this->cleanText($region_text),
                    'salary_min'      => $salary_min,
                    'salary_max'      => $salary_max,
                    'currency'        => $currency,
                    'currency_origin' => $this->cleanText($currency_origin),
                    'labels'          => $labels_title,
                    'description'     => $description->count() > 0 ? $this->cleanText($description->text()) : '',
                    'employer_title'  => $this->cleanText($employer_title),
                    'employer_url'    => $employer_url,
                ];
            });
        }


        if (empty($result['vacancies'])) {
            //$vacancies = $this->filter($dom, '.vacancy-serp-content .vacancy-search-item__card');
            $vacancies = $this->filter($dom, '.vacancy-search-item__card');

            if ($vacancies->count() > 0) {
                $result['content_correct'] = true;
                $result['vacancies']       = $vacancies->each(function (Crawler $vacancy_dom) {

                    $title = $this->filter($vacancy_dom, '.serp-item__title-link-wrapper a')->first();

                    $description   = $this->filter($vacancy_dom, '.g-user-content .bloko-text');
                    $employer_link = $this->filter($vacancy_dom, '[data-qa="vacancy-serp__vacancy-employer"]')->first();
                    $labels        = $this->filter($vacancy_dom, '.search-result-label');
                    $labels2       = $this->filter($vacancy_dom, '[class^="label--"]');
                    $region        = $this->filter($vacancy_dom, '[data-qa="vacancy-serp__vacancy-address"]')->first();
                    $money         = $this->filter($vacancy_dom, '[class^="wide-container--"] [class^="compensation-labels--"] [class^="fake-magritte-primary-text--"]')->first();

                    $salary_min      = null;
                    $salary_max      = null;
                    $currency_origin = null;
                    $labels_title    = [];

                    if ($money->count() > 0) {
                        $money_text = preg_replace("~( |&nbsp;|\h)~ui", '', $money->text());

                        if (preg_match('~(?<min>\d+)\s*–\s*(?<max>\d+)\s*(?<currency>[^а-яА-Я\d]+)~ui', $money_text, $matches)) {
                            $salary_min      = $matches['min'];
                            $salary_max      = $matches['max'];
                            $currency_origin = trim($matches['currency']);

                        } else {
                            if (preg_match('~от[^\w\d]*(?<min>\d+)\s*(?<currency>[^а-яА-Я\d]*)~ui', $money_text, $matches)) {
                                $salary_min      = $matches['min'];
                                $currency_origin = trim($matches['currency']);

                            } elseif (preg_match('~до[^\w\d]*(?<max>\d+)\s*(?<currency>[^а-яА-Я\d]*)~ui', $money_text, $matches)) {
                                $salary_max      = $matches['max'];
                                $currency_origin = trim($matches['currency']);
                            }
                        }

                        if (mb_strpos($money_text, 'довычетаналогов') !== false) {
                            $labels_title[] = 'До вычета налогов';
                        }
                    }

                    $currency = $currency_origin ? $this->getCurrency($currency_origin) : null;

                    if ($labels->count() > 0) {
                        $labels->each(function (Crawler $label_dom) use (&$labels_title) {
                            $label_text = preg_replace("~( |&nbsp;|\h)~ui", ' ', $label_dom->text());
                            $labels_title[] = trim($label_text);
                        });
                    }
                    if ($labels2->count() > 0) {
                        $labels2->each(function (Crawler $label_dom) use (&$labels_title) {
                            $label_text = preg_replace("~( |&nbsp;|\h)~ui", ' ', $label_dom->text());
                            $labels_title[] = trim($label_text);
                        });
                    }


                    $title_text = null;
                    $url        = null;

                    if ($title->count() > 0) {
                        $title_text = preg_replace("~( |&nbsp;|\h)~ui", ' ', $title->text());
                        $title_text = trim($title_text, "\t\n\r\0 .,-");

                        $url = $title->attr('href');

                        if ($url) {
                            $url = $this->getDomain($url) ? $url : "{$this->base_url}{$url}";

                            if (mb_strpos($url, '/click?') !== false) {
                                $url          = '';
                                $btn_response = $this->filter($vacancy_dom, 'a[data-qa="vacancy-serp__vacancy_response"]')->first();

                                if ($btn_response->count() > 0) {
                                    $btn_response_href = $btn_response->attr('href');

                                    if ($btn_response_href && preg_match('~vacancyId=(\d*)~', $btn_response_href, $matches)) {
                                        $url = "{$this->base_url}/vacancy/{$matches[1]}";
                                    }
                                }

                            } else {
                                $url = preg_replace('~\?.*~ui', '', $url);
                            }
                            $url = trim($url);
                        }
                    }

                    $employer_url   = null;
                    $employer_title = null;

                    if ($employer_link->count() > 0) {
                        $employer_url = $employer_link->attr('href');
                        $employer_url = $this->getDomain($employer_url) ? $employer_url : "{$this->base_url}{$employer_url}";
                        $employer_url = preg_replace('~\?.*~ui', '', $employer_url);
                        $employer_url = trim($employer_url);

                        $employer_title = preg_replace("~( |&nbsp;|\h)~ui", ' ', $employer_link->text());
                    }


                    $region_text = '';
                    if ($region->count() > 0) {
                        $region_text = explode(',', $region->text());
                        $region_text = trim($region_text[0], ', ');
                    }

                    return [
                        'title'           => $this->cleanText($title_text),
                        'url'             => $url,
                        'region'          => $this->cleanText($region_text),
                        'salary_min'      => $salary_min,
                        'salary_max'      => $salary_max,
                        'currency'        => $currency,
                        'currency_origin' => $this->cleanText($currency_origin),
                        'labels'          => array_unique($labels_title),
                        'description'     => $description->count() > 0 ? $this->cleanText($description->text()) : '',
                        'employer_title'  => $this->cleanText($employer_title),
                        'employer_url'    => $employer_url,
                    ];
                });
            }
        }


        if (empty($result['vacancies'])) {
            //$vacancies = $this->filter($dom, '.vacancy-serp-content [data-qa~="vacancy-serp__vacancy"]');
            $vacancies = $this->filter($dom, '[data-qa~="vacancy-serp__vacancy"]');

            if ($vacancies->count() > 0) {
                $result['content_correct'] = true;
                $result['vacancies']       = $vacancies->each(function (Crawler $vacancy_dom) {

                    $title         = $this->filter($vacancy_dom, 'a[data-qa~="serp-item__title"]')->first();
                    $description   = $this->filter($vacancy_dom, '[data-qa~="vacancy-serp__vacancy_snippet_responsibility"]');
                    $employer_link = $this->filter($vacancy_dom, '[data-qa="vacancy-serp__vacancy-employer"]')->first();
                    $labels        = $this->filter($vacancy_dom, '[class^="wide-container-magritte--"] [class^="compensation-labels--"] [class^="magritte-tag__label___"]');
                    $region        = $this->filter($vacancy_dom, '[data-qa="vacancy-serp__vacancy-address"]')->first();
                    $money         = $this->filter($vacancy_dom, '[class^="wide-container"] [class^="compensation-labels--"] [class*="magritte-text_style-primary___"]')->first();

                    $salary_min      = null;
                    $salary_max      = null;
                    $currency_origin = null;
                    $labels_title    = [];

                    if ($money->count() > 0) {
                        $money_text = preg_replace("~( |&nbsp;|\h)~ui", '', $money->text());

                        if (preg_match('~(?<min>\d+)\s*–\s*(?<max>\d+)\s*(?<currency>[^а-яА-Я\d]+)~ui', $money_text, $matches)) {
                            $salary_min      = $matches['min'];
                            $salary_max      = $matches['max'];
                            $currency_origin = trim($matches['currency']);

                        } else {
                            if (preg_match('~от[^\w\d]*(?<min>\d+)\s*(?<currency>[^а-яА-Я\d]*)~ui', $money_text, $matches)) {
                                $salary_min      = $matches['min'];
                                $currency_origin = trim($matches['currency']);

                            } elseif (preg_match('~до[^\w\d]*(?<max>\d+)\s*(?<currency>[^а-яА-Я\d]*)~ui', $money_text, $matches)) {
                                $salary_max      = $matches['max'];
                                $currency_origin = trim($matches['currency']);
                            }
                        }

                        if (mb_strpos($money_text, 'довычетаналогов') !== false) {
                            $labels_title[] = 'До вычета налогов';
                        }
                    }

                    $currency = $currency_origin ? $this->getCurrency($currency_origin) : null;

                    if ($labels->count() > 0) {
                        $labels->each(function (Crawler $label_dom) use (&$labels_title) {
                            $label_text = preg_replace("~( |&nbsp;|\h)~ui", ' ', $label_dom->text());
                            $labels_title[] = trim($label_text);
                        });
                    }


                    $title_text = null;
                    $url        = null;

                    if ($title->count() > 0) {
                        $title_text = preg_replace("~( |&nbsp;|\h)~ui", ' ', $title->text());
                        $title_text = trim($title_text, "\t\n\r\0 .,-");

                        $url = $title->attr('href');

                        if ($url) {
                            $url = $this->getDomain($url) ? $url : "{$this->base_url}{$url}";

                            if (mb_strpos($url, '/click?') !== false) {
                                $url          = '';
                                $btn_response = $this->filter($vacancy_dom, 'a[data-qa="vacancy-serp__vacancy_response"]')->first();

                                if ($btn_response->count() > 0) {
                                    $btn_response_href = $btn_response->attr('href');

                                    if ($btn_response_href && preg_match('~vacancyId=(\d*)~', $btn_response_href, $matches)) {
                                        $url = "{$this->base_url}/vacancy/{$matches[1]}";
                                    }
                                }

                            } else {
                                $url = preg_replace('~\?.*~ui', '', $url);
                            }
                            $url = trim($url);
                        }
                    }

                    $employer_url   = null;
                    $employer_title = null;

                    if ($employer_link->count() > 0) {
                        $employer_url = $employer_link->attr('href');
                        $employer_url = $this->getDomain($employer_url) ? $employer_url : "{$this->base_url}{$employer_url}";
                        $employer_url = preg_replace('~\?.*~ui', '', $employer_url);
                        $employer_url = trim($employer_url);

                        $employer_title = preg_replace("~( |&nbsp;|\h)~ui", ' ', $employer_link->text());
                    }


                    $region_text = '';
                    if ($region->count() > 0) {
                        $region_text = explode(',', $region->text());
                        $region_text = trim($region_text[0], ', ');
                    }

                    return [
                        'title'           => $this->cleanText($title_text),
                        'url'             => $url,
                        'region'          => $this->cleanText($region_text),
                        'salary_min'      => $salary_min,
                        'salary_max'      => $salary_max,
                        'currency'        => $currency,
                        'currency_origin' => $this->cleanText($currency_origin),
                        'labels'          => array_unique($labels_title),
                        'description'     => $description->count() > 0 ? $this->cleanText($description->text()) : '',
                        'employer_title'  => $this->cleanText($employer_title),
                        'employer_url'    => $employer_url,
                    ];
                });
            }
        }


        if (empty($result['vacancies'])) {
            $template = $this->filter($dom, 'template#HH-Lux-InitialState');

            if ($template->count() > 0) {
                $content_json = $template->html();

                $vacancies = @json_decode($content_json, true);


                if ($vacancies &&
                    ! empty($vacancies['vacancySearchResult'])
                ) {
                    $result['content_correct'] = true;
                }

                if ($vacancies &&
                    ! empty($vacancies['vacancySearchResult']) &&
                    ! empty($vacancies['vacancySearchResult']['vacancies']) &&
                    is_array($vacancies['vacancySearchResult']['vacancies'])
                ) {
                    foreach ($vacancies['vacancySearchResult']['vacancies'] as $vacancy) {

                        $labels_title = [];

                        if ( ! empty($vacancy['@workSchedule']) && $vacancy['@workSchedule'] == 'fullDay') {
                            $labels_title[] = 'Полный рабочий день';
                        }
                        if ( ! empty($vacancy['workExperience'])) {
                            switch ($vacancy['workExperience']) {
                                case 'between1And3': $labels_title[] = 'Опыт 1-3 лет'; break;
                                case 'between3And6': $labels_title[] = 'Опыт 3-6 лет'; break;
                            }
                        }


                        $currency_origin = ! empty($vacancy['compensation']) && ! empty($vacancy['compensation']['currencyCode'])
                            ? $vacancy['compensation']['currencyCode']
                            : null;

                        $currency = $currency_origin
                            ? $this->getCurrency($currency_origin)
                            : null;

                        $result['vacancies'][] = [
                            'title'           => $vacancy['name'] ?? null,
                            'url'             => ! empty($vacancy['links']) && ! empty($vacancy['links']['desktop']) ? $vacancy['links']['desktop'] : null,
                            'region'          => ! empty($vacancy['area']) && ! empty($vacancy['area']['name']) ? $vacancy['area']['name'] : null,
                            'salary_min'      => ! empty($vacancy['compensation']) && ! empty($vacancy['compensation']['from']) ? $vacancy['compensation']['from'] : null,
                            'salary_max'      => ! empty($vacancy['compensation']) && ! empty($vacancy['compensation']['to']) ? $vacancy['compensation']['to'] : null,
                            'currency'        => $currency,
                            'currency_origin' => $currency_origin,
                            'labels'          => array_unique($labels_title),
                            'description'     => '',
                            'employer_title'  => ! empty($vacancy['company']) && ! empty($vacancy['company']['name']) ? $vacancy['company']['name'] : null,
                            'employer_url'    => ! empty($vacancy['company']) && ! empty($vacancy['company']['id']) ? "{$this->base_url}/employer/{$vacancy['company']['id']}" : null,
                            'employer_site'   => ! empty($vacancy['company']) && ! empty($vacancy['company']['companySiteUrl']) ? $vacancy['company']['companySiteUrl'] : null,
                        ];
                    }
                }
            }
        }


        $stat = $this->filter($dom, '[data-qa="footer"] .bloko-columns-wrapper .bloko-column p')->first();

        if ($stat->count() > 0) {
            preg_match('~(\d*)\s+ваканси~ui', $stat->text(), $matches);

            if ( ! empty($matches[1])) {
                $result['total_vacancies'] = $matches[1];
            }

            preg_match('~(\d*)\s+компани~ui', $stat->text(), $matches);
            if ( ! empty($matches[1])) {
                $result['total_employers'] = $matches[1];
            }

            preg_match('~(\d*)\s+резюме~ui', $stat->text(), $matches);
            if ( ! empty($matches[1])) {
                $result['total_resume'] = $matches[1];
            }

            preg_match('~(\d*)\s+приглашени~ui', $stat->text(), $matches);
            if ( ! empty($matches[1])) {
                $result['total_week_invites'] = $matches[1];
            }
        }

        return $result;
    }


    /**
     * Обработка страниц резюме
     * @param string $content
     * @param array  $options
     * @return array
     */
    public function parseResumeList(string $content, array $options = []): array {

        $dom    = new Crawler($content);
        $result = [
            'total_vacancies'    => null,
            'total_resume'       => null,
            'total_employers'    => null,
            'total_week_invites' => null,
            'content_correct'    => false,
            'people_found'       => null,
            'resume_found'       => null,
            'resume'             => [],
        ];

        $header = $this->filter($dom, 'h1.bloko-header-section-3');
        $header = $header->count() > 0 ? $header : $this->filter($dom, '[data-qa="vacancies-total-found"]');

        if ($header->count() > 0) {
            $header_text = preg_replace("~( |&nbsp;|\h)~ui", '', $header->text());
            preg_match('~(\d+)\s*резюм~ui', $header_text, $matches);
            if ( ! empty($matches[1])) {
                $result['resume_found'] = $matches[1];
            }

            preg_match('~(\d+)\s*соискате~ui', $header_text, $matches);
            if ( ! empty($matches[1])) {
                $result['people_found'] = $matches[1];
            }
        }

        $subtitle = $this->filter($dom, '.registration-in-serp-messages [data-qa="resumes-total-found"]');

        if ($subtitle->count() > 0) {
            $subtitle = $subtitle->first();
            $subtitle_text = preg_replace("~( |&nbsp;)~ui", '', $subtitle->text());
            preg_match('~покажем\s*ещё\s*(\d+)~ui', $subtitle_text, $matches);
            if ( ! empty($matches[1])) {
                $result['resume_found'] = empty($result['resume_found']) ? $matches[1] : $result['resume_found'] + $matches[1];
            }
        }


        $not_found = $this->filter($dom, '.bloko-gap.bloko-gap_top.bloko-gap_bottom');
        if ($not_found->count() > 0) {
            $text = preg_replace("~( |&nbsp;|\h)~ui", ' ', $not_found->text());
            if (preg_match('~Попробуйте\s+другие\s+варианты\s+поискового\s+запроса\s+или\s+уберите\s+фильтры~u', $text)) {
                $result['content_correct'] = true;
            }
        }

        $resume_items = $this->filter($dom, '[data-qa=resume-serp__results-search] [data-qa=resume-serp__resume]');


        if ($resume_items->count() > 0) {
            $result['content_correct'] = true;
            $result['resume']          = $resume_items->each(function (Crawler $resume_dom) {

                $title                = $this->filter($resume_dom, '[data-qa="serp-item__title"]')->first();
                $age                  = $this->filter($resume_dom, '[data-qa="resume-serp__resume-age"]')->first();
                $money                = $this->filter($resume_dom, '.bloko-text.bloko-text_large.bloko-text_strong')->first();
                $labels               = $this->filter($resume_dom, '.search-result-label');
                $labels2              = $this->filter($resume_dom, '[class^="label--"]');
                $experience           = $this->filter($resume_dom, '[data-qa="resume-serp__resume-excpirience-sum"]')->first();
                $last_profession      = $this->filter($resume_dom, '[data-qa="resume-serp_resume-item-content"] [data-qa="last-experience-link"]')->first();
                $last_employer        = $this->filter($resume_dom, '[data-qa="resume-serp_resume-item-content"] .bloko-text_strong')->first();
                $last_employer_period = $this->filter($resume_dom, '[data-qa="resume-serp_resume-item-content"] span')->last();
                $date_last_update     = $this->filter($resume_dom, 'span .bloko-text')->first();

                $salary          = null;
                $currency_origin = null;

                if ($money->count() > 0) {
                    $money_text = preg_replace("~( |&nbsp;|\h)~ui", '', $money->text());

                    if (preg_match('~(?<solary>\d+)\s*(?<currency>.*)~ui', $money_text, $matches)) {
                        $salary          = $matches['solary'];
                        $currency_origin = trim($matches['currency']);
                    }
                }


                $currency = $currency_origin ? $this->getCurrency($currency_origin) : null;

                $age_text = null;
                if ($age->count() > 0) {
                    preg_match('~(\d*)~ui', $age->text(), $matches);
                    $age_text = $matches[1];
                }

                $labels_title = [];
                if ($labels->count() > 0) {
                    $labels_title = $labels->each(function (Crawler $label_dom) {
                        $label_text = preg_replace("~( |&nbsp;|\h)~ui", ' ', $label_dom->text());
                        return trim($label_text);
                    });
                }
                if ($labels2->count() > 0) {
                    $labels_title = $labels2->each(function (Crawler $label_dom) {
                        $label_text = preg_replace("~( |&nbsp;|\h)~ui", ' ', $label_dom->text());
                        return trim($label_text);
                    });
                }

                $experience_text = $experience->count() > 0
                    ? preg_replace("~( |&nbsp;|\h)~ui", ' ', $experience->text())
                    : '';


                if ($date_last_update->count() > 0) {
                    $date_last_update_text = preg_replace("~Обновлено~ui", '', $date_last_update->text());
                    $date_last_update_text = preg_replace("~( |&nbsp;|\h)~ui", ' ', $date_last_update_text);
                } else {
                    $date_last_update_text = '';
                }


                $date_last_update = null;
                if (preg_match('~(?<day>\d+)\s*(?<month_ru>\w+)*\s*в\s*(?<hour>\d+):(?<minute>\d+)~ui', $date_last_update_text, $match)) {
                    if (isset($this->months_ru[$match['month_ru']])) {
                        $last_update           = [];
                        $last_update['month']  = $this->months_ru[$match['month_ru']];
                        $last_update['day']    = str_pad($match['day'], 2, "0", STR_PAD_LEFT);
                        $last_update['hour']   = $match['hour'];
                        $last_update['minute'] = $match['minute'];
                        $last_update['year']   = date('Y');

                        if ( ! empty($options['date_created'])) {
                            $last_update['year'] = date('Y', strtotime($options['date_created']));
                        }

                        $date_last_update = "{$last_update['year']}-{$last_update['month']}-{$last_update['day']} {$last_update['hour']}:{$last_update['minute']}:00";
                    }


                } elseif (preg_match('~(?<day>\d+)\s*(?<month_ru>\w+)*\s*(?<year>\d+)~ui', $date_last_update_text, $match)) {
                    $last_update          = [];
                    $last_update['month'] = $this->months_ru[$match['month_ru']];
                    $last_update['day']   = $match['day'];
                    $last_update['year']  = $match['year'];

                    $date_last_update = "{$last_update['year']}-{$last_update['month']}-{$last_update['day']} 00:00:00";
                }

                $experience_year  = null;
                $experience_month = null;
                if (preg_match('~(?<year>\d+)\s*(лет|год)\w*\s*(?<month>\d+)\s*мес~ui', $experience_text, $match)) {
                    $experience_year  = $match['year'];
                    $experience_month = $match['month'];

                } elseif (preg_match('~(?<year>\d+)\s*(лет|год)~ui', $experience_text, $match)) {
                    $experience_year  = $match['year'];

                } elseif (preg_match('~(?<month>\d+)\s*мес~ui', $experience_text, $match)) {
                    $experience_month = $match['month'];
                }

                $title_text = null;
                $url        = null;

                if ($title->count() > 0) {
                    $title_text = preg_replace("~( |&nbsp;|\h)~ui", ' ', $title->text());
                    $title_text = trim($title_text, "\t\n\r\0 .,-");

                    $url = $title->attr('href');
                    $url = $this->getDomain($url) ? $url : "{$this->base_url}{$url}";
                    $url = preg_replace('~\?.*~ui', '', $url);
                }


                $employer_title        = $last_employer->count() > 0   ? trim(preg_replace("~( |&nbsp;|\h)~ui", ' ', $last_employer->text())) : null;
                $last_profession_title = $last_profession->count() > 0 ? trim(preg_replace("~( |&nbsp;|\h)~ui", ' ', $last_profession->text())) : null;



                return [
                    'title'                   => $this->cleanText($title_text),
                    'url'                     => $url,
                    'age'                     => $age_text,
                    'experience'              => $this->cleanText($experience_text),
                    'experience_year'         => $experience_year,
                    'experience_month'        => $experience_month,
                    'labels'                  => $labels_title,
                    'salary'                  => $salary,
                    'currency'                => $currency,
                    'currency_origin'         => $this->cleanText($currency_origin),
                    'last_profession'         => $this->cleanText($last_profession_title),
                    'last_employer_title'     => $this->cleanText($employer_title),
                    'last_employer_period'    => $last_employer_period->count() > 0 ? $this->cleanText($last_employer_period->text()) : null,
                    'date_last_update_origin' => $this->cleanText($date_last_update_text),
                    'date_last_update'        => $date_last_update,
                ];
            });

        }


        if (empty($result['resume'])) {
            $template = $this->filter($dom, 'template#HH-Lux-InitialState');

            if ($template->count() > 0) {
                $content_json = $template->html();

                $vacancies = @json_decode($content_json, true);


                if ($vacancies &&
                    ! empty($vacancies['resumeSearchResult'])
                ) {
                    $result['content_correct'] = true;
                }

                if ($vacancies &&
                    ! empty($vacancies['resumeSearchResult']) &&
                    ! empty($vacancies['resumeSearchResult']['resumes']) &&
                    is_array($vacancies['resumeSearchResult']['resumes'])
                ) {
                    foreach ($vacancies['resumeSearchResult']['resumes'] as $resume) {

                        $currency_origin = ! empty($resume['salary']) && ! empty($resume['salary'][0]) && ! empty($resume['salary'][0]['currency'])
                            ? $resume['salary'][0]['currency']
                            : null;

                        $currency = $currency_origin
                            ? $this->getCurrency($currency_origin)
                            : null;

                        $salary = ! empty($resume['salary']) && ! empty($resume['salary'][0]) && ! empty($resume['salary'][0]['amount'])
                            ? $resume['salary'][0]['amount']
                            : null;

                        $url = ! empty($resume['_attributes']) && ! empty($resume['_attributes']['hash'])
                            ? "{$this->base_url}/resume/{$resume['_attributes']['hash']}"
                            : null;

                        $age = ! empty($resume['age']) && ! empty($resume['age'][0]) && ! empty($resume['age'][0]['string'])
                            ? $resume['age'][0]['string']
                            : null;

                        $labels_title = [];

                        if ( ! empty($resume['keySkills'])) {
                            foreach ($resume['keySkills'] as $item) {
                                if ( ! empty($item['string'])) {
                                    $labels_title[] = $item['string'];
                                }
                            }
                        }

                        $count_years  = 0;
                        $count_months = 0;
                        $count_days   = 0;

                        $last_profession      = null;
                        $last_employer_title  = null;
                        $last_employer_period = null;

                        if ( ! empty($resume['shortExperience'])) {
                            foreach ($resume['shortExperience'] as $experience) {
                                if (empty($last_employer_title) && ! empty($experience['companyName'])) {
                                    $last_employer_title = $experience['companyName'];
                                }
                                if (empty($last_profession) && ! empty($experience['position'])) {
                                    $last_profession = $experience['position'];
                                }
                                if (empty($last_employer_period) && ! empty($experience['startDate'])) {
                                    $last_employer_period = "{$experience['startDate']} - " . ($experience['endDate'] ?? '');
                                }

                                if ( ! empty($experience['startDate'])) {

                                    $date_start = new \DateTime($experience['startDate']);
                                    $date_end   = new \DateTime($experience['endDate'] ?? 'now');

                                    $interval = $date_start->diff($date_end);

                                    if ($interval) {
                                        $count_years  += $interval->y;
                                        $count_months += $interval->m;
                                        $count_days   += $interval->d;
                                    }
                                }
                            }

                            if ($count_years > 0) {
                                $count_days += $count_years * 365;
                            }
                            if ($count_months > 0) {
                                $count_days += $count_months * 30;
                            }
                        }

                        $experience_year  = $count_days > 0 ? floor($count_days / 365) : 0;
                        $experience_month = $count_days > 0 ? floor(($count_days - ($experience_year * 365)) / 30) : 0;

                        $date_last_update = ! empty($resume['lastChangeTimeDetails']) && ! empty($resume['lastChangeTimeDetails'][0]) && ! empty($resume['lastChangeTimeDetails'][0]['date'])
                            ? date('Y-m-d H:i:s', ($resume['lastChangeTimeDetails'][0]['date'] / 1000))
                            : null;

                        $result['resume'][] = [
                            'title'                   => ! empty($resume['title']) && ! empty($resume['title'][0]) && ! empty($resume['title'][0]['string']) ? $resume['title'][0]['string'] : null,
                            'url'                     => $url,
                            'age'                     => $age,
                            'experience'              => '',
                            'experience_year'         => $experience_year,
                            'experience_month'        => $experience_month,
                            'labels'                  => $labels_title,
                            'salary'                  => $salary,
                            'currency'                => $currency,
                            'currency_origin'         => $currency_origin,
                            'last_profession'         => $last_profession,
                            'last_employer_title'     => $last_employer_title,
                            'last_employer_period'    => $last_employer_period,
                            'date_last_update_origin' => $date_last_update,
                            'date_last_update'        => $date_last_update,
                        ];
                    }
                }
            }
        }


        $stat = $this->filter($dom, '[data-qa="footer"] .bloko-columns-wrapper .bloko-column p')->first();

        if ($stat->count() > 0) {
            preg_match('~(\d*)\s+ваканси~ui', $stat->text(), $matches);

            if ( ! empty($matches[1])) {
                $result['total_vacancies'] = $matches[1];
            }

            preg_match('~(\d*)\s+резюме~ui', $stat->text(), $matches);
            if ( ! empty($matches[1])) {
                $result['total_resume'] = $matches[1];
            }

            preg_match('~(\d*)\s+компани~ui', $stat->text(), $matches);
            if ( ! empty($matches[1])) {
                $result['total_employers'] = $matches[1];
            }

            preg_match('~(\d*)\s+приглашени~ui', $stat->text(), $matches);
            if ( ! empty($matches[1])) {
                $result['total_week_invites'] = $matches[1];
            }
        }

        return $result;
    }


    /**
     * @param string $content
     * @return array
     */
    public function parseVacancy(string $content): array {

        // TODO Доделать
    }


    /**
     * @param string $content
     * Обработка страниц резюме
     * @return array
     */
    public function parseResume(string $content): array {

        // TODO Доделать
    }


    /**
     * Получение категорий по вакансиям
     * @return array
     * @throws \Zend_Config_Exception
     */
    private function getCategoriesVacancies(): array {

        $categories = parent::getCategories();

        $url = $this->base_url . "/search/vacancy?area={$this->region_id}&search_field=name&specialization=[SPEC]&text=&page=[PAGE]&hhtmFrom=vacancy_search_list&items_on_page=100";

        if ( ! empty($categories['auto']))          $categories['auto']['url'][0]          = str_replace('[SPEC]', 7, $url);
        if ( ! empty($categories['admin']))         $categories['admin']['url'][0]         = str_replace('[SPEC]', 4, $url);
        if ( ! empty($categories['bank']))          $categories['bank']['url'][0]          = str_replace('[SPEC]', 5, $url);
        if ( ! empty($categories['security']))      $categories['security']['url'][0]      = str_replace('[SPEC]', 8, $url);
        if ( ! empty($categories['bookkeeping']))   $categories['bookkeeping']['url'][0]   = str_replace('[SPEC]', 2, $url);
        if ( ! empty($categories['management']))    $categories['management']['url'][0]    = str_replace('[SPEC]', 9, $url);
        if ( ! empty($categories['gov']))           $categories['gov']['url'][0]           = str_replace('[SPEC]', 16, $url);
        if ( ! empty($categories['mining']))        $categories['mining']['url'][0]        = str_replace('[SPEC]', 10, $url);
        if ( ! empty($categories['home']))          $categories['home']['url'][0]          = str_replace('[SPEC]', 27, $url);
        if ( ! empty($categories['procurement']))   $categories['procurement']['url'][0]   = str_replace('[SPEC]', 26, $url);
        if ( ! empty($categories['service']))       $categories['service']['url'][0]       = str_replace('[SPEC]', 25, $url);
        if ( ! empty($categories['it']))            $categories['it']['url'][0]            = str_replace('[SPEC]', 1, $url);
        if ( ! empty($categories['relax']))         $categories['relax']['url'][0]         = str_replace('[SPEC]', 11, $url);
        if ( ! empty($categories['consult']))       $categories['consult']['url'][0]       = str_replace('[SPEC]', 12, $url);
        if ( ! empty($categories['marketing']))     $categories['marketing']['url'][0]     = str_replace('[SPEC]', 3, $url);
        if ( ! empty($categories['medicine']))      $categories['medicine']['url'][0]      = str_replace('[SPEC]', 13, $url);
        if ( ! empty($categories['science']))       $categories['science']['url'][0]       = str_replace('[SPEC]', 14, $url);
        if ( ! empty($categories['begin']))         $categories['begin']['url'][0]         = str_replace('[SPEC]', 15, $url);
        if ( ! empty($categories['sales']))         $categories['sales']['url'][0]         = str_replace('[SPEC]', 17, $url);
        if ( ! empty($categories['production']))    $categories['production']['url'][0]    = str_replace('[SPEC]', 18, $url);
        if ( ! empty($categories['working']))       $categories['working']['url'][0]       = str_replace('[SPEC]', 29, $url);
        if ( ! empty($categories['beauty_health'])) $categories['beauty_health']['url'][0] = str_replace('[SPEC]', 24, $url);
        if ( ! empty($categories['insurance']))     $categories['insurance']['url'][0]     = str_replace('[SPEC]', 19, $url);
        if ( ! empty($categories['building']))      $categories['building']['url'][0]      = str_replace('[SPEC]', 20, $url);
        if ( ! empty($categories['transport']))     $categories['transport']['url'][0]     = str_replace('[SPEC]', 21, $url);
        if ( ! empty($categories['tourism']))       $categories['tourism']['url'][0]       = str_replace('[SPEC]', 22, $url);
        if ( ! empty($categories['hr']))            $categories['hr']['url'][0]            = str_replace('[SPEC]', 6, $url);
        if ( ! empty($categories['lawyers']))       $categories['lawyers']['url'][0]       = str_replace('[SPEC]', 23, $url);

        return $categories;
    }


    /**
     * Получение категорий по резюме
     * @return array
     * @throws \Zend_Config_Exception
     */
    private function getCategoriesResume(): array {

        $categories = parent::getCategories();


        if ( ! empty($categories['auto']))          $categories['auto']['active'][0]          = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=4&professional_role=5&professional_role=62&professional_role=70&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['admin']))         $categories['admin']['active'][0]         = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=8&professional_role=33&professional_role=58&professional_role=76&professional_role=84&professional_role=88&professional_role=93&professional_role=110&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['bank']))          $categories['bank']['active'][0]          = '';
        if ( ! empty($categories['security']))      $categories['security']['active'][0]      = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=22&professional_role=90&professional_role=95&professional_role=116&professional_role=120&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['bookkeeping']))   $categories['bookkeeping']['active'][0]   = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=16&professional_role=154&professional_role=18&professional_role=50&professional_role=158&professional_role=57&professional_role=155&professional_role=147&professional_role=134&professional_role=135&professional_role=136&professional_role=137&professional_role=142&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['management']))    $categories['management']['active'][0]    = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=26&professional_role=36&professional_role=37&professional_role=38&professional_role=166&professional_role=53&professional_role=80&professional_role=87&professional_role=157&professional_role=172&professional_role=170&professional_role=171&professional_role=161&professional_role=125&professional_role=135&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['gov']))           $categories['gov']['active'][0]           = '';
        if ( ! empty($categories['mining']))        $categories['mining']['active'][0]        = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=27&professional_role=28&professional_role=168&professional_role=63&professional_role=79&professional_role=82&professional_role=49&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['home']))          $categories['home']['active'][0]          = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=8&professional_role=21&professional_role=23&professional_role=32&professional_role=58&professional_role=89&professional_role=90&professional_role=130&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['procurement']))   $categories['procurement']['active'][0]   = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=66&professional_role=119&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['service']))       $categories['service']['active'][0]       = '';
        if ( ! empty($categories['it']))            $categories['it']['active'][0]            = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=156&professional_role=160&professional_role=10&professional_role=12&professional_role=150&professional_role=25&professional_role=165&professional_role=34&professional_role=36&professional_role=73&professional_role=155&professional_role=96&professional_role=164&professional_role=104&professional_role=157&professional_role=107&professional_role=112&professional_role=113&professional_role=148&professional_role=114&professional_role=116&professional_role=121&professional_role=124&professional_role=125&professional_role=126&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['relax']))         $categories['relax']['active'][0]         = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=12&professional_role=13&professional_role=20&professional_role=25&professional_role=34&professional_role=41&professional_role=55&professional_role=98&professional_role=103&professional_role=139&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['consult']))       $categories['consult']['active'][0]       = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=10&professional_role=150&professional_role=75&professional_role=107&professional_role=134&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['marketing']))     $categories['marketing']['active'][0]     = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=1&professional_role=2&professional_role=3&professional_role=10&professional_role=12&professional_role=34&professional_role=37&professional_role=55&professional_role=163&professional_role=68&professional_role=70&professional_role=71&professional_role=99&professional_role=170&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['medicine']))      $categories['medicine']['active'][0]      = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=8&professional_role=15&professional_role=19&professional_role=24&professional_role=29&professional_role=42&professional_role=168&professional_role=64&professional_role=65&professional_role=79&professional_role=151&professional_role=133&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['science']))       $categories['science']['active'][0]       = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=17&professional_role=23&professional_role=168&professional_role=167&professional_role=79&professional_role=101&professional_role=132&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['begin']))         $categories['begin']['active'][0]         = '';
        if ( ! empty($categories['sales']))         $categories['sales']['active'][0]         = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=6&professional_role=10&professional_role=154&professional_role=51&professional_role=53&professional_role=54&professional_role=57&professional_role=70&professional_role=71&professional_role=83&professional_role=97&professional_role=105&professional_role=106&professional_role=161&professional_role=151&professional_role=121&professional_role=122&professional_role=129&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['sales']))         $categories['sales']['active'][1]         = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=9&professional_role=35&professional_role=77&professional_role=97&professional_role=99&professional_role=123&professional_role=127&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['production']))    $categories['production']['active'][0]    = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=174&professional_role=44&professional_role=45&professional_role=46&professional_role=48&professional_role=169&professional_role=144&professional_role=149&professional_role=168&professional_role=162&professional_role=63&professional_role=152&professional_role=173&professional_role=79&professional_role=80&professional_role=82&professional_role=85&professional_role=86&professional_role=109&professional_role=111&professional_role=115&professional_role=151&professional_role=49&professional_role=128&professional_role=141&professional_role=143&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['production']))    $categories['production']['active'][1]    = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=7&professional_role=19&professional_role=43&professional_role=63&professional_role=111&professional_role=49&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['working']))       $categories['working']['active'][0]       = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=5&professional_role=21&professional_role=31&professional_role=52&professional_role=59&professional_role=63&professional_role=173&professional_role=78&professional_role=85&professional_role=86&professional_role=102&professional_role=109&professional_role=111&professional_role=115&professional_role=128&professional_role=131&professional_role=143&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['beauty_health'])) $categories['beauty_health']['active'][0] = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=8&professional_role=56&professional_role=60&professional_role=61&professional_role=70&professional_role=92&professional_role=138&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['insurance']))     $categories['insurance']['active'][0]     = $this->base_url . "/search/resume?area={$this->region_id}&clusters=true&currency_code=BYR&exp_period=all_time&job_search_status=active_search&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&professional_role=11&professional_role=91&professional_role=122&text=&items_on_page=100&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['building']))      $categories['building']['active'][0]      = $this->base_url . "/search/resume?area={$this->region_id}&clusters=true&currency_code=BYR&exp_period=all_time&job_search_status=active_search&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&professional_role=6&professional_role=14&professional_role=154&professional_role=27&professional_role=30&professional_role=34&professional_role=47&professional_role=45&professional_role=46&professional_role=48&professional_role=59&professional_role=63&professional_role=78&professional_role=100&professional_role=102&professional_role=107&professional_role=108&professional_role=109&professional_role=115&professional_role=143&text=&items_on_page=100&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['transport']))     $categories['transport']['active'][0]     = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=159&professional_role=21&professional_role=31&professional_role=39&professional_role=52&professional_role=58&professional_role=63&professional_role=67&professional_role=81&professional_role=172&professional_role=131&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['tourism']))       $categories['tourism']['active'][0]       = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=8&professional_role=72&professional_role=74&professional_role=76&professional_role=89&professional_role=94&professional_role=130&professional_role=140&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['hr']))            $categories['hr']['active'][0]            = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=17&professional_role=38&professional_role=153&professional_role=69&professional_role=171&professional_role=117&professional_role=118&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";
        if ( ! empty($categories['lawyers']))       $categories['lawyers']['active'][0]       = $this->base_url . "/search/resume?area={$this->region_id}&job_search_status=active_search&professional_role=166&professional_role=158&professional_role=145&professional_role=146&relocation=living_or_relocation&gender=unknown&text=&clusters=true&exp_period=all_time&logic=normal&no_magic=true&order_by=relevance&ored_clusters=true&pos=full_text&items_on_page=100&search_period=0&page=[PAGE]&hhtmFrom=resume_search_result";


        if ( ! empty($categories['auto']))            $categories['auto']['passive'][0]          = str_replace('active_search', 'looking_for_offers', $categories['auto']['active'][0]);
        if ( ! empty($categories['admin']))           $categories['admin']['passive'][0]         = str_replace('active_search', 'looking_for_offers', $categories['admin']['active'][0]);
        if ( ! empty($categories['bank']))            $categories['bank']['passive'][0]          = str_replace('active_search', 'looking_for_offers', $categories['bank']['active'][0]);
        if ( ! empty($categories['security']))        $categories['security']['passive'][0]      = str_replace('active_search', 'looking_for_offers', $categories['security']['active'][0]);
        if ( ! empty($categories['bookkeeping']))     $categories['bookkeeping']['passive'][0]   = str_replace('active_search', 'looking_for_offers', $categories['bookkeeping']['active'][0]);
        if ( ! empty($categories['management']))      $categories['management']['passive'][0]    = str_replace('active_search', 'looking_for_offers', $categories['management']['active'][0]);
        if ( ! empty($categories['gov']))             $categories['gov']['passive'][0]           = str_replace('active_search', 'looking_for_offers', $categories['gov']['active'][0]);
        if ( ! empty($categories['mining']))          $categories['mining']['passive'][0]        = str_replace('active_search', 'looking_for_offers', $categories['mining']['active'][0]);
        if ( ! empty($categories['home']))            $categories['home']['passive'][0]          = str_replace('active_search', 'looking_for_offers', $categories['home']['active'][0]);
        if ( ! empty($categories['procurement']))     $categories['procurement']['passive'][0]   = str_replace('active_search', 'looking_for_offers', $categories['procurement']['active'][0]);
        if ( ! empty($categories['service']))         $categories['service']['passive'][0]       = str_replace('active_search', 'looking_for_offers', $categories['service']['active'][0]);
        if ( ! empty($categories['it']))              $categories['it']['passive'][0]            = str_replace('active_search', 'looking_for_offers', $categories['it']['active'][0]);
        if ( ! empty($categories['relax']))           $categories['relax']['passive'][0]         = str_replace('active_search', 'looking_for_offers', $categories['relax']['active'][0]);
        if ( ! empty($categories['consult']))         $categories['consult']['passive'][0]       = str_replace('active_search', 'looking_for_offers', $categories['consult']['active'][0]);
        if ( ! empty($categories['marketing']))       $categories['marketing']['passive'][0]     = str_replace('active_search', 'looking_for_offers', $categories['marketing']['active'][0]);
        if ( ! empty($categories['medicine']))        $categories['medicine']['passive'][0]      = str_replace('active_search', 'looking_for_offers', $categories['medicine']['active'][0]);
        if ( ! empty($categories['science']))         $categories['science']['passive'][0]       = str_replace('active_search', 'looking_for_offers', $categories['science']['active'][0]);
        if ( ! empty($categories['begin']))           $categories['begin']['passive'][0]         = str_replace('active_search', 'looking_for_offers', $categories['begin']['active'][0]);
        if ( ! empty($categories['sales']))           $categories['sales']['passive'][0]         = str_replace('active_search', 'looking_for_offers', $categories['sales']['active'][0]);
        if ( ! empty($categories['sales']))           $categories['sales']['passive'][1]         = str_replace('active_search', 'looking_for_offers', $categories['sales']['active'][1]);
        if ( ! empty($categories['production']))      $categories['production']['passive'][0]    = str_replace('active_search', 'looking_for_offers', $categories['production']['active'][0]);
        if ( ! empty($categories['production']))      $categories['production']['passive'][1]    = str_replace('active_search', 'looking_for_offers', $categories['production']['active'][1]);
        if ( ! empty($categories['working']))         $categories['working']['passive'][0]       = str_replace('active_search', 'looking_for_offers', $categories['working']['active'][0]);
        if ( ! empty($categories['beauty_health']))   $categories['beauty_health']['passive'][0] = str_replace('active_search', 'looking_for_offers', $categories['beauty_health']['active'][0]);
        if ( ! empty($categories['insurance']))       $categories['insurance']['passive'][0]     = str_replace('active_search', 'looking_for_offers', $categories['insurance']['active'][0]);
        if ( ! empty($categories['building']))        $categories['building']['passive'][0]      = str_replace('active_search', 'looking_for_offers', $categories['building']['active'][0]);
        if ( ! empty($categories['transport']))       $categories['transport']['passive'][0]     = str_replace('active_search', 'looking_for_offers', $categories['transport']['active'][0]);
        if ( ! empty($categories['tourism']))         $categories['tourism']['passive'][0]       = str_replace('active_search', 'looking_for_offers', $categories['tourism']['active'][0]);
        if ( ! empty($categories['hr']))              $categories['hr']['passive'][0]            = str_replace('active_search', 'looking_for_offers', $categories['hr']['active'][0]);
        if ( ! empty($categories['lawyers']))         $categories['lawyers']['passive'][0]       = str_replace('active_search', 'looking_for_offers', $categories['lawyers']['active'][0]);

        return $categories;
    }


    /**
     * Получение профессий по вакансиям
     * @return array
     * @throws \Zend_Config_Exception
     */
    private function getProfessionsVacancies(): array {

        $professions = $this->getProfessions();
        $base_url    = $this->base_url . "/search/vacancy?text=[TITLE]&from=suggest_post&salary=&clusters=true&area={$this->region_id}&no_magic=true&ored_clusters=true&items_on_page=100&enable_snippets=true&page=[PAGE]&hhtmFrom=vacancy_search_list&search_field=name";

        foreach ($professions as $key => $profession) {
            if (empty($profession['title'])) {
                unset($professions[$key]);
                continue;
            }

            $professions[$key]['url'][] = str_replace('[TITLE]', urlencode($profession['title']), $base_url);
        }

        return $professions;
    }


    /**
     * Получение профессий по резюме
     * @return array
     * @throws \Zend_Config_Exception
     */
    private function getProfessionsResume(): array {

        $professions      = $this->getProfessions();
        $base_url         = $this->base_url . "/search/resume?text=[TITLE]&clusters=true&area={$this->region_id}&currency_code=BYR&no_magic=true&ored_clusters=true&order_by=relevance&items_on_page=100&job_search_status=[STATUS]&logic=normal&pos=full_text&exp_period=all_time&page=[PAGE]&hhtmFrom=resume_search_result";
        $base_url_active  = str_replace('[STATUS]', 'active_search',      $base_url);
        $base_url_passive = str_replace('[STATUS]', 'looking_for_offers', $base_url);

        foreach ($professions as $key => $profession) {
            if (empty($profession['title'])) {
                unset($professions[$key]);
                continue;
            }

            $professions[$key]['active'][]  = str_replace('[TITLE]', urlencode($profession['title']), $base_url_active);
            $professions[$key]['passive'][] = str_replace('[TITLE]', urlencode($profession['title']), $base_url_passive);
        }

        return $professions;
    }


    /**
     * @param array $urls
     * @return array
     */
    private function loadList(array $urls): array {

        $pages = [];

        foreach ($urls as $url) {
            $page_url        = str_replace('[PAGE]', 0, $url);
            $pages_raw_first = $this->loadPages([$page_url], [ 'headers' => $this->getHeaders() ]);

            if ( ! empty($pages_raw_first[0])) {
                $pages[] = $pages_raw_first[0];

                $count_pages = $this->getCountPages($pages_raw_first[0]['content']);

                if ( ! empty($pages_raw_first[0]['headers']['Set-Cookie'])) {
                    $this->setCookie($pages_raw_first[0]['headers']['Set-Cookie']);
                }

                if ($count_pages > 1) {
                    $pages_url = [];

                    for ($p = 1; $p < $count_pages; $p++) {
                        $pages_url[] = str_replace('[PAGE]', $p, $url);
                    }

                    $pages_raw = $this->loadPages($pages_url, [ 'headers' => $this->getHeaders() ]);

                    foreach ($pages_raw as $page_raw) {
                        $pages[] = $page_raw;

                        if ( ! empty($page_raw['headers']['Set-Cookie'])) {
                            $this->setCookie($page_raw['headers']['Set-Cookie']);
                        }
                    }
                }
            }
        }

        // Вырезание лишнего текста
        foreach ($pages as $key => $page) {
            if ( ! empty($page['content'])) {
                $dom   = new Crawler($page['content']);
                $items = $this->filter($dom, '.HH-MainContent');

                if ($items->count() > 0) {
                    $pages[$key]['content'] = $items->html();
                }
            }
        }


        return $pages;
    }


    /**
     * @param string $page_content
     * @return int
     */
    private function getCountPages(string $page_content): int {

        $dom         = new Crawler($page_content);
        $items       = $this->filter($dom, '.pager > span .bloko-button[data-qa="pager-page"]');
        $count_pages = 0;

        if ($items->count() > 0) {
            $count_pages = (int)$items->last()->text();
        }

        return $count_pages;
    }


    /**
     * @param string $name
     * @param array  $cookies
     * @return string
     */
    private function getCookie(string $name, array $cookies): string {

        $cookie_value = '';

        foreach ($cookies as $cookie) {
            $name_quote = preg_quote($name);

            if (preg_match("~^{$name_quote}=(?<value>[^;]);~", $cookie, $matches)) {
                $cookie_value = $matches['value'];
                break;
            }
        }

        return $cookie_value;
    }


    /**
     * @param array $cookies_raw
     * @return void
     */
    private function setCookie(array $cookies_raw): void {

        $cookie_result = [
            'display=desktop',
            'region_clarified=NOT_SET',
            'hhrole=anonymous',
            'GMT=3',
        ];

        foreach ($this->cookies_need as $cookie_name) {
            $cookie_value = $this->getCookie($cookie_name, $cookies_raw);
            if ($cookie_value) {
                $cookie_result[] = "{$cookie_name}={$cookie_value}";
            }
        }

        $this->cookies = $cookie_result;
    }


    /**
     * @return array
     */
    private function getHeaders(): array {

        if ( ! empty($this->cookies)) {
            return [
                'Cookie' => implode('; ', $this->cookies)
            ];
        }

        return [];
    }


    /**
     * @param $url
     * @return string
     */
    private function getDomain($url): string {

        $parse_url = $url ? parse_url($url) : [];
        return $parse_url['host'] ?? '';
    }


    /**
     * @param string|null $string
     * @return string
     */
    private function cleanText(string $string = null): string {

        if (is_string($string)) {
            $string = trim($string, "\t\n\r\0 .,-");
            $string = iconv("UTF-8", "UTF-8//IGNORE", $string);
            $string = preg_replace('~[\s]{2,}~', ' ', $string);
        }

        return $string ?: '';
    }


    /**
     * @param string $currency_origin
     * @return string|null
     */
    private function getCurrency(string $currency_origin): ?string {

        $currency = null;

        switch ($currency_origin) {
            case 'Br':
            case 'бел.руб.': $currency = 'BYN'; break;
            case '€':
            case 'EUR':      $currency = 'EUR'; break;
            case '$':
            case 'USD':      $currency = 'USD'; break;
            case '₸':
            case 'KZT':      $currency = 'KZT'; break;
            case "UZS":
            case "so'm":
            case 'сум':      $currency = 'UZS'; break;
            case '₾':
            case 'GEL':      $currency = 'GEL'; break;
            case 'сом':
            case 'KGS':      $currency = 'KGS'; break;
            case '₼':
            case 'AZN':      $currency = 'AZN'; break;
            case '₽':
            case 'руб.':
            case 'руб':
            case 'RUR':
            case 'рос.руб.': $currency = 'RUB'; break;
            case 'грн':
            case '₴':
            case 'UAH':      $currency = 'UAH'; break;
        }

        return $currency;
    }
}