<?php namespace Scrape\TvNu;

class ProgrammingCollection
{
    /**
     * @var ChannelProgramming[] array
     */
    protected $programming = [];

    public function addProgramming(ChannelProgramming $programming)
    {
        $this->programming[] = $programming;
    }

    /**
     * @return ChannelProgramming[] array
     */
    public function getProgrammings()
    {
        return $this->programming;
    }

    /**
     * @param $name
     * @return ChannelProgramming|null
     */
    public function getProgrammingsByChannel($name)
    {
        foreach ($this->programming as $prog) {
            if (strtoupper($prog->getChannelName()) == strtoupper($name)) {
                return $prog;
            }
        }
        return null;
    }
}
