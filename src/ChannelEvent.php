<?php namespace Scrape\TvNu;

class ChannelEvent
{
    /**
     * @var \Carbon\Carbon $starts_at
     */
    public $starts_at;

    /**
     * @var \Carbon\Carbon $starts_at
     */
    public $ends_at;

    /**
     * @var string
     */
    public $title;

    public function render()
    {
        return $this->starts_at->format('H:i')
            //.'-'.$this->ends_at->format('H:i')
            . ' '
            . $this->title;
    }
}
