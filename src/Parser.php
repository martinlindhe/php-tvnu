<?php namespace MartinLindhe\Scrape\TvNu;

use Carbon\Carbon;

class Parser
{
    /**
     * @param $data
     * @return ProgrammingCollection
     * @throws \Exception
     */
    public static function parseDataToProgrammingCollection($data)
    {
        $startPos = 0;
// XXX all broken
        $res = new ProgrammingCollection;
        do {
            $findStart = '<div class="tabla_container">';
            $findEnd = "</div> \n</div>";
            $startPos = strpos($data, $findStart, $startPos);
            if ($startPos === false) {
                // done
                break;
            }
            $endPos = strpos($data, $findEnd, $startPos);
            if ($endPos === false) {
                throw new \Exception("parse error: didn't find end pos");
            }
            $chunk = substr($data, $startPos, $endPos - $startPos);
            $programming = self::parseChannelChunk($chunk);

            $res->addProgramming($programming);
            $startPos++;

        } while (1);

        return $res;
    }

    /**
     * @param string $chunk
     * @return ChannelProgramming
     * @throws \Exception
     */
    private static function parseChannelChunk($chunk)
    {
        $res = new ChannelProgramming;

        // extract channel name

        $channelName = self::str_between_exclude($chunk, '<div class="tabla_topic">', '</div>');
        $channelName = trim(strip_tags($channelName));
        if (!$channelName) {
            throw new \Exception('parse error: no channel name');
        }
        $res->setChannelName($channelName);

        $content = self::str_between_exclude($chunk, '<ul class="prog_tabla">', '</ul>');
        if (!$content) {
            throw new \Exception('parse error: no content');
        }

        $content = str_replace('</li>', "\n", $content);
        $content = str_replace("\t", '', $content);
        $content = strip_tags($content);

        $programs = explode("\n", trim($content));

        $foundHour = 0;
        $addDays = 0;

        /** @var ChannelEvent $event */
        $event = null;

        foreach ($programs as $prog) {
            if (!$prog) {
                continue;
            }

            preg_match('/^(?<hh>[\d]+)+\:(?<mm>[\d]+)+$/ui', $prog, $match);
            if (!empty($match['hh']) && !empty($match['mm'])) {

                $event = new ChannelEvent;

                $time = explode(' ', $prog)[0];

                $timeParts = explode(':', $time);
                if ($timeParts[0] < $foundHour) {
                    // new day
                    $addDays = 1;
                }
                $foundHour = $timeParts[0];

                $event->starts_at = Carbon::createFromTime($timeParts[0], $timeParts[1], 0, 'Europe/Stockholm')
                    ->addDays($addDays);

                continue;
            }

            if (!$event) {
                continue;
            }

            $event->title = $prog;

            $res->addEvent($event);
        }

        // guesstimate end time for each event
        $events = $res->getEvents();
        for ($i = 0; $i < count($events); $i++) {
            if (!empty($events[$i + 1])) {
                $events[$i]->ends_at = $events[$i+1]->starts_at;
            } else {
                // HACK: we dont know end of last event, so we add 2 hours
                $events[$i]->ends_at = $events[$i]->starts_at->copy()->addHours(2);
            }
        }
        $res->setEvents($events);

        return $res;
    }

    /**
     * @param $s string
     * @param $needle1 string
     * @param $needle2 string
     * @return string
     */
    private static function str_between_exclude($s, $needle1, $needle2)
    {
        $p1 = strpos($s, $needle1);
        if ($p1 === false)
            return '';

        $p2 = strpos($s, $needle2, $p1 + strlen($needle1));
        if ($p2 === false)
            return '';

        return substr($s, $p1 + strlen($needle1), $p2 - $p1 - strlen($needle1));
    }

    /**
     * @param $s string
     * @param $needle1 string
     * @param $needle2 string
     * @return string
     */
    private static function mb_str_between_exclude($s, $needle1, $needle2)
    {
        $p1 = mb_strpos($s, $needle1);
        if ($p1 === false) {
            return '';
        }

        $p2 = mb_strpos($s, $needle2, $p1 + mb_strlen($needle1));
        if ($p2 === false) {
            return '';
        }

        return mb_substr($s, $p1 + mb_strlen($needle1), $p2 - $p1 - mb_strlen($needle1));
    }
}
