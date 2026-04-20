<?php

/*
 * Copyright (C) 2013 Michael Hegenbarth (carschrotter) <mnh@mn-hegenbarth.de>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

namespace mnhcc\ml\classes {
    
    use \mnhcc\ml\traits,
	\mnhcc\ml\classes\BootstrapHandler as BH;
    /**
     * Description of Error
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     * @copyright (c) 2012, Michael Hegenbarth
     */
    class Error extends MNHcC {

	use traits\Instances;

	const ERROR = E_ERROR;
	const WARNING = E_WARNING;
	const NOTICE = E_NOTICE;
	const STRICT = E_STRICT;
	const DEPRECATED = E_DEPRECATED;
	const EXCEPTION = -1;
	const RAISE_USE_TEMPLATE = '{"RAISEUSETEMPLATE":true,"secure":"Ay0keRT1l8"}';
	const RAISE_ERROR = '{"RAISEERROR":true,"secure":"Ay0keRT1l8"}';

	
	protected static $_templateParms = ['~baseurl~' => '/', '~heading~' => '<h1>Error 500</h1>'];
	/**
	 * the default template for shutdown event
	 * @var string 
	 */
	protected static $template = <<<EOF
<!DOCTYPE html><!-- XHTML 5 -->
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" dir="ltr">
<haed>
	<link rel="stylesheet" href="~baseurl~assets/css/bootstrap.css" type="text/css" />
	<link rel="stylesheet" href="~baseurl~assets/css/override.css" type="text/css" />
	<link rel="stylesheet" href="~baseurl~assets/css/error.css" type="text/css" />
	<link rel="stylesheet" href="~baseurl~assets/css/debug.css" type="text/css" />
</haed>
<body id="error">
	<div class="container error">
		<div class="row">
			<div class="span12 box-outline" id="main-error-box">
				<div class class="box-header">
				~heading~
				</div>
				<div class="box-body">
				%s
				<p>
				<span style="color:inherit; font-weight:900;">Last Error:</span>
				<br /> %s <br />
				</p>
				</div>
			</div>
		</div>
	</div>
	<div id="placeholder">Error</div>
</body>
</html>
EOF;
	/**
	 * which were "bugs" already displayed?
	 * @var bool 
	 */
	protected $_isDisplayedBugs = false;
	
	/**
	 * protection against empty page (blank creen).
	 * @var type 
	 */
	protected $_blankScreenProtection = true;
	
	/**
	 * array of all errors in exception form
	 * @var array
	 */
	private $_exceptions = array();

	/**
	 * Value is true when is json vormat
	 * @var type
	 */
	protected $_isJson = false;

	/**
	 * responsecode (Errorstate)
	 * @var int 
	 */
	protected $_code = 0;
	
	/**
	 *
	 * @var array 
	 */
	protected $_errorHash = [];
	
	public function isDisplayedBugs() {
	    return $this->_isDisplayedBugs;
	}

	protected static function renderTemplate() {
	    $args = func_get_args();
	    $template = self::$template;

	    foreach (self::$_templateParms as $key => $value) {
		$template = str_replace($key, $value, $template);
	    }

	    \array_unshift($args, $template);
	    $template = call_user_func_array('\\sprintf', $args);

	    return $template;
	}

	/**
	 * set the responsecode (Errorstate)
	 * @param int $code
	 * @return \mnhcc\ml\classes\Error
	 */
	protected function _setCode($code) {
	    if ($this->_code < $code) {
		$this->_code = $code;
	    }
	    return $this;
	}

	/**
	 * get the responsecode (Errorstate)
	 * @return int
	 */
	public function getCode() {
	    return $this->_code;
	}

	/**
	 * render infos for json
	 * @param bool $isJson
	 * @return bool
	 */
	public function isJson($isJson = null) {
	    if ($isJson !== null)
		$this->_isJson = $isJson;
	    return $this->_isJson;
	}

	public function __construct() {
	    $this->parms = Router::getInstance()->getParm();
	    $this->_blankScreenProtection = (bool) $this->parms->get('blankScreenProtection', 1);
	    if ($this->parms->get('enabeled', 1)) {
		register_shutdown_function([$this, 'shutdown']);
		set_error_handler([$this, 'handleError']);
		set_exception_handler([$this, 'handleException']);
	    }
	    if ($this->_blankScreenProtection) {
		ob_start();
		ob_implicit_flush(false);
	    }
	    EventManager::register(new Event([self::getCalledClass(), 'onTemplateCreated'], 'onTemplateCreated'));
	}

	public function __destruct() {
	    restore_error_handler();
	    restore_exception_handler();
	    if (!$this->_blankScreenProtection) {
		return;
	    }
	    $contents = ob_get_contents();
	    $baseurl = '/';
	    if ($contents == '') {
		$message = '';
		$exception = $this->getLastException();
		if (is_object($exception)) {
		    $message = $exception->getMessage();
		}
		if (Helper::classExists('SERVER', true)) {
		    self::$_templateParms['~baseurl~'] = SERVER::getBase();
		}
		echo self::renderTemplate('The program was completed without spending a content.', $message);
	    }
	    $contents = ob_get_contents();
	    ob_end_clean();
	    echo $this->documentPrepare($contents);
	}

	public function documentPrepare($doc) {
	    if (Helper::classExists('Bootstrap', true, false)) {
		if (Bootstrap::isDebug()) {
		    if ($this->isJson()) {
			return $this->documentPrepareJSON($doc);
		    } else {
			return $this->documentPrepareHTML($doc);
		    }
		}
	    }
	    return $doc;
	}

	public function documentPrepareJSON($doc) {
	    $exeptions = ', ' . ltrim(json_encode(['bugcatcher' => $this->_exceptions]), '{');
	    return rtrim($doc, '}') . $exeptions;
	}

	/**
	 * add the bugcatcher console to the document
	 * @param string $doc the document
	 * @return string the prepared document
	 */
	public function documentPrepareHTML($doc) {
	    $html = '';
	    if (!$this->isDisplayedBugs()) {
		$html = $this->displayBugs();
		$html .= "<script  type=\"text/javascript\">
                    function toggleContainer(name) {
                    var e = document.getElementById(name);// old scool. jqery might not be available ;)
                    e.style.height = (e.style.height == '0px') ? 'auto' : '0px';
                    }
                    </script>";
	    }
	    $msg = '';
	    if(strpos($doc, '</body>') === false) {
		if (Helper::classExists('SERVER', true)) {
		    self::$_templateParms['~baseurl~'] = SERVER::getBase();
		}
		$msg ='<pre class="invalidContent">'.$doc.'</pre>';
		$doc =  self::renderTemplate('The program was completed without spending valid html.', '');
	    }
	    $doc = str_replace('</body>', n . $html . n . '</body>', $doc);
	    $doc = str_replace('<div id="placeholder">Error</div>',$msg, $doc);
	    
	    return $doc;
	}

	/**
	 * 
	 * @param int $code
	 * @param string $message
	 * @param string $log
	 * @param \mnhcc\ml\classes\Template $template
	 * @return mixed on succes Error::USETEMPLATE on error RAISEERROR 
	 */
	public function raise($code, $message = '', $log = '', $header = []) {
	    $this->_setCode($code);
	    $template = (Helper::classExists('Template', true) && Template::isInit()) ? Template::getInstance() : null;
	    $logmsg = 'Enabele debug to show this message.';
	    if (Helper::classExists('Router', true, false)) {
		if (Router::isDebug()) {
		    $logmsg = $log;
		    $logmsg = ($logmsg) ? $logmsg : $message->getMessage() . ': in file '
		    . $message->getFile()
		    . ' on line '
		    . $message->getLine();
		}
	    }
	    if(\is_object($message) && $message instanceof \Exception){
		$this->_exceptions[] = $message;
	    }
	    switch ($code) {
		case 505:
		    if (Helper::classExists('Router', true, false)) {
			Router::header(505);
		    }
		    if (Helper::classExists('SERVER', true, false)) {
			self::$_templateParms['~baseurl~'] = SERVER::getBase();
		    }
		    die($this->documentPrepare(self::renderTemplate($message, $logmsg)));
		    break;
		case 404: case 403: default:
		    Router::header($code);
		    if ($template != null) {
			$template->error($code, $message);
			return self::RAISE_USE_TEMPLATE;
		    } else {
			if(Helper::classExists('SERVER', true)){
			    self::$_templateParms['~baseurl~'] = SERVER::getBase();
			}
			die($this->documentPrepare(self::renderTemplate($message, $logmsg)));
		    }
		    break; 
	    }
	    return self::RAISE_ERROR;
	}

	/**
	 * @see \trigger_error()
	 * <b>Generates a user-level error/warning/notice message</b>
	 * @param string $error_msg <p>
	 * The designated error message for this error. It's limited to 1024
	 * bytes in length. Any additional characters beyond 1024 bytes will be
	 * truncated.
	 * </p>
	 * @param int $error_type [optional] <p>
	 * The designated error type for this error. It only works with the E_USER
	 * family of constants, and will default to <b>E_USER_NOTICE</b>.
	 * </p>
	 * @return bool This function returns <b>FALSE</b> if wrong <i>error_type</i> is
	 * specified, <b>TRUE</b> otherwise.
	 */
	public static function triggerError($error_msg, $error_type = E_USER_NOTICE) {
	    switch (self::getType($error_type)) {
		case self::DEPRECATED :
		    $error_type = E_USER_DEPRECATED;
		    break;
		case self::ERROR :
		    $error_type = E_USER_ERROR;
		    break;
		case self::NOTICE :
		    $error_type = E_USER_NOTICE;
		    break;
		case self::WARNING :
		    $error_type = E_USER_WARNING;
		    break;

		default:
		    break;
	    }
	    return \trigger_error($error_msg, $error_type);
	}

	public function shutdown($exception = null) {

	    if ($exception === null) {
		$exception = $this->getLastException(true);
	    }
	    if (!\is_object($exception)) {
		return exit();
	    }
	    if (Helper::classExists('EventManager', true, false)) {
		EventManager::raise('shutdown', new EventParms\ExceptionEventParms(['exception' => $exception])) ;
	    }
	    $this->isJson(false);
	    if (self::isExit($exception->getCode())) {
		ob_clean();	
		$this->raise(505,
			'<span style="color:inherit; font-weight:900;">Shutdown with BugCatcher :-(</span><br />', 
			$exception->getMessage() . ' in '
			    . str_replace(ROOT_PATH, 'ROOT_PATH', $exception->getFile()) . ' on line '
			    . $exception->getLine()
		);
	    }
	    exit(0);
	}

	public static function getType($code) {
	    $type = null;
	    switch ($code) {
		case 1:
		case E_ERROR:
		case E_USER_ERROR:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
		case E_RECOVERABLE_ERROR:
		    $type = self::ERROR;
		    break;
		case E_WARNING:
		case E_CORE_WARNING:
		case E_USER_WARNING:
		case E_COMPILE_WARNING :
		    $type = self::WARNING;
		    break;
		case E_NOTICE:
		case E_USER_NOTICE:
		    $type = self::NOTICE;
		    break;
		case E_DEPRECATED:
		case E_USER_DEPRECATED:
		    $type = self::DEPRECATED;
		    break;
		case E_STRICT :
		    $type = self::STRICT;
		    break;
		case self::EXCEPTION:
		default :
		    $type = self::EXCEPTION;
		    break;
	    }
	    return $type;
	}

	/**
	 * 
	 * @param int $code the error code
	 * @return bool
	 */
	public static function isExit($code) {
	    $type = self::getType($code);
	    return (bool) ($type == self::ERROR || $type == self::EXCEPTION);
	}

	/**
	 * @return \Exception
	 */
	function getLastException() {
	    $test = function($exeption, $error) {
		return (
			($exeption->getMessage() == $error['message']) &&
			($exeption->getFile() == $error['file']) &&
			($exeption->getLine() == $error['line'])
			);
	    };
	    $last = error_get_last();
	    if ($last){
		$this->handleError($last['type'], $last['message'], $last['file'], $last['line']);
	    }
	    if (count($this->_exceptions) > 0) {
		$index = (count($this->_exceptions) - 1);
		if ($test($this->_exceptions[$index], $last)) { //check error on Helper and co
		    return $this->_exceptions[$index];
		} else {
		    $_exceptions = array_reverse($this->_exceptions);
		    foreach ($_exceptions as $i => $exeption) {
			if ($test($exeption, $last))
			    return $exeption;
		    }
		}
	    } else {
		return null;
	    }
	}

	/**
	 * 
	 * @param \Exception $e
	 */
	protected function log(\Exception $e) {
	    if (Helper::classExists('Config', true, false)) {
		Config::getInstance()->get('errror.log', false);
	    } else {
		$logfile = ( ini_get('error_log') ) ? ini_get('error_log') : './php-error.log.php';
		$date = new \DateTime();
		\error_log(
			self::logFormat(
				$date->format('Y-m-d H:i:s'), $date->getTimezone()->getName(), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace()
			), 3, $logfile);
	    }
	}

	public static function logFormat($time, $timezone, $message, $file, $line, array $trace = []) {
	    $message = "[$time $timezone] PHP MNHcCError "
		    . $message
		    . ' in '
		    . $file
		    . " on line $line." . PHP_EOL.PHP_EOL;
	    foreach ($trace as $i => $itrace) {
		if ($i === 1) {
		    $message .= "[$time $timezone] PHP Stack trace:" . PHP_EOL.PHP_EOL;
		}
		$message .= "[$time $timezone] PHP " 
			. ArrayHelper::get('function', $itrace, '{main}') . '() ' 
			. ArrayHelper::get('file', $itrace, 'eval')  . ':' . ArrayHelper::get('line', $itrace, 0) . PHP_EOL . PHP_EOL;
	    };
	    return $message;
	}

	/**
	 * @param \Exception $exception
	 * @return boolean
	 */
	public function handleException($exception) {
	    $this->_exceptions[] = $exception;
	    EventManager::raise('exception', new EventParms\ExceptionEventParms(['exception' => $exception]));
	    self::log($exception);
	    return true;
	}

	/**
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param string $errline
	 * @param mixed $errcontext
	 * @return boolean
	 */
	public function handleError($errno, $errstr, $errfile, $errline, $errcontext = NULL) {
	    try {
		self::throwErrorException($errno, $errstr, $errfile, $errline);
	    } catch (\Exception $exc) {
		return $this->handleException($exc); //return the result handleException to disable the php error reporting
	    }
	}

	/**
	 * 
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param string $errline
	 * @throws \ErrorException
	 */
	public static function throwErrorException($errno, $errstr, $errfile, $errline) {
	    if(Helper::classExists('Exception\\ErrorException', true, true)) {
		throw new Exception\ErrorException($errstr, $errno, $errno, $errfile, $errline);
	    } else {
		throw new \ErrorException($errstr, $errno, $errno, $errfile, $errline);
	    }
	}

	private function param($name, $default = NULL) {
	    return $this->params->get($name, $default);
	}

	protected function displayBugs() {
	    $this->_isDisplayedBugs = true;
	    $count = count($this->_exceptions);
	    $js = "toggleContainer('dbg-content-bugcatcher-$count');";
	    $html = '<div class="container" >'
		     . ' <div class="row">' . n
		    . '     <div class="span12">' . n
		    . '		<div class="accordion" id="mnhcc-bugcatcher-debug">' . n
		    . '		    <div class="accordion-group">' . n
		    . '			<div class="accordion-heading dbg-header" data-toggle="collapse" data-parent="#mnhcc-bugcatcher-debug" onclick="' . $js . '">' . n
		    . '			    <a class="accordion-toggle" href="#mnhcc-bugcatcher-debug">' . n
		    . '				Bug Catcher [' . $count . ']' . n
		    . '			    </a>' . n
		    . '			</div>' . n
		    . '			<div id="dbg-content-bugcatcher-' . $count . '" class="accordion-body collapse dbg-container" style="height:0px;">' . n;
	    $i = 0;
	    $limit = 500;
	    foreach ($this->_exceptions As $exception) {
		$i++;
		if ($i > $limit) {
		    $html .= '<b>more as ' . $limit . ' errors is to mutch! the list ist break</b>' . n;
		    break;
		}
		$memoryUsage = new Bytes(memory_get_usage());
		$memoryLimit = new Bytes(ini_get('memory_limit'));
		if ($memoryUsage->toFloat() < ($memoryLimit->toFloat() - 128)) {
		    $has = \md5(\htmlentities(\json_encode($exception)));
		    if( $this->addErrorHash($has) < 2) {
			$html .= $this->renderError($exception, $has);
		    }
		} else {
		    $Bytes = new Bytes(memory_get_usage());
		    $html .= '<b>' . $memoryUsage->getUfriendlySize() . ' is to mutch! ' . $memoryLimit->getUfriendlySize() . ' is The Limit!</b>' . n;
		    break;
		}
	    }
	    $html = str_replace(array_keys($this->_errorHash), $this->_errorHash, $html);
	    
	    $memoryUsage = new Bytes(memory_get_usage());
	    $memoryLimit = new Bytes(ini_get('memory_limit'));
	    $runtime = 'not enabeled';
	    if(Bootstrap::defined('STARTTIME')){
		$runtime = microtime(true) - Bootstrap::constant('STARTTIME');
	    } 
	    $html .= '<div><code>Runtime: ' . $runtime . ' ' . $memoryUsage->getUfriendlySize() . ' from max ' . $memoryLimit->getUfriendlySize() . '</code>';
	    $html .= '</div></div></div></div></div></div>';
	    return $html;
	}
	
	protected function addErrorHash($hash) {
	    if(ArrayHelper::keyExists($hash, $this->_errorHash)) {
		$this->_errorHash[$hash] = $this->_errorHash[$hash]++;
	    } else {
		$this->_errorHash[$hash] = 1;
	    }
	    return $this->_errorHash[$hash];
	}
	
	/**
	 * @staticvar string $html
	 * @staticvar int $id
	 * @param Exception $exception
	 * @return string
	 */
	protected function renderError($exception, $has) {
	    static $id;
	    if ($id === null){$id = 1;}
	    $html = '';
	    $js = "toggleContainer('dbgContainer_BugCatcher" . $id . "');";
	    $style = ' style="height: 0px;"';
	    $errorType = (is_a($exception, 'ErrorException')) ? self::FriendlyErrorType($exception->getCode()) : get_class($exception);
	    $html .= '          <div class="dbgHeader" onclick="' . $js . '">' . n
		    . '           <a href="javascript:void(0);">' . n
		    . '               <h3 title="' . self::FriendlyErrorType($exception->getCode()) . ' in ' . $exception->getFile() . '">       ' . n
		    . '['.$has.'] '
		    . $exception->getMessage() . n
		    . '               </h3>' . n
		    . '           </a>' . n
		    . '          </div>' . n;
	    $html .= '          <div ' . $style . ' class="dbgContainer" id="dbgContainer_BugCatcher' . $id . '">' . n
		    . '           <p class="' . Helper::cssNameClean($errorType) . ' alert alert-info">' . n
		    . '               <b>[' . $errorType . '] </b>'
		    . $exception->getMessage() . ': in file '
		    . $exception->getFile()
		    . ' on line '
		    . $exception->getLine()
		    . '               <br /><br /><br />' . n
		    . '            </p>' . n;
	    $html .= static::renderBacktrace($exception->getTrace());
	    $html .= '          </div>' . n;
	    $id++;
	    return str_replace(ROOT_PATH, 'ROOT_PATH', $html);
	}

	public static function renderBacktrace(array $trace) {
	    $str = '<table cellpadding="0" cellspacing="0" class="table backtrace-table">' . n
		    . ' <thead>' . n
		    . '     <tr>' . n
		    . '         <th colspan="3" class="TD"><strong>Call stack</strong></th>' . n
		    . '     </tr>' . n
		    . '     <tr>' . n
		    . '         <th class="TD"><strong>#</strong></th>' . n
		    . '         <th class="TD"><strong>Function</strong></th>' . n
		    . '         <th class="TD"><strong>Location</strong></th>' . n
		    . '     </tr>' . n
		    . ' </thead>' . n
		    . ' <tbody>' . n;
	    foreach ($trace as $key => $trace) {
		$str .= static::renderTraceArray($trace, $key + 1);
	    }
	    $str .= '   </tbody>' . n
		    . '</table>' . n;
	    return $str;
	}

//        public static function renderTraceArray($trace) {
//            return '<pre>' . print_r($trace, true) . '</pre>';
//        }

	public static function renderTraceArray($trace, $key = 0) {
	    $highlight = [];
	    $highlight['keyword'] = ini_get('highlight.keyword') ? ini_get('highlight.keyword') : '#007700';
	    $highlight['comment'] = ini_get('highlight.comment') ? ini_get('highlight.comment') : '#FF8000';
	    $highlight['default'] = ini_get('highlight.default') ? ini_get('highlight.default') : '#0000BB';
	    $highlight['html'] = ini_get('highlight.html') ? ini_get('highlight.html') : '#000000';
	    $highlight['string'] = ini_get('highlight.string') ? ini_get('highlight.string') : '#DD0000';
	    $args = '';
	    if (isset($trace['args'])) {
		$args .= '<ol>';
		foreach ($trace['args'] as $arg) {
		    try {
			$args .= '<li>' . str_replace(['<pre', '</pre'], ['<code', '</code'], Helper::dump($arg)) . '</li>';
		    } catch (\Exception $ex) {
			$args .= '<li>' . str_replace(['<pre', '</pre'], ['<code', '</code'], $ex->getMessage()) . '</li>';
		    }
		}
		$args .= '</ol>';
	    }


	    $contents = '     <tr>' . n
		    . '         <td class="TD">' . $key . '</td>' . n;
	    if (isset($trace['class'])) {
		$contents .= 
			'         <td class="TD" style="color:' . $highlight['default'] . '">' 
			. $trace['class'] 
			. '<span style="color:' . $highlight['keyword'] . '">' 
			. $trace['type'] 
			. '</span>' 
			. $trace['function'] 
			. '<span style="color:' . $highlight['keyword'] . '">(' . $args . ')</span></td>' . n;
	    } else {
		$contents .= 
			'         <td class="TD" style="color:' . $highlight['default'] . '">' 
			. $trace['function'] 
			. '<span style="color:' . $highlight['keyword'] . '">(' . $args . ')</span></td>' . n;
	    }
	    if (isset($trace['file'])) {
		$contents .= 
			'         <td class="TD" style="color:' . $highlight['string'] . '">' 
			. $trace['file'] . ':' . $trace['line'] 
			. '</td>' . n;
	    } else {
		$contents .= 
			'         <td class="TD">&#160;</td>' . n;
	    }
	    $contents .= '     </tr>' . n;
	    return $contents;
	}

	public static function FriendlyErrorType($type) {
	    switch ($type) {
		case E_ERROR: // 1 //
		    return 'E_ERROR';
		case E_WARNING: // 2 //
		    return 'E_WARNING';
		case E_PARSE: // 4 //
		    return 'E_PARSE';
		case E_NOTICE: // 8 //
		    return 'E_NOTICE';
		case E_CORE_ERROR: // 16 //
		    return 'E_CORE_ERROR';
		case E_CORE_WARNING: // 32 //
		    return 'E_CORE_WARNING';
		case E_CORE_ERROR: // 64 //
		    return 'E_COMPILE_ERROR';
		case E_CORE_WARNING: // 128 //
		    return 'E_COMPILE_WARNING';
		case E_USER_ERROR: // 256 //
		    return 'E_USER_ERROR';
		case E_USER_WARNING: // 512 //
		    return 'E_USER_WARNING';
		case E_USER_NOTICE: // 1024 //
		    return 'E_USER_NOTICE';
		case E_STRICT: // 2048 //
		    return 'E_STRICT';
		case E_RECOVERABLE_ERROR: // 4096 //
		    return 'E_RECOVERABLE_ERROR';
		case E_DEPRECATED: // 8192 //
		    return 'E_DEPRECATED';
		case E_USER_DEPRECATED: // 16384 //
		    return 'E_USER_DEPRECATED';
	    }
	    return $type;
	}
	
	/**
	 * 
	 * @param \mnhcc\ml\classes\EventParms $eventArgs
	 */
	public static function onTemplateCreated(template\EventParms $eventArgs) {
	    $template = $eventArgs->getTemplate();
	    if( method_exists($template, 'addStyle') ) {
		$template->addStyle('error');
		$template->addStyle('debug');
	    }
	}
	
	public static function ___require() {
	    return parent::___require();
	}

	public static function ___onLoaded() {
	    self::$___require = [
		BH::TYPE_CLASS => BH::addRootNamespace('ArrayHelper'),
		BH::TYPE_CLASS => BH::addRootNamespace('Helper'),
		BH::TYPE_CLASS => __NAMESPACE__ .'\\Bytes'];
	    
	    return BH::Load('Helper', true, BH::TYPE_CLASS) &&
		BH::Load('ArrayHelper', true, BH::TYPE_CLASS) &&
		BH::Load('Bytes', true, BH::TYPE_CLASS) &&
		BH::Load('Router', false, BH::TYPE_CLASS) &&
		BH::Load('Bootstrap', false, BH::TYPE_CLASS);
	}
	
//	public function renderBacktrace($exception) {
//		return '<pre>'.htmlentities(print_r($exception, true)).'</pre>';
//	}
    }

}