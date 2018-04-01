<?php

class Script_UpdateBoardStats extends ConsoleScript {

    public function run()
    {
        if ($this->getArg(0) == 'init') {
            $timestampRange = TimestampRange::create(Timestamp::create(0), Timestamp::makeNow()); // all times
        } else {
            $timestampRange = TimestampRange::create(Timestamp::makeToday()->modify('1 day ago'), Timestamp::makeNow());
        }

        /** @var BoardStatsDAO[] $daos */
        $daos = [ BoardStatsDaily::dao(), BoardStatsHourly::dao() ];
        foreach ($daos as $dao) {
            $dao->store($dao->aggregate($timestampRange));
        }
    }

}