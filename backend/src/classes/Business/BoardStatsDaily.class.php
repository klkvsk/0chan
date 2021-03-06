<?php
/*****************************************************************************
 *   Copyright (C) 2006-2009, onPHP's MetaConfiguration Builder.             *
 *   Generated by onPHP-1.1.master at 2017-05-03 16:07:07                    *
 *   This file will never be generated again - feel free to edit.            *
 *****************************************************************************/

	class BoardStatsDaily extends AutoBoardStatsDaily implements Prototyped, DAOConnected
	{
		/**
		 * @return BoardStatsDaily
		**/
		public static function create()
		{
			return new self;
		}
		
		/**
		 * @return BoardStatsDailyDAO
		**/
		public static function dao()
		{
			return Singleton::getInstance('BoardStatsDailyDAO');
		}
		
		/**
		 * @return ProtoBoardStatsDaily
		**/
		public static function proto()
		{
			return Singleton::getInstance('ProtoBoardStatsDaily');
		}

        public function getActualPeriodValue()
        {
            return $this->getDate();
        }

        public function setActualPeriodValue(Timestamp $timestamp)
        {
            return $this->setDate($timestamp);
        }

        protected function exportKey()
        {
            return $this->getDate()->toString();
        }


    }
?>