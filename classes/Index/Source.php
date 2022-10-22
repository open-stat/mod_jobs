<?php
namespace Core2\Mod\Jobs\Index;

use Symfony\Component\DomCrawler\Crawler;

require_once DOC_ROOT . 'core2/inc/classes/Common.php';

/**
 *
 */
abstract class Source extends \Common {

    protected $debug = false;

    /**
     * @param array $options
     */
    public function __construct(array $options) {
        parent::__construct();

        $this->debug = !! ($options['debug'] ?? false);
    }


    /**
     * Название источника
     * @return string
     */
    abstract public function getTitle(): string;


    /**
     * Загрузка страниц вакансиями
     * @param string $category_name
     * @return array
     */
    abstract public function loadVacanciesCategory(string $category_name): array;


    /**
     * Загрузка страниц резюме
     * @param string $category_name
     * @param array  $options
     * @return array
     */
    abstract public function loadResumeCategory(string $category_name, array $options): array;


    /**
     * Загрузка страниц вакансиями
     * @param string $profession_name
     * @return array
     */
    abstract public function loadVacanciesProfessions(string $profession_name): array;


    /**
     * Загрузка страниц резюме
     * @param string $profession_name
     * @param array  $options
     * @return array
     */
    abstract public function loadResumeProfessions(string $profession_name, array $options): array;


    /**
     * Загрузка страниц резюме и вакансий
     * @param string $url
     * @return array
     */
    abstract public function loadVacancies(string $url): array;


    /**
     * Загрузка страниц резюме и вакансий
     * @param string $url
     * @return array
     */
    abstract public function loadResume(string $url): array;


    /**
     * Обработка страниц с вакансиями
     * @param string $content
     * @param array  $options
     * @return array
     */
    abstract public function parseVacanciesList(string $content, array $options = []): array;


    /**
     * Обработка страниц резюме
     * @param string $content
     * @param array  $options
     * @return array
     */
    abstract public function parseResumeList(string $content, array $options = []): array;


    /**
     * Обработка страниц с вакансиями
     * @param string $content
     * @return array
     */
    abstract public function parseVacancy(string $content): array;


    /**
     * Обработка страниц резюме
     * @param string $content
     * @return array
     */
    abstract public function parseResume(string $content): array;


    /**
     * Получение списка категорий
     * @return array
     * @throws \Zend_Config_Exception
     */
    public function getCategories(): array {

        $config = $this->getModuleConfig('jobs');

        $categories = $config->categories
            ? $config->categories->toArray()
            : [];

        return $categories;
    }



    /**
     * Получение списка профессий
     * @return array
     * @throws \Zend_Config_Exception
     */
    public function getProfessions(): array {

        $config = $this->getModuleConfig('jobs');

        $professions = $config->professions
            ? $config->professions->toArray()
            : [];

        return $professions;
    }


    /**
     * @param array $addresses
     * @param array $options
     * @return array
     */
    protected function loadPages(array $addresses, array $options = []): array {

        $responses = $this->modProxy->request('get', $addresses, [
            'request' => [
                'timeout'            => 10,
                'connection_timeout' => 3,
                'verify'             => false,
                'headers'            => $options['headers'] ?? [],
            ],
            'level_anonymity' => ['elite', /*'anonymous', 'non_anonymous'*/ ],
            'max_try'         => 2,
            'limit'           => 1,
            'debug'           => $this->debug ? 'print' : '',
        ]);

        $pages = [];

        foreach ($responses as $response) {
            if ($response['status'] != 'success' ||
                $response['http_code'] != '200' ||
                empty($response['content'])
            ) {
                if ($this->debug) {
                    print_r($response) . PHP_EOL;
                }

            } else {
                $pages[] = [
                    'url'     => $response['url'],
                    'content' => $response['content'],
                    'headers' => $response['headers'],
                ];
            }
        }

        return $pages;
    }


    /**
     * @param string $string
     * @param array  $tags
     * @return string
     */
    protected function deleteTags(string $string, array $tags): string {

        $regex = [];

        foreach ($tags as $tag) {
            $regex[] = "~(<{$tag}[^>]*>.*?</{$tag}[^>]*>)~muis";
        }

        return (string)preg_replace($regex, '', $string);
    }


    /**
     * @param Crawler $dom
     * @param string  $rule
     * @return Crawler
     */
    protected function filter(Crawler $dom, string $rule): Crawler {

        if ($this->isXpath($rule)) {
            return $dom->filterXPath(mb_substr($rule, 6));
        } else {
            return $dom->filter($rule);
        }
    }


    /**
     * @param string $rule
     * @return bool
     */
    protected function isXpath(string $rule): bool {

        return mb_strpos($rule, '//') === 0;
    }
}