<?php
namespace Core2\Mod\Jobs\Index\Sources;
use Core2\Mod\Jobs\Index;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

require_once __DIR__ . '/../Hh.php';


/**
 *
 */
class ArmeniaHhRu extends Index\Hh {

    protected string $base_url  = 'armenia.hh.ru';
    protected string $region_id = '13';
}