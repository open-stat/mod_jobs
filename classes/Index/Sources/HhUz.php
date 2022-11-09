<?php
namespace Core2\Mod\Jobs\Index\Sources;
use Core2\Mod\Jobs\Index;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

require_once __DIR__ . '/../Hh.php';


/**
 *
 */
class HhUz extends Index\Hh {

    protected string $base_url  = 'https://hh.uz';
    protected string $region_id = '97';
}