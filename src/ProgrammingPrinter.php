<?php namespace MartinLindhe\Scrape\TvNu;

use Carbon\Carbon;
use JakubOnderka\PhpConsoleColor\ConsoleColor;

class ProgrammingPrinter
{
    public static function whenAirs(ProgrammingCollection $collection, $programName)
    {
        $consoleColor = new ConsoleColor();

        $res = "Matching ".$consoleColor->apply('green', $programName).":\n\n";

        // colors: white = in future, green = currently airing

        $now = Carbon::now();

        /**
         * @var SearchMatch[] $matches
         */
        $matches = [];

        foreach ($collection->getProgrammings() as $programming) {
            foreach ($programming->getEvents() as $event) {

                if (stripos($event->title, $programName) === false) {
                    continue;
                }

                if ($event->ends_at->lt($now)) {
                    // dont show past events
                    continue;
                }

                $match = new SearchMatch;
                $match->event = $event;
                $match->channelName = $programming->getChannelName();

                $matches[] = $match;
            }
        }

        // sort matches
        usort($matches, function ($a, $b) {
            return $a->event->starts_at->gt($b->event->starts_at);
        });

        foreach ($matches as $match) {
            $color = 'white';
            if ($match->event->starts_at->lte($now) && $match->event->ends_at->gte($now)) {
                $color = 'green';
            }
            $res .=
                $consoleColor->apply(
                    $color,
                    $match->event->starts_at->format('H:i')
                    . ' '
                    . $match->event->title
                )
                . ', '
                . $match->event->starts_at->diffForHumans()
                . ' in '
                . $match->channelName
                . "\n";
        }

        return $res;
    }

    public static function printAll(ProgrammingCollection $collection, array $includeOnly = [], $showFullProgramming = true)
    {
        $width = self::getConsoleWidth();
        $res = '';
        $progs = $collection->getProgrammings();

        $twoColumnWidth = 84;

        if ($width < $twoColumnWidth) {
            // terminal is too small for two columns

            foreach ($progs as $prog) {
                if (empty($includeOnly) || in_array($prog->getChannelName(), $includeOnly)) {
                    $res .= self::printChannel($collection, $prog->getChannelName(), $showFullProgramming);
                    $res .= "\n";
                }
            }
            return trim($res);

        }

        // round to even number
        $twoColumnWidth = (round($width / 2) * 2) - 2;

        /**
         * @var ChannelProgramming[] $included
         */
        $included = [];
        foreach ($progs as $prog) {
            if (empty($includeOnly) || in_array($prog->getChannelName(), $includeOnly)) {
                $included[] = $prog;
            }
        }

        $maxLength = $twoColumnWidth / 2;

        for ($i = 0; $i < count($included); $i += 2) {
            $part1 = self::printChannel($collection, $included[$i]->getChannelName(), $showFullProgramming, $maxLength);

            if (!isset($included[$i+1])) {
                $res .= $part1;
                continue;
            }

            $part2 = self::printChannel($collection, $included[$i+1]->getChannelName(), $showFullProgramming, $maxLength);

            $one = explode("\n", trim($part1));
            $two = explode("\n", trim($part2));

            $len = count($one) > count($two) ? count($one) : count($two);

            for ($j = 0; $j < $len; $j++) {
                $p1 = isset($one[$j]) ? $one[$j] : '';
                $res .= self::mb_str_pad_ansicodes($p1, $maxLength, ' ');
                $p2 = isset($two[$j]) ? $two[$j] : '';
                $res .= $p2."\n";
            }

            if ($i + 2 < count($included)) {
                $res .= "\n";
            }
        }

        return $res;
    }

    /**
     * Multibyte and ansi escape code aware str_pad()
     * @param $input
     * @param $pad_length
     * @param string $pad_string
     * @param int $pad_style
     * @return string
     */
    public static function mb_str_pad_ansicodes($input, $pad_length, $pad_string = ' ', $pad_style = STR_PAD_RIGHT)
    {
        $len = strlen($input) - self::mb_strlen_ansicode($input) + $pad_length;
        return str_pad($input, $len, $pad_string, $pad_style);
    }

    /**
     * Multibyte and ansi escape code aware strlen()
     * @param $input
     * @return int
     */
    public static function mb_strlen_ansicode($input)
    {
        $len = 0;
        $inAnsi = false;
        for ($i = 0; $i < mb_strlen($input); $i++) {
            $char = mb_substr($input, $i, 1);
            if ($char == "\033" && mb_substr($input, $i + 1, 1) == '[') {
                $inAnsi = true;
            }
            if (!$inAnsi) {
                $len++;
            }
            if ($inAnsi && $char == 'm') {
                $inAnsi = false;
            }
        }
        return $len;
    }

    public static function getConsoleWidth()
    {
        return intval(exec('tput cols'));
    }

    /**
     * @param ProgrammingCollection $collection
     * @param string $channelName
     * @param bool $showFullProgramming show all programs for the day
     * @param int $maxLength
     * @return string
     */
    public static function printChannel(ProgrammingCollection $collection, $channelName, $showFullProgramming = false, $maxLength = 100)
    {
        $consoleColor = new ConsoleColor();

        $res = '';

        $programming = $collection->getProgrammingsByChannel($channelName);
        $res .= $consoleColor->apply('blue', '==> ')
            .$consoleColor->apply('white', $programming->getChannelName()
            .$consoleColor->apply('blue', ' <==')
            ."\n");

        $now = Carbon::now();
        $nextTwoHours = Carbon::now()->addHours(2);

        foreach ($programming->getEvents() as $event) {
            $color = null;
            if ($event->starts_at->lte($now) && $event->ends_at->gte($now)) {
                $color = 'green';
            } else if (!$showFullProgramming && $event->starts_at->gte($now) && $event->starts_at->lte($nextTwoHours) ) {
                $color = 'default';
            } else if ($showFullProgramming) {
                $color = 'default';
            }

            if (!$color) {
                continue;
            }

            $title = $event->render();
            if (mb_strlen($title) > $maxLength) {
                $title = mb_substr($title, 0, $maxLength-2).'..';
            }

            $res .= $consoleColor->apply(
                $color,
                $title
            );
            $res .= "\n";
        }

        return $res;
    }
}
