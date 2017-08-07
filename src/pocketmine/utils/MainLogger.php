<?php

/*
 *
 *  ____			_		_   __  __ _				  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___	  |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|	 |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\utils;

use LogLevel;

class MainLogger extends \AttachableThreadedLogger
{
	protected $logFile;
	protected $logStream;
	protected $shutdown;
	protected $logDebug;
	/** @var MainLogger */
	public static $logger = null;

	/**
	 * @param string $logFile
	 * @param bool $logDebug
	 *
	 * @throws \RuntimeException
	 */
	public function __construct(string $logFile, bool $logDebug = false)
	{
		if(static::$logger instanceof MainLogger) {
			throw new \RuntimeException("MainLogger has been already created");
		}
		static::$logger = $this;
		touch($logFile);
		$this->logFile = $logFile;
		$this->logDebug = $logDebug;
		$this->logStream = new \Threaded;
		$this->start();
	}

	/**
	 * @return MainLogger
	 */
	public static function getLogger()
	{
		return static::$logger;
	}

	public function emergency($message)
	{
		$this->send($message, \LogLevel::EMERGENCY, "EMERGENCY", TextFormat::RED);
	}

	public function alert($message)
	{
		$this->send($message, \LogLevel::ALERT, "ALERT", TextFormat::RED);
	}

	public function critical($message)
	{
		$this->send($message, \LogLevel::CRITICAL, "CRITICAL", TextFormat::RED);
	}

	public function error($message)
	{
		$this->send($message, \LogLevel::ERROR, "ERROR", TextFormat::DARK_RED);
	}

	public function warning($message)
	{
		$this->send($message, \LogLevel::WARNING, "WARNING", TextFormat::YELLOW);
	}

	public function notice($message)
	{
		$this->send($message, \LogLevel::NOTICE, "NOTICE", TextFormat::AQUA);
	}

	public function info($message)
	{
		$this->send($message, \LogLevel::INFO, "INFO", TextFormat::WHITE);
	}

	public function debug($message)
	{
		if($this->logDebug === false) {
			return;
		}
		$this->send($message, \LogLevel::DEBUG, "DEBUG", TextFormat::GRAY);
	}

	/**
	 * @param bool $logDebug
	 */
	public function setLogDebug(bool $logDebug)
	{
		$this->logDebug = $logDebug;
	}

	public function logException(\Throwable $e, $trace = null)
	{
		if($trace === null) {
			$trace = $e->getTrace();
		}
		$errstr = $e->getMessage();
		$errfile = $e->getFile();
		$errno = $e->getCode();
		$errline = $e->getLine();

		$errorConversion = [
			0                   => "EXCEPTION",
			E_ERROR             => "E_ERROR",
			E_WARNING           => "E_WARNING",
			E_PARSE             => "E_PARSE",
			E_NOTICE            => "E_NOTICE",
			E_CORE_ERROR        => "E_CORE_ERROR",
			E_CORE_WARNING      => "E_CORE_WARNING",
			E_COMPILE_ERROR     => "E_COMPILE_ERROR",
			E_COMPILE_WARNING   => "E_COMPILE_WARNING",
			E_USER_ERROR        => "E_USER_ERROR",
			E_USER_WARNING      => "E_USER_WARNING",
			E_USER_NOTICE       => "E_USER_NOTICE",
			E_STRICT            => "E_STRICT",
			E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
			E_DEPRECATED        => "E_DEPRECATED",
			E_USER_DEPRECATED   => "E_USER_DEPRECATED",
		];
		if($errno === 0) {
			$type = LogLevel::CRITICAL;
		} else {
			$type = ($errno === E_ERROR or $errno === E_USER_ERROR) ? LogLevel::ERROR : (($errno === E_USER_WARNING or $errno === E_WARNING) ? LogLevel::WARNING : LogLevel::NOTICE);
		}
		$errno = $errorConversion[$errno] ?? $errno;
		$errstr = preg_replace('/\s+/', ' ', trim($errstr));
		$errfile = \pocketmine\cleanPath($errfile);
		$this->log($type, get_class($e) . ": \"$errstr\" ($errno) in \"$errfile\" at line $errline");
		foreach(\pocketmine\getTrace(0, $trace) as $i => $line) {
			$this->debug($line);
		}
	}

	public function log($level, $message)
	{
		switch($level) {
			case LogLevel::EMERGENCY:
				$this->emergency($message);
				break;
			case LogLevel::ALERT:
				$this->alert($message);
				break;
			case LogLevel::CRITICAL:
				$this->critical($message);
				break;
			case LogLevel::ERROR:
				$this->error($message);
				break;
			case LogLevel::WARNING:
				$this->warning($message);
				break;
			case LogLevel::NOTICE:
				$this->notice($message);
				break;
			case LogLevel::INFO:
				$this->info($message);
				break;
			case LogLevel::DEBUG:
				$this->debug($message);
				break;
		}
	}

	public function shutdown()
	{
		$this->shutdown = true;
		$this->notify();
	}

	public function send($message, $level, $prefix, $color, $direct = false)
	{
		if($direct === true) {
			$message = TextFormat::toANSI($message);
			$cleanMessage = TextFormat::clean($message);

			if(!Terminal::hasFormattingCodes()) {
				echo str_repeat("\010", strlen(TextFormat::clean(TextFormat::toANSI(TextFormat::GREEN . "/"))));
				echo $cleanMessage . PHP_EOL;
				echo TextFormat::toANSI(TextFormat::GREEN . "/");
			} else {
				echo str_repeat("\010", strlen(TextFormat::clean(TextFormat::toANSI(TextFormat::GREEN . "/"))));
				echo $message . PHP_EOL;
				echo TextFormat::toANSI(TextFormat::GREEN . "/");
			}

			if($this->attachment instanceof \ThreadedLoggerAttachment) {
				$this->attachment->call($level, $message);
			}
		} else {
			$now = time();

			$message = TextFormat::toANSI(TextFormat::AQUA . date("H:i:s", $now) . " " . TextFormat::RESET . $color . "[" . $prefix . "] " . $message . TextFormat::RESET);
			$cleanMessage = TextFormat::clean($message);

			if(!Terminal::hasFormattingCodes()) {
				echo str_repeat("\010", strlen(TextFormat::clean(TextFormat::toANSI(TextFormat::GREEN . "/"))));
				echo $cleanMessage . PHP_EOL;
				echo TextFormat::toANSI(TextFormat::GREEN . "/");
			} else {
				echo str_repeat("\010", strlen(TextFormat::clean(TextFormat::toANSI(TextFormat::GREEN . "/"))));
				echo $message . PHP_EOL;
				echo TextFormat::toANSI(TextFormat::GREEN . "/");
			}

			if($this->attachment instanceof \ThreadedLoggerAttachment) {
				$this->attachment->call($level, $message);
			}

			$this->logStream[] = date("Y-m-d", $now) . " " . $cleanMessage . PHP_EOL;
		}
	}
	
	public static function clear()
	{
		echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
		echo TextFormat::toANSI(TextFormat::GREEN . "/");
	}

	public function directSend($message)
	{
		$message = TextFormat::toANSI($message);
		$cleanMessage = TextFormat::clean($message);

		if(!Terminal::hasFormattingCodes()) {
			echo str_repeat("\010", strlen(TextFormat::clean(TextFormat::toANSI(TextFormat::GREEN . "/"))));
			echo $cleanMessage . PHP_EOL;
			echo TextFormat::toANSI(TextFormat::GREEN . "/");
		} else {
			echo str_repeat("\010", strlen(TextFormat::clean(TextFormat::toANSI(TextFormat::GREEN . "/"))));
			echo $message . PHP_EOL;
			echo TextFormat::toANSI(TextFormat::GREEN . "/");
		}
	}

	public function run()
	{
		$this->shutdown = false;
		$logResource = fopen($this->logFile, "ab");
		if(!is_resource($logResource)) {
			throw new \RuntimeException("Couldn't open log file");
		}

		while($this->shutdown === false) {
			$this->writeLogStream($logResource);
			$this->synchronized(function() {
				$this->wait(25000);
			});
		}

		$this->writeLogStream($logResource);

		fclose($logResource);
	}

	private function writeLogStream($logResource){
		while($this->logStream->count() > 0){
			$chunk = $this->logStream->shift();
			fwrite($logResource, $chunk);
		}
	}
}
