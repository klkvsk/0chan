<?php
/*****************************************************************************
 *   Copyright (C) 2006-2009, onPHP's MetaConfiguration Builder.             *
 *   Generated by onPHP-1.1.master at 2017-03-23 01:07:32                    *
 *   This file is autogenerated - do not edit.                               *
 *****************************************************************************/

	abstract class AutoStorageTrash extends IdentifiableObject
	{
		protected $server = null;
		protected $filename = null;
		
		public function getServer()
		{
			return $this->server;
		}
		
		/**
		 * @return StorageTrash
		**/
		public function setServer($server)
		{
			$this->server = $server;
			
			return $this;
		}
		
		public function getFilename()
		{
			return $this->filename;
		}
		
		/**
		 * @return StorageTrash
		**/
		public function setFilename($filename)
		{
			$this->filename = $filename;
			
			return $this;
		}
	}
?>