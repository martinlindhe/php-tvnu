<?php namespace MartinLindhe\Scrape\TvNu;

class ChannelProgramming
{
    protected $channelName;

    /**
     * @var ChannelEvent[] $events
     */
    protected $events = [];

    public function addEvent(ChannelEvent $event)
    {
        $this->events[] = $event;
    }

    /**
     * @param array $events
     */
    public function setEvents(array $events)
    {
        $this->events = $events;
    }

    public function setChannelName($name)
    {
        $this->channelName = $name;
    }

    public function getChannelName()
    {
        return $this->channelName;
    }

    /**
     * @return ChannelEvent[]
     */
    public function getEvents()
    {
        return $this->events;
    }
}
