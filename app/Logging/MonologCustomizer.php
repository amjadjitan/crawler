<?php
namespace App\Logging;

use Monolog\Formatter\LogglyFormatter;
use Monolog\Handler\LogglyHandler;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use DateTimeImmutable;
use DateTimeZone;
use NewRelic\Monolog\Enricher\{Formatter, Processor};

class MonologCustomizer {

  private static $bufferHandler = null;

  /**
   * Customize the given logger instance.
   *
   * @param  array $config
   * @return Logger
   */
  public function __invoke(array $config){
    $logger = new Logger("logger");
    $token  = env("LOG_UPLOAD_TOKEN","NO_TOKEN");
    if($token === "NO_TOKEN"){
      // DEV ENV
      $now      = (new DateTimeImmutable("now"))->format("Y-m-d\\TH-i-s");
      // This section attemps to deals with http vs cli `working directory` disparities
      $dirs     = explode(DIRECTORY_SEPARATOR, getcwd());
      $maybe    = $dirs[count($dirs)-1]==="public" ? ".." : ".";
      $fileName = implode(DIRECTORY_SEPARATOR, [getcwd(), $maybe, "storage", "logs", $now."_".uniqid().".json"]);
      $wrappedHandler = new StreamHandler($fileName, Logger::DEBUG);
      $wrappedHandler->setFormatter(new LogglyFormatter(LogglyFormatter::BATCH_MODE_NEWLINES, true));
    }
    else{
      // PRODUCTION ENV
      $wrappedHandler = new LogglyHandler($token, Logger::DEBUG);
      $wrappedHandler->addTag(env("LOG_UPLOAD_TAG","REPORTING_COMPILER"));
      $wrappedHandler = new BufferHandler($wrappedHandler, env("LOG_UPLOAD_BUFFER_SIZE",250), Logger::DEBUG, true, true);
      self::$bufferHandler = $wrappedHandler;
    }
    $logger->setTimezone(new DateTimeZone("America/New_York"));
    $logger->pushHandler($wrappedHandler);

    $newHandler = new StreamHandler(storage_path('/logs/newrelic.log'));
    $newHandler->setFormatter(new Formatter);
    $logger->pushHandler($newHandler);

    $logger->pushProcessor(
      function($record){
        $record["context"]["peakMemoryUsage"] = memory_get_peak_usage();
        return $record;
      }
    );
    $logger->info("crawler service : Log is logging"); // TODO
    return $logger;
  }
  public static function flush(){
    if(!is_null(self::$bufferHandler)){
      self::$bufferHandler->flush();
    }
  }
}
