<?php
 /* test.php 4 log.inc.php */
const DEBUG=true;
require_once 'log.inc.php';
$messages = file('test_messages.txt');
$options = array(
  'path'           => '.',           // path to the logfile ('.' = logfile life in same directory)
  'filename'       => 'log',         // main name, _ and date will be added
  'syslog'         => false,         // true = use system log function (works only in txt format)
  'filePermission' => 0644,          // or 0777
  'maxSize'        => 0.002,         // in MB
  'format'         => 'htm',         // use txt, csv or htm
  'template'       => 'barecss',     // for htm format only: plain, terminal or barecss
  'timeZone'       => 'UTC',         // UTC or what you like
  'dateFormat'     => 'Y-m-d H:i:s', // see http://php.net/manual/en/function.date.php
  'backtrace'      => true,          // true = slower but with line number of call
);
$log = new log($options);
#$log->maxSize(10);
$sleep = 0;
for ($i=0; $i<7; ++$i) {
  $index = array_rand($messages,1);
  $status = intval(rand(1,9).'00');
  $log->write(rtrim($messages[$index]),$status);
  $index = array_rand($messages,1);
  $status = intval(rand(1,9).'00');
  $log->write(rtrim($messages[$index]),$status);
  $index = array_rand($messages,1);
  $status = intval(rand(1,9).'00');
  $log->write(rtrim($messages[$index]),$status);
  $sleep = $sleep + 0.3;
  usleep(300000); // 0.3 seconds
}
echo 'Runtime: '.number_format((microtime(true)-$_SERVER['REQUEST_TIME_FLOAT']-$sleep),3);
