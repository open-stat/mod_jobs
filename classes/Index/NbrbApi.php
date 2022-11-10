<?php
namespace Core2\Mod\Jobs\Index;
use GuzzleHttp\Client;


/**
 * @property \ModProxyController $modProxy
 */
class NbrbApi extends \Common {

    private $base_url = 'https://www.nbrb.by';

    /**
     * @var Client
     */
    private $client = null;


    /**
     *
     */
    public function __construct() {

        parent::__construct();
        $this->client = new Client();
    }


    /**
     * @param \DateTime|null $date
     * @param string|null    $currency
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function getCurrency(\DateTime $date = null, string $currency = null): array {

        $url = "{$this->base_url}/api/exrates/rates";

        if ($currency) {
            $currency = strtolower($currency);
            $url .= "/{$currency}";
        }

        $url .= "?periodicity=0&parammode=2";

        if ($date) {
            $url .= '&ondate=' . $date->format('Y-n-j');
        }


        $responses = $this->modProxy->request('get', [$url], [
            'request'  => [
                'timeout'            => 10,
                'connection_timeout' => 3,
                'verify'             => false,
            ],
            'level_anonymity' => ['elite'],
            'max_try'         => 5,
            'limit'           => 3,
        ]);


        $response = current($responses);
        $data = @json_decode($response['content'], true);


//        $response = $this->client->get($url, [
//            'timeout'         => 10,
//            'connect_timeout' => 5
//        ]);
//
//        $data = @json_decode($response->getBody()->getContents(), true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            throw new \Exception('Не удалось распознать json ответ от банка');
        }

        $currency_rows = [];

        if ($currency) {
            if (empty($data['Cur_ID']) ||
                empty($data['Cur_Name']) ||
                empty($data['Cur_Abbreviation']) ||
                empty($data['Date']) ||
                empty($data['Cur_Scale']) ||
                empty($data['Cur_OfficialRate'])
            ) {
                throw new \Exception('Не удалось получить корректный ответ от банка');
            }

            $currency_rows[] = [
                'id'           => $data['Cur_ID'],
                'title'        => $data['Cur_Name'],
                'abbreviation' => $data['Cur_Abbreviation'],
                'date'         => $data['Date'],
                'scale'        => $data['Cur_Scale'],
                'rate'         => $data['Cur_OfficialRate'],
            ];

        } else {
            if (empty($data[0]) ||
                empty($data[0]['Cur_ID']) ||
                empty($data[0]['Cur_Name']) ||
                empty($data[0]['Cur_Abbreviation']) ||
                empty($data[0]['Date']) ||
                empty($data[0]['Cur_Scale']) ||
                empty($data[0]['Cur_OfficialRate'])
            ) {
                throw new \Exception('Не удалось получить корректный ответ от банка');
            }

            foreach ($data as $currency_row) {
                $currency_rows[] = [
                    'id'           => $currency_row['Cur_ID'],
                    'title'        => $currency_row['Cur_Name'],
                    'abbreviation' => $currency_row['Cur_Abbreviation'],
                    'date'         => $currency_row['Date'],
                    'scale'        => $currency_row['Cur_Scale'],
                    'rate'         => $currency_row['Cur_OfficialRate'],
                ];
            }
        }

        return $currency_rows;
    }
}