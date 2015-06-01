<?php namespace Scrape\TvNu;

use Carbon\Carbon;
use MartinLindhe\Traits\DiskCacheTrait;

class Scraper
{
    use DiskCacheTrait;

    /**
     * @param Carbon $date
     * @return ProgrammingCollection
     */
    public function getProgramming(Carbon $date)
    {
        $data = $this->getDataByDay($date);

        return (new Parser)->parseDataToProgrammingCollection($data);
    }

    public function getDataByDay(Carbon $date)
    {
        if ($date->dayOfYear == Carbon::now()->dayOfYear) {
            $url = 'http://www.tv.nu/';
        } else {
            throw new \Exception ('howowxsdf');
        }

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

        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        echo "GET ".$url." ".$httpCode."\n";

        curl_close($ch);
        return $output;
    }
}
