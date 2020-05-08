# Introduction to PHP logging via log file

**In this article I show how logging techniques can be used in PHP.**

We will discuss all the details, why logging via logfile is important and what the logs should show. And finally I will show how to set up and use my PHP logging class.

But let us begin!


## What is logging?

The reader probably already has an intuitive understanding of what logging is, but it is still worth taking the time to go through a proper definition that we can build on. We have defined logging this way:

> Logging persistently records information about the runtime behavior of an application.

There are two important points to note here.

First, logging is all about the runtime behavior of the application. In other words, it is about recording the events of the application in their chronological sequence.

Second, you always want to store logs on a persistent (non-volatile) medium. The exact meaning of "persistent" depends on your requirements and your environment, but at the very least you want to be able to read the logs even if the application - or the entire server - crashes. In general, however, saving as a file (text or HTML) in the file system should be sufficient.

While this definition explains what logging is, it does not explain why logging is important in an application. This is an important question, which I will discuss in detail below.


## Why write log files?

If we lived in an ideal world, our applications would simply work as planned. But the reality is that even well-designed applications have flaws and are in danger of collapsing at one point or another. It gets worse when an application does not abort with an error message, but behaves in an untraceable way that is not desired.

If a developer develops on his PC and encounters errors, these are usually relatively easy to fix. After all, the developer knows what he was working on and the sequence of events that led to the error. He could also debug his application and thus obtain a lot of additional information about the system.

But at some point he has to release the application for his users. The application may be used in an unplanned way or in environments not intended, and there may be many users using the application simultaneously. Also, the application must function for a much longer period of time. Debugging errors in this situation is very difficult without additional information. **This is the start for the...**


### Logging per log file

Recording the runtime behavior of your application allows you to investigate all kinds of unwanted behavior afterwards. In other words, even if your application crashes and loses all of its runtime state, the application logs will help you understand what caused the crash.

But the benefits of protocols do not stop there. Logs become priceless when users report bugs: You can audit your application, including retrospective audits (what happened on a particular day at a particular time). Log files can be analyzed at any time, and for example, warn about possible performance problems; and much more..

In short: File logging is a must for software applications as it gives you an overview of the runtime history of your application.


## What should be logged?

The logging approach in a log file fulfills the persistence requirement because it stores the logs in a file. However, it is not necessarily good for recording runtime behavior because we do not necessarily know when exactly each particular event occurred.

This leads to a more general question: What information should be logged?

It is a common practice to include at least the following information in each log entry:


  - **time stamp** &mdash; When exactly did the event described by the log entry occur? To make life easier, a correctly formatted ISO 8601 date/time in UTC should be used. 

  - **event context** &mdash; For example, it would be a bad idea to log "Name: test; eMail: mail@example.com", as you will probably forget what it means in a month's time. Here is a context to make the log entry more descriptive: "Login: User=test eMail=mail@example.com" 
  
  - **Protocol severity** &mdash; Such as "Error", "Warning", "Info", etc. This information provides additional context and allows easy filtering of logs by severity. 


I suggest that the above list should be considered as the absolute basis for a logging strategy and that no less information than this should be logged. Otherwise, it will be very difficult to read and understand the logs.

Also the complete article is only to be seen as very basic guidelines. It's an introduction, but of course there is much more to say about file logging. I recommend to read the [Logging Cheat Sheet](https://www.owasp.org/index.php/Logging_Cheat_Sheet) to get an even broader overview of this important topic.


## Simple file logging via PHP Log Class

Sure, you can program your own logging solution, but that would be unnecessary work in my opinion. Most of the time you log only because you have to - it is not part of the functionality of an application. Every minute that can be saved for implementing logging solutions is one minute that you can put into the core functionality of the main application.

Luckily, the requirements for a logging solution are very similar for millions of developers, so a standard solution can be solved in the form of a logging class. This ready-to-use library, just needs to be imported and can be used in any PHP project. Usually you do not have to worry about the exact functionality. In other words, there is no need to reinvent the wheel when it comes to log file logging..

Let me introduce my simple logging class in PHP In the next sections I will show how it can be used in any project.


## 1. Installation PHP log file class

If you develop your application with PHP, simply copy the log class file into your root directory. For more information, see the documentation notes at the beginning of the class.

**Here is the PHP log file class log.inc.php as complete source code (only 200 lines):**

```php
<?php
/* ⣠⣾⣿ Simple PHP log class PHP 4.3 and > ⣿⣷⣄ */
class log {
    protected $options = array(
        // e.g. '/path/to/logfile' OR use '.' = logfile life in same directory
        'path'           => '.',
        // logfile filename without extention ('-date' in Y-m-d format will be attached)
        'filename'       => 'log',
        // e.g. true = use system log function (works only in txt format)
        'syslog'         => false,
        // e.g. 0644 OR 0777 see: http://php.net/manual/en/function.chmod.php
        'filePermission' => 0644,
        // Maximal LogFile Size in MB
        'maxSize'        => 10,
        // e.g. 'txt' = Text with TAB OR 'csv' = Comma-Separated Values with (,) OR 'htm' = HTML
        'format'         => 'htm',
        // e.g. 'terminal' = terminalcss.xyz OR 'barecss' = barecss.com OR plain = simple HTML
        'template'       => 'barecss',
        // e.g. 'UTC' see: http://php.net/manual/en/timezones.php
        'timeZone'       => 'UTC',
        // e.g. 'Y-m-d H:i:s' see: http://php.net/manual/en/function.date.php
        'dateFormat'     => 'Y-m-d H:i:s',
        // e.g. true = Calling Prog. AND Linenumber OR false = Only calling Prog.
        'backtrace'      => true,
    );
    // log message severities from RFC 3164, section 4.1.1, table 2.
    // http://www.faqs.org/rfcs/rfc3164.html
    protected $level = array(
        100 => 'DEBUG',     // Debug: debug messages
        200 => 'INFO',      // Informational: informational messages
        300 => 'NOTICE',    // Notice: normal but significant condition
        400 => 'WARNING',   // Warning: warning conditions
        500 => 'ERROR',     // Error: error conditions
        600 => 'CRITICAL',  // Critical: critical conditions
        700 => 'ALERT',     // Alert: action must be taken immediately
        800 => 'EMERGENCY', // Emergency: system is unusable
        900 => 'GAU',       // Maximum Credible Accident: no system anymore, we need no log
    );
    // set open and write error messages for die()
    protected $error = array(
        'openA' => 'The logfile could not be opened for appending. Check permissions: ',
        'openW' => 'The logfile exists, but could not be opened for writing. Check permissions: ',
        'write' => 'The logfile could not be written. Check logfile: ',
    );
    // set logfile name and options
    public function __construct($params=array()) {
        $this->params = array_merge($this->options, $params);
        // set default max logfile size
        $this->maxSize();
    }
    // set logfile max. filesize
    public function maxSize($size=0) {
        if ($size==0) $size=$this->params['maxSize'];
        $this->log_size = $size * (1024 * 1024); // calc megabyt to byte
    }
    // alias functions
    public function debug($message){
        $this->write((string) $message, (int) 100);
    }
    public function info($message){
        $this->write((string) $message, (int) 200);
    }
    public function notice($message){
        $this->write((string) $message, (int) 300);
    }
    public function warning($message){
        $this->write((string) $message, (int) 400);
    }
    public function error($message){
        $this->write((string) $message, (int) 500);
    }
    public function critical($message){
        $this->write((string) $message, (int) 600);
    }
    public function alert($message){
        $this->write((string) $message, (int) 700);
    }
    public function emergency($message){
        $this->write((string) $message, (int) 800);
    }
    public function gau($message){
        $this->write((string) $message, (int) 900);
    }
    // write message to the logfile
    public function write($message, $status) {
        if (is_array($message)) {
            $message = implode(' ', $message);
        }
        // if status is a number set status name
        if (isset($this->level[$status])) {
            $status = $status.' '.$this->level[$status];
        }
        // if file handler doesn't exist, then open logfile
        if (!isset($this->fh) || !is_resource($this->fh)) {
            $this->lopen();
        }
        // use sys log
        if ($this->params['syslog'] == true) {
            $type = 'txt';
            $time = '';
        }else{
            // get logfile type
            $type = strtolower($this->params['format']);
            // define current time for logfile entry - (@ suppress the E_WARNING)
            @ini_set('date.timezone', $this->params['timeZone']); // set timezone
            @date_default_timezone_set($this->params['timeZone']); // set timezone
            $time = @date($this->params['dateFormat']);
        }
        // look for the caller
        if ($this->params['backtrace']==true) {
            $backtrace = debug_backtrace();
            $caller = array_shift($backtrace);
            $proc = $caller['file'].' ('.$caller['line'].')';
        } else {
            $proc = $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        }
        // build logfile entry
        if ($type=='txt') {
            $line = $time."\t".$proc."\t".$message."\t".$status.PHP_EOL;
        } elseif ($type=='csv') {
            $line = $time.','.$proc.','.$message.','.$status.PHP_EOL;
        } elseif ($type=='htm'||'html') {
            $html1 = '<tr><td data-label="Date/Time">';
            $html2 = '</td><td data-label="Programm">';
            $html3 = '</td><td data-label="Message">';
            $html4 = '</td><td data-label="Status">';
            $html5 = '</tag></td></tr>';
            $status= '<tag '.$status.'>'.$status;
            $line  = $html1.$time.$html2.$proc.$html3.$message.$html4.$status.$html5.PHP_EOL;
        }
        // write current entry to the log file
        if ($this->params['syslog'] == true) {
            error_log($line);
        } else {
            flock($this->fh, LOCK_EX);
            fwrite($this->fh, $line) or die($this->error['write'].$this->file);
            flock($this->fh, LOCK_UN);
        }
        // in debug mode print entry also to the browser
        if (defined('DEBUG') == true && DEBUG === true) {
            echo '<pre>'.$line.'</pre>';
        }
    }
    // open logfile (private method)
    private function lopen() {
        // get logfile type
        $type = strtolower($this->params['format']);
        // get logfile path
        $this->path = rtrim($this->params['path'], '\\/');
        // sys log only in txt format
        if ($this->params['syslog'] == true) {
            $type='txt';
        }
        // get default logfile name
        $this->file = $this->path.'/'.$this->params['filename'].'-'.date('Y-m-d').'.'.$type;
        // if logfile is to big, delete it
        if (file_exists($this->file) and isset($this->log_size)) {
            clearstatcache(FALSE, $this->file);
            if (filesize($this->file) > $this->log_size) {
                unlink($this->file);
              }
        }
        // use sys log
        if ($this->params['syslog'] == true) {
            @ini_set('log_errors', 1);
            @ini_set('error_log', $this->file);
            return;
        }
        // if logfile not exist create it
        if (!file_exists($this->file)) {
            $this->fh = fopen($this->file, 'w')
                or die($this->error['openW'].$this->file);
            if ($type=='htm' || $type=='html') {
                if ($html = @file_get_contents('tpl.'.$this->params['template'].'.htm')) {
                    fwrite($this->fh, $html) or die($this->error['write'].$this->file);
                } else {
                    $html = '<html><head><title></title><body><tt><h2>Log</h2><table>';
                    fwrite($this->fh, $html) or die($this->error['write'].$this->file);
                }
            }
            fclose($this->fh);
            if (!is_writable($this->file)) {
                chmod($this->file, $this->params['filePermission']);
            }
        }
        // file exist, so open logfile for appending
        $this->fh = fopen($this->file, 'a')
            or die($this->error['openA'].$this->file);
    }
    // close logfile
    public function __destruct(){
        if ($this->fh) {
            fclose($this->fh);
        }
        elseif ($this->params['syslog'] == true){
            closelog();
        }
    }
}
/* ⣠⣾⣿ EOF - END OF FILE ⣿⣷⣄ */
```

The next step will be to use the logging class from within your PHP code.


## 2. Use of the protocol class

To use the protocol class in your code, you only need to load it when needed. For this purpose, PHP code can access the class as follows All you need to do is put the following code at the beginning of your PHP script:

```php
<?php
require_once __DIR__ . 'log.inc.php';
```

Previously, the protocol class was loaded and thus certain functions were imported. Now you instantiate a new protocol:

```php
$log = new log();
```

**or the same with the transfer of options:**

```php
$options = array(
  'path'           => '.',           // path to the logfile
  'filename'       => 'log',         // main name, _ and date will be added
  'syslog'         => false,         // true = use system function (works only in txt format)
  'filePermission' => 0644,          // or 0777
  'maxSize'        => 0.001,         // in MB
  'format'         => 'htm',         // use txt, csv or htm
  'template'       => 'barecss',     // for htm format only: plain, terminal or barecss
  'timeZone'       => 'UTC',         // UTC or what you like
  'dateFormat'     => 'Y-m-d H:i:s', // see http://php.net/manual/en/function.date.php
  'backtrace'      => true,          // true = slower but with line number of call
);
$log = new log($options);
```

The following options can be named and passed in an array:


  - **path** &mdash; Default: '.'<br>
    z.B. '/path/to/logfile' OR  '.' and the log file is created in the current directory.
  
  - **filename** &mdash; Default: 'log'<br>
    Name of the log file which is supplemented by an underscore '\_' and the date in the format Y-m-d.
  
  - **syslog** &mdash; Default: false<br>
    If this parameter is set to true, the class uses the system log function. This works faster, but only in _txt_ format!
  
  - **filePermission** &mdash; Default: 0644<br>
    Must always be specified in octal format (i.e. with leading 0), e.g. 0777
  
  - **maxSize** &mdash; Default: 10<br>
    Maximum log file size in MB on the same day (before the log file is overwritten)
  
  - **format** &mdash; Default: 'htm'<br>
    You can use 'htm' for HTML format, 'txt' for text format with TAB (\t) separation and 'csv' for **c**omma-**s**eparated **v**alues, i.e. comma separated values with (,) separation. _At the same time the format is used as the file extension of the log file, also for 'txt' and 'csv'._
  
  - **template** &mdash; Default: ''<br>
    The template is only used if the format 'htm' is selected! Possible is here: 'terminal' for (http://terminalcss.xyz) or 'barecss' for (http://barecss.com) or 'plain' for simple HTML.
  
  - **timeZone** &mdash; Default: 'UTC'<br>
    UTC' stands for Universal Time Coordinated, formerly also officially called GMT (Greenwich Mean Time). Here is a list of all other time zones: http://php.net/manual/de/timezones.php
  
  - **dateFormat** &mdash; Default: 'Y-m-d H:i:s'<br>
    All information about the date format can be found here: http://php.net/manual/en/function.date.php
  
  - **backtrace** &mdash; Default: true<br>
    If true, not only the script that wrote the log entry is logged, but also the line number of the call is added in brackets. _This can be very useful for debugging, but the backtrace function slows down the logging process slightly!_



**Naturally, not all parameters have to be set at the same time, but individual values can be used as desired. The default value from the class is then automatically used for all unused ones.**

After initializing the protocol object, the new protocol functions can be used as follows:

```php
$log->debug($logMessage);
$log->info($logMessage);
$log->notice($logMessage);
$log->warning($logMessage);
$log->error($logMessage);
$log->critical($logMessage);
$log->alert($logMessage);
$log->emergency($logMessage);
$log->gau($logMessage); /* tongue-in-cheek ;-) */
```

Or, if you want to set the status value yourself from 100 (debug) to 900 (gau) in steps of 100 (where the severity level increases with the higher digit):

```php
$log->debug($logMessage);
$log->write($logMessage, $status);
```

All in all an example.php file could look like this:

```php
<?php
require_once __DIR__ . 'log.inc.php';

$log=new log();

$logMessage = 'This is the log message';

$log->error($logMessage);
```

**The variable $logMessage can be a string or an array. With an array, the individual values are joined to form a string and are connected with a space.**

There is also a public function available to change the maximum log file size:

```php
// Overrides the options setting and sets the maximum log file size to 10 MB
$log->maxSize(10);
```

This means that some pre-configuration is required, but this procedure makes logging much clearer and easier. In real applications usually a lot of events have to be logged, so this procedure is really very useful.


## 3. Read the log files

If you call the example.php in your browser again, a new log entry is added to your log file.

Here is the original log entry:

```This is the log message```

And here is what the log class produces from it:

```2019-02-03 12:31:06 localhost/example.php (121) This is the log message 500 ERROR```

As you can see, the log class has automatically added the following information to the log entry:


  - Time stamp 
  - Calling script (with line number of the function call)
  - Protocol severity 


**Hint**: Here is a good PHP script to view CSV files online: 
   [PHP CSV Editor](https://codecanyon.net/item/online-csv-editor-php-crud/13213413?ref=adilbo)
   This tool can retrieve and change information stored in CSV (Comma Separated Values) files easily. 

There are already three templates available for the HTML format:

**Template for 'template' => 'plain' Option:**
```html
<!-- File Name: tpl.plain.htm -->
<!DOCTYPE html>
<html>
<head>
<title>Extended Log &mdash; Plain Style</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
:focus {outline:0}
* {background:white;margin:12px;font-family:"Lucida Console",Monaco,"Courier New",Courier,monospace}
td,th{border:1px solid black}
td{padding:7px}
/* STATUS-COLOR-TAGS */
tag            {padding:3px 7px;border-radius:7px}
tag[debug]     {color:black;background:#DFF2BF}
tag[info]      {color:black;background:#BDE5F8}
tag[notice]    {color:black;background:#FEEFB3}
tag[warning]   {color:black;background:#FFCCBA}
tag[error]     {color:black;background:#FFBABA}
tag[critical]  {color:white;background:#D8000C}
tag[alert]     {color:white;background:blueviolet}
tag[emergency] {color:white;background:deeppink}
tag[gau]       {color:white;background:black}
/* TABLE-SORT */
.tablesorter-header { /* COLUMNE HEADER */
 background-repeat:no-repeat;background-position:center right;cursor:pointer
}
.tablesorter-headerUnSorted { /* UNSORTED IMAGE */
 background-image:url(data:image/gif;base64,R0lGODlhFQAJAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAkAAAIXjI+AywnaYnhUMoqt3gZXPmVg94yJVQAAOw)
}
.tablesorter-headerAsc { /* UP IMAGE */
 background-image:url(data:image/gif;base64,R0lGODlhFQAEAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAQAAAINjI8Bya2wnINUMopZAQA7)
}
.tablesorter-headerDesc { /* DOWN IMAGE */
 background-image:url(data:image/gif;base64,R0lGODlhFQAEAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAQAAAINjB+gC+jP2ptn0WskLQA7)
}
.sorter-false { background-image:none !important }
/* RESPONSIVE TABLE */
@media screen and (max-width:600px){thead{display:none}
 tr{display:block;border-bottom:2px solid #ddd}
 td{display:block;text-align:right;font-size:.9em}
 td:before{content:attr(data-label);float:left;font-weight:bold}
}
</style>
<link href="data:image/x-icon;base64,AAABAAEAEBAQAAAAAAAoAQAAFgAAACgAAAAQAAAAIAAAAAEABAAAAAAAgAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAnYBjAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABEREREREQAAERERERERAAAAAAAAAAAAAAAAAAAAAAAAERERERERAAAREREREREAAAAAAAAAAAAAAAAAAAAAAAAREREREREAABEREREREQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD//wAA//8AAP//AADAAwAAwAMAAP//AAD//wAAwAMAAMADAAD//wAA//8AAMADAADAAwAA//8AAP//AAD//wAA" rel="icon" type="image/x-icon" />
<script src="//code.jquery.com/jquery-2.1.1.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.1/js/jquery.tablesorter.min.js"></script>
<script>
 $(document).ready(function(){ $("table").tablesorter({dateFormat:"yyyymmdd"}); });
</script>
</head>
<body>
<section>
<h2>Extended Log</h2>
<table xx>
 <thead>
  <tr>
   <th>Date/Time</th>
   <th>Programm</th>
   <th>Message</th>
   <th>Status</th>
  </tr>
 </thead>
 <tbody>
<!-- </tbody></table></section></body></html> -->
```

**Template for 'template' => 'terminal' Option:**
```html
<!-- File Name: tpl.terminal.htm -->
<!DOCTYPE html>
<html>
<head>
<title>Extended Log &mdash; Terminal Style</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" href="https://unpkg.com/terminal.css@0.4.2/dist/terminal.min.css" />
<style>
:focus {outline:0}
body {background:white;margin:12px}
/* STATUS-COLOR-TAGS */
tag[debug]     {color:black;background:#DFF2BF}
tag[info]      {color:black;background:#BDE5F8}
tag[notice]    {color:black;background:#FEEFB3}
tag[warning]   {color:black;background:#FFCCBA}
tag[error]     {color:black;background:#FFBABA}
tag[critical]  {color:white;background:#D8000C}
tag[alert]     {color:white;background:blueviolet}
tag[emergency] {color:white;background:deeppink}
tag[gau]       {color:white;background:black}
/* TABLE-SORT */
.tablesorter-header { /* COLUMNE HEADER */
 background-repeat:no-repeat;background-position:center right;cursor:pointer
}
.tablesorter-headerUnSorted { /* UNSORTED IMAGE */
 background-image:url(data:image/gif;base64,R0lGODlhFQAJAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAkAAAIXjI+AywnaYnhUMoqt3gZXPmVg94yJVQAAOw)
}
.tablesorter-headerAsc { /* UP IMAGE */
 background-image:url(data:image/gif;base64,R0lGODlhFQAEAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAQAAAINjI8Bya2wnINUMopZAQA7)
}
.tablesorter-headerDesc { /* DOWN IMAGE */
 background-image:url(data:image/gif;base64,R0lGODlhFQAEAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAQAAAINjB+gC+jP2ptn0WskLQA7)
}
.sorter-false { background-image:none !important }
/* RESPONSIVE TABLE */
@media screen and (max-width:600px){thead{display:none}
 tr{display:block;border-bottom:2px solid #ddd}
 td{display:block;text-align:right;font-size:.9em}
 td:before{content:attr(data-label);float:left;font-weight:bold}
}
</style>
<link href="data:image/x-icon;base64,AAABAAEAEBAQAAAAAAAoAQAAFgAAACgAAAAQAAAAIAAAAAEABAAAAAAAgAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAnYBjAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABEREREREQAAERERERERAAAAAAAAAAAAAAAAAAAAAAAAERERERERAAAREREREREAAAAAAAAAAAAAAAAAAAAAAAAREREREREAABEREREREQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD//wAA//8AAP//AADAAwAAwAMAAP//AAD//wAAwAMAAMADAAD//wAA//8AAMADAADAAwAA//8AAP//AAD//wAA" rel="icon" type="image/x-icon" />
<script src="//code.jquery.com/jquery-2.1.1.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.1/js/jquery.tablesorter.min.js"></script>
<script>
 $(document).ready(function(){ $("table").tablesorter({dateFormat:"yyyymmdd"}); });
</script>
</head>
<body>
<section>
<h2>Extended Log</h2>
<table>
 <thead>
  <tr>
   <th>Date/Time</th>
   <th>Programm</th>
   <th>Message</th>
   <th>Status</th>
  </tr>
 </thead>
 <tbody>
<!-- </tbody></table></section></body></html> -->
```

**Template for 'template' => 'barecss' Option:**
```html
<!-- File Name: tpl.barecss.htm -->
<!DOCTYPE html>
<html>
<head>
<title>Extended Log &mdash; Barecss Style</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bare-css@2.0.3/css/bare.min.css" />
<style>
:focus {outline:0}
body {background:white;margin:12px}
/* STATUS-COLOR-TAGS */
tag[debug]     {color:black;background:#DFF2BF}
tag[info]      {color:black;background:#BDE5F8}
tag[notice]    {color:black;background:#FEEFB3}
tag[warning]   {color:black;background:#FFCCBA}
tag[error]     {color:black;background:#FFBABA}
tag[critical]  {color:white;background:#D8000C}
tag[alert]     {color:white;background:blueviolet}
tag[emergency] {color:white;background:deeppink}
tag[gau]       {color:white;background:black}
/* TABLE-SORT */
.tablesorter-header { /* COLUMNE HEADER */
 background-repeat:no-repeat;background-position:center right;cursor:pointer
}
.tablesorter-headerUnSorted { /* UNSORTED IMAGE */
 background-image:url(data:image/gif;base64,R0lGODlhFQAJAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAkAAAIXjI+AywnaYnhUMoqt3gZXPmVg94yJVQAAOw)
}
.tablesorter-headerAsc { /* UP IMAGE */
 background-image:url(data:image/gif;base64,R0lGODlhFQAEAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAQAAAINjI8Bya2wnINUMopZAQA7)
}
.tablesorter-headerDesc { /* DOWN IMAGE */
 background-image:url(data:image/gif;base64,R0lGODlhFQAEAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAQAAAINjB+gC+jP2ptn0WskLQA7)
}
.sorter-false { background-image:none !important }
/* RESPONSIVE TABLE */
@media screen and (max-width:600px){thead{display:none}
 tr{display:block;border-bottom:2px solid #ddd}
 td{display:block;text-align:right;font-size:.9em}
 td:before{content:attr(data-label);float:left;font-weight:bold}
}
</style>
<link href="data:image/x-icon;base64,AAABAAEAEBAQAAAAAAAoAQAAFgAAACgAAAAQAAAAIAAAAAEABAAAAAAAgAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAnYBjAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABEREREREQAAERERERERAAAAAAAAAAAAAAAAAAAAAAAAERERERERAAAREREREREAAAAAAAAAAAAAAAAAAAAAAAAREREREREAABEREREREQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD//wAA//8AAP//AADAAwAAwAMAAP//AAD//wAAwAMAAMADAAD//wAA//8AAMADAADAAwAA//8AAP//AAD//wAA" rel="icon" type="image/x-icon" />
<script src="//code.jquery.com/jquery-2.1.1.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.1/js/jquery.tablesorter.min.js"></script>
<script>
 $(document).ready(function(){ $("table").tablesorter({dateFormat:"yyyymmdd"}); });
</script>
</head>
<body>
<section>
<h2>Extended Log</h2>
<table xx>
 <thead>
  <tr>
   <th>Date/Time</th>
   <th>Programm</th>
   <th>Message</th>
   <th>Status</th>
  </tr>
 </thead>
 <tbody>
<!-- </tbody></table></section></body></html> -->
```

And the best thing is, you don't have to take care of all these things yourself, it happens automatically.

## 4. Check the log file class

With the following script and test data the PHP Log-File class can be thoroughly tested:

**test.php Script:**
```php
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
```

**test_messages.txt Test-Data:**
```text
Continue
Switching Protocols
Processing
OK
Created
Accepted
Non-Authoritative Information
No Content
Reset Content
Partial Content
Multi-Status
Already Reported
IM Used
Multiple Choices
Moved Permanently
Found
See Other
Not Modified
Use Proxy
Switch Proxy
Temporary Redirect
Permanent Redirect
Bad Request
Unauthorized
Payment Required
Forbidden
Not Found
Method Not Allowed
Not Acceptable
Proxy Authentication Required
Request Timeout
Conflict
Gone
Length Required
Precondition Failed
Request Entity Too Large
Request-URI Too Long
Unsupported Media Type
Requested Range Not Satisfiable
Expectation Failed
I'm a teapot (April fool's joke)
Authentication Timeout
Enhance Your Calm
Method Failure
Unprocessable Entity
Locked
Failed Dependency
Method Failure
Unordered Collection
Upgrade Required
Precondition Required
Too Many Requests
Request Header Fields Too Large
No Response
Retry With
Blocked by Windows Parental Controls
Redirect
Unavailable For Legal Reasons
Request Header Too Large
Cert Error
No Cert
HTTP to HTTPS
Client Closed Request
Internal Server Error
Not Implemented
Bad Gateway
Service Unavailable
Gateway Timeout
HTTP Version Not Supported
Variant Also Negotiates
Insufficient Storage
Loop Detected
Bandwidth Limit Exceeded
Not Extended
Network Authentication Required
Network read timeout error
Network connect timeout error
```

With this script (test.php) and the test data (test_messages.txt) a test log can be created and all options can be tried out comfortably. Have fun with it!


## Conclusion

I have explained the basics of file logging in PHP, which should be enough to get you started. Nevertheless, you might want to look for more information to deepen your understanding.

If you want to use my log class (which you are very welcome to do), you better invest some time to test the functionality. This PHP logging class is extremely configurable, so it should basically cover most logging needs.

But of course I am very grateful for any suggestions regarding this article, e.g. as a comment! 

Since applications usually increase in size and complexity during their lifetime, log files should not be avoided under any circumstances. Without log files, it could otherwise be difficult to find errors in an application in live operation.

So be sure to use file logging in your PHP projects! And remember: You can suspect many causes of errors, you usually only know the exact cause after a look into the log files. With assumptions you are usually more wrong than you think. But for this you should write a log entry at all relevant places!

