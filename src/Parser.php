<?php namespace Scrape\TvNu;

use Carbon\Carbon;

class Parser
{
    /**
     * @param $data
     * @return ProgrammingCollection
     * @throws \Exception
     */
    public function parseDataToProgrammingCollection($data)
    {
        $startPos = 0;

        $res = new ProgrammingCollection;
        do {
            $findStart = '<div class="tabla_topic"> <a class="logo-container"';
            $findEnd = '</div> </div>';
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

            $programming = $this->parseChannelChunk($chunk);
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
    private function parseChannelChunk($chunk)
    {
        $res = new ChannelProgramming;

        // extract channel name
        $channelName = $this->str_between_exclude($chunk, '<p class="tabla_topic_label">', '</p>');
        $channelName = trim(strip_tags($channelName));
        if (!$channelName) {
            throw new \Exception('parse error: no channel name');
        }
        $res->setChannelName($channelName);

        $content = $this->str_between_exclude($chunk, '<ul class="prog_tabla">', '</ul>');
        if (!$content) {
            throw new \Exception('parse error: no content');
        }

        $content = str_replace('</li>', "\n", $content);

        $programs = explode("\n", trim($content));

        $foundHour = 0;
        $addDays = 0;
        foreach ($programs as $prog) {

            $pos1 = strpos($prog, '/>');
            $rest = substr($prog, $pos1 + 2);
            if (!$rest) {
                continue;
            }

            $title = $this->str_between_exclude($rest, 'title="', '"');
            if (!$title) {
                throw new \Exception('parse error: no title');
            }

            $timeChunk = trim(strip_tags($rest));
            $time = explode(' ', $timeChunk)[0];

            $timeParts = explode(':', $time);
            if ($timeParts[0] < $foundHour) {
                // new day
                $addDays = 1;
            }
            $c = Carbon::createFromTime($timeParts[0], $timeParts[1], 0, 'Europe/Stockholm')->addDays($addDays);
            $foundHour = $timeParts[0];

            $event = new ChannelEvent;
            $event->starts_at = $c;
            $event->title = $title;

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
    private function str_between_exclude($s, $needle1, $needle2)
    {
        $p1 = strpos($s, $needle1);
        if ($p1 === false)
            return '';

        $p2 = strpos($s, $needle2, $p1 + strlen($needle1));
        if ($p2 === false)
            return '';

        return substr($s, $p1 + strlen($needle1), $p2 - $p1 - strlen($needle1));
    }
}
