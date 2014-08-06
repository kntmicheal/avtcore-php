<?php
/**
 * Logger-Klasse. Loggt Meldungen in error.log und info.log in
 * Unterverzeichnisse log/Jahr/Monat/Tag.
 * Einbindung mit: require_once 'code/avorium/core/util/Log.php';
 * Verwendung: avorium_core_util_Log::info('Irgendeinemeldung');
 */
class avorium_core_util_Log {
	
	/**
	 * Use this function for critical error messages, when
	 * unexpected behaviours occur. Make the messages such
	 * self explaining that the error can be found very fast
	 */
	public static function error($message) {
		self::log('info.log', $message);
	}
	
	/**
	 * Use this function for runtime information like CRON job results
	 * or so.
	 */
	public static function info($message) {
		self::log('info.log', $message);
	}
	
	private static function log($filename, $message) {
		// Construct logfile path
		$now = new DateTime();
		$logFilePath = dirname(__FILE__).'/../../../../log/'.$now->format('Y/m/d').'/';
		// Create log path if not existing
		if (!file_exists($logFilePath)) {
			mkdir($logFilePath, 0777, true);
		}
		$output = $now->format('H:i:s'). ' - '.$message."\n";
		file_put_contents($logFilePath.$filename, $output, FILE_APPEND | LOCK_EX);
	}
}
