<?php

class Limiter {
    protected $subject;
    protected $eventType;
    protected $timespan;
    protected $events;

    public function __construct($subject, $eventType, $timespan = 60)
    {
        $this->subject = $subject;
        $this->eventType = $eventType;
        $this->timespan = $timespan;
        $this->events = Cache::me()->get($this->makeCacheKey()) ?: [];

        // filter and leave only events in current timespan
        $prevCount = count($this->events);
        $now = time();
        $this->events = array_filter(
            $this->events,
            function ($t) use ($now) { return $now - $t < $this->timespan; }
        );
        if ($prevCount != count($this->events)) {
            $this->save();
        }
    }

    protected function makeCacheKey() {
        return 'limiter:' . $this->subject . ':' . $this->eventType;
    }

    protected function save()
    {
        Cache::me()->set($this->makeCacheKey(), $this->events, $this->timespan);
    }

    public function isReached($limit)
    {
        return count($this->events) >= $limit;
    }

    public function increment()
    {
        $this->events []= time();
        $this->save();
        return $this;
    }
}