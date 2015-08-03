<?php namespace MartinLindhe\Scrape\TvNu;

use Carbon\Carbon;
use MartinLindhe\Traits\DiskCacheTrait;
use MartinLindhe\UserAgentTrait\UserAgentTrait;

class Scraper
{
    use DiskCacheTrait;
    use UserAgentTrait;

    /**
     * @param Carbon $date
     * @return ProgrammingCollection
     */
    public function getProgramming(Carbon $date)
    {
        $data = $this->getDataByDay($date);

        return Parser::parseDataToProgrammingCollection($date, $data);
    }

    private function getDataByDay(Carbon $date)
    {
        $url = 'http://www.tv.nu/arkiv/'.$date->format('Y-m-d');

        if ($data = $this->load($url)) {
            return $data;
        }

        $data = $this->getRequest($url);

        if (strpos($data, 'ISO-8859-1') !== false) {
            // 2015-03-18: page is served as ISO-8859-1 with a meta tag
            $data = mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1');
        }

        $this->store($url, $data);

        return $data;
    }

    private function getRequest($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_USERAGENT, $this->userAgent);

        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        dbg('GET '.$url.' '.$httpCode);

        curl_close($ch);
        return $output;
    }
}
