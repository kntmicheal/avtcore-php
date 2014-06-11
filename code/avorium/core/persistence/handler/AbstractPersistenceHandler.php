<?php

require_once($GLOBALS['AvtPersistenceAdapter']);

abstract class avorium_core_persistence_handler_AbstractPersistenceHandler {
	
	protected static $eventlisteners = array();
	
	public static function addEventListener($eventname, $listener) {
		if (!isset(static::$eventlisteners[$eventname])) {
			static::$eventlisteners[$eventname] = array();
		}
		static::$eventlisteners[$eventname][] = $listener;
	}
	
	public static function sendEvent($eventname, $args) {
		if (isset(static::$eventlisteners[$eventname])) {
			foreach (static::$eventlisteners[$eventname] as $eventlistener) {
				if (is_callable($eventlistener)) {
					call_user_func($eventlistener, $args);
				}
			}
		}
	}

}
