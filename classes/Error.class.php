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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap-theme.min.css">
    <style>body { padding-top: 70px; padding-bottom: 40px; }</style>
</head>
<body>
<nav class="navbar navbar-default navbar-fixed-top">
    <div class="container">
        <div class="navbar-header">
            <a class="navbar-brand" href="~baseurl~">Minimalus Layoutilus</a>
        </div>
    </div>
</nav>
<div class="container">
    ~heading~
    <div class="alert alert-danger">
        %s
        <p><strong>Last Error:</strong><br />%s</p>
    </div>
</div>
<div id="placeholder">Error</div>
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
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
	 * Soft / reported exceptions — non-fatal failures handed to the
	 * framework via `report()`.  Always written to the framework log;
	 * additionally rendered into the BugCatcher overlay when DEBUG is on
	 * so a developer notices a partial failure during a render that
	 * otherwise looks fine in production.
	 *
	 * @var \Exception[]
	 */
	protected $_softExceptions = array();

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

	/**
	 * Render the BugCatcher default-fallback HTML page.
	 *
	 * The inner template (self::$template) holds two `%s` sprintf slots for
	 * the main message and the "Last Error" detail plus `~baseurl~` and
	 * `~heading~` placeholders that are str_replaced from
	 * self::$_templateParms.  The result is a self-contained HTML document
	 * with Bootstrap CSS/JS from cdn — no Programm, no Template, no
	 * PageConfig dependency, so it survives any framework state.
	 *
	 * Public so that TemplateHtml::_renderInlineFallback can reuse it as
	 * a recursion-safety net when the user's own template throws during
	 * render (otherwise we'd duplicate the markup).
	 *
	 * @param  string $message    HTML to embed in the alert body.  Optional.
	 * @param  string $lastError  Plain-text "Last Error" line.  Optional.
	 * @return string             A complete HTML document.
	 */
	public static function renderTemplate() {
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
		$html  = $this->displayBugs();
		$html .= self::_overlayScript();
		$html .= self::_overlayStyles();
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
	 * Report a non-fatal exception.
	 *
	 * Always written to the framework log via {@see log()}.  When DEBUG is
	 * defined and truthy, additionally collected for the BugCatcher overlay
	 * — so a developer running with DEBUG=1 sees a partial filter / module /
	 * pipeline failure that would otherwise pass silently in production.
	 *
	 * Use this for recoverable failures where the surrounding render must
	 * keep going (one filter blew up, the rest of the pipeline still runs)
	 * — never for genuinely uncaught exceptions, which belong on
	 * {@see handleException()} so the shutdown handler can route them as
	 * 5xx responses.
	 *
	 * @param  \Exception $exception
	 * @return void
	 */
	public function report(\Exception $exception) {
	    $this->log($exception);
	    if (defined('DEBUG') && DEBUG) {
		$this->_softExceptions[] = $exception;
	    }
	}

	/**
	 * Read-only access to the soft / reported exceptions registry.
	 *
	 * @return \Exception[]
	 */
	public function getSoftExceptions() {
	    return $this->_softExceptions;
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
	    // Wrap in our own container instead of Bootstrap's `.container` so
	    // the overlay always renders full-width with our own background and
	    // text colours regardless of the host page's CSS.  See
	    // _overlayStyles() for the actual rules.
	    $html = '<div class="bugcatcher-overlay">'
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

	    // Soft (reported) exceptions — rendered as a sibling block so the
	    // primary chain stays uncluttered.  Hidden when none have been
	    // reported, so production renders without DEBUG see nothing.
	    $html .= $this->_renderSoftExceptions();

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

	/**
	 * Render the soft / reported exceptions panel (DEBUG-only block at the
	 * bottom of the overlay).  Each reported exception gets its own header
	 * + collapsible backtrace, styled with the .reported modifier so it is
	 * visually distinct from the primary exception chain.
	 *
	 * @return string  Empty string when no soft exceptions have been
	 *                 collected — keeps the overlay tidy in the no-fault
	 *                 case.
	 */
	protected function _renderSoftExceptions() {
	    if (empty($this->_softExceptions)) {
		return '';
	    }
	    static $softId;
	    if ($softId === null) { $softId = 1; }

	    $count = count($this->_softExceptions);
	    $html  = '<div class="dbgHeader reported">'
		   . 'Reported (non-fatal) [' . $count . ']'
		   . '</div>';
	    foreach ($this->_softExceptions as $exception) {
		$id = 'dbg-soft-' . $softId++;
		$html .= $this->_renderExceptionFrame($exception, 'Reported:', $id, false);
	    }
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
	 * Render a single exception plus its full getPrevious() chain into the
	 * BugCatcher debug overlay.
	 *
	 * The primary exception gets the original-style header.  Each chained
	 * predecessor gets a "Caused by:" prefix and its own collapsible
	 * dbgContainer with its own backtrace.  Per-frame toggle IDs are unique
	 * across calls so multiple stacked exceptions never share a container.
	 *
	 * @staticvar int $id
	 * @param  \Exception $exception  The primary exception (caller passes a
	 *                                duplicate-check hash in $has).
	 * @param  string     $has        Hash for the primary, used in the header tag.
	 * @return string
	 */
	protected function renderError($exception, $has) {
	    static $id;
	    if ($id === null) { $id = 1; }

	    $html    = '';
	    $current = $exception;
	    $depth   = 0;
	    while ($current !== null) {
		// Reuse the primary $has so the dedup label stays stable in the
		// header; previous-frames get a derived label for visual context.
		$frameLabel = ($depth === 0) ? $has : ($has . '.' . $depth);
		$html .= $this->_renderExceptionFrame($current, $frameLabel, $id, $depth === 0);
		$id++;
		$current = $current->getPrevious();
		$depth++;
	    }

	    return str_replace(ROOT_PATH, 'ROOT_PATH', $html);
	}

	/**
	 * Build the HTML for one exception frame in the BugCatcher overlay.
	 *
	 * @param  \Exception $exception
	 * @param  string     $label     Header label (hash for primary, derived for chain).
	 * @param  int        $id        Unique toggle id for this frame.
	 * @param  bool       $isPrimary False for previous-chain frames.
	 * @return string
	 */
	protected function _renderExceptionFrame($exception, $label, $id, $isPrimary) {
	    $js        = "toggleContainer('dbgContainer_BugCatcher" . $id . "');";
	    $style     = ' style="height: 0px;"';
	    $errorType = (is_a($exception, 'ErrorException'))
		? self::FriendlyErrorType($exception->getCode())
		: get_class($exception);
	    $headerCss = 'dbgHeader' . ($isPrimary ? '' : ' caused-by');
	    $causedBy  = $isPrimary ? '' : 'Caused by: ';

	    $html  = '          <div class="' . $headerCss . '" onclick="' . $js . '">' . n
		   . '           <a href="javascript:void(0);">' . n
		   . '               <h3 title="' . self::FriendlyErrorType($exception->getCode())
		   . ' in ' . $exception->getFile() . '">       ' . n
		   . '['.$label.'] ' . $causedBy
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
	    return $html;
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
	
	/**
	 * Inline JS for the BugCatcher overlay — toggles a single dbgContainer
	 * between collapsed (height: 0) and expanded (height: auto).  Vanilla,
	 * jQuery-free, IE11-compatible.
	 *
	 * @return string  An HTML <script> block.
	 */
	protected static function _overlayScript() {
	    return n . '<script type="text/javascript">' . n
		 . 'function toggleContainer(name) {' . n
		 . '    var e = document.getElementById(name);' . n
		 . '    if (!e) { return; }' . n
		 . '    if (e.style.height === \'0px\') {' . n
		 . '        e.style.height = \'auto\';' . n
		 . '        e.style.display = \'block\';' . n
		 . '    } else {' . n
		 . '        e.style.height = \'0px\';' . n
		 . '        e.style.display = \'\';' . n
		 . '    }' . n
		 . '}' . n
		 . '</script>' . n;
	}

	/**
	 * The BugCatcher overlay's stylesheet.
	 *
	 * Goals
	 * -----
	 * - Always full-width, regardless of the host page's container CSS.
	 * - Light, high-contrast defaults (white background, dark grey text)
	 *   so it is legible on any host theme — including dark-blue layouts
	 *   like mn-hegenbarth.de's blue template.
	 * - IE11 compatibility: no CSS custom properties, no `:has()`,
	 *   no `@supports`, no logical properties.  Plain RGB values, named
	 *   selectors, classic media queries.
	 * - `prefers-color-scheme: dark` is honored on browsers that support
	 *   it; IE11 and older Safari ignore the @media block and stay on the
	 *   light defaults.
	 *
	 * @return string  An HTML <style> block.
	 */
	protected static function _overlayStyles() {
	    return n . '<style type="text/css">' . n
. '.bugcatcher-overlay {' . n
. '    width: 100%;' . n
. '    box-sizing: border-box;' . n
. '    margin: 2em 0 0 0;' . n
. '    padding: 1.25em 2em;' . n
. '    background: #ffffff;' . n
. '    color: #222222;' . n
. '    font-family: "Segoe UI", Tahoma, Geneva, Verdana, Arial, sans-serif;' . n
. '    font-size: 14px;' . n
. '    line-height: 1.5;' . n
. '    border-top: 4px solid #c00000;' . n
. '    text-align: left;' . n
. '    position: relative;' . n
. '    z-index: 9999;' . n
. '}' . n
. '.bugcatcher-overlay * { box-sizing: border-box; }' . n
. '.bugcatcher-overlay h1,' . n
. '.bugcatcher-overlay h2,' . n
. '.bugcatcher-overlay h3,' . n
. '.bugcatcher-overlay h4,' . n
. '.bugcatcher-overlay h5,' . n
. '.bugcatcher-overlay h6 {' . n
. '    color: #1a1a1a;' . n
. '    font-weight: 600;' . n
. '    margin: 0 0 0.5em 0;' . n
. '}' . n
. '.bugcatcher-overlay a,' . n
. '.bugcatcher-overlay a:link,' . n
. '.bugcatcher-overlay a:visited {' . n
. '    color: #0050a0;' . n
. '    text-decoration: none;' . n
. '}' . n
. '.bugcatcher-overlay a:hover { text-decoration: underline; }' . n
. '.bugcatcher-overlay p { margin: 0.5em 0; }' . n
. '.bugcatcher-overlay code,' . n
. '.bugcatcher-overlay pre {' . n
. '    font-family: Consolas, "Liberation Mono", Menlo, "Courier New", monospace;' . n
. '    background: #f4f4f4;' . n
. '    color: #c00000;' . n
. '    padding: 1px 4px;' . n
. '    border-radius: 3px;' . n
. '    word-break: break-all;' . n
. '}' . n
. '.bugcatcher-overlay pre {' . n
. '    display: block;' . n
. '    padding: 0.75em 1em;' . n
. '    overflow-x: auto;' . n
. '    color: #222222;' . n
. '}' . n
. '.bugcatcher-overlay .dbg-header {' . n
. '    background: #f0f0f0;' . n
. '    border: 1px solid #d0d0d0;' . n
. '    border-left: 4px solid #c00000;' . n
. '    padding: 0.5em 1em;' . n
. '    cursor: pointer;' . n
. '    font-weight: 600;' . n
. '}' . n
. '.bugcatcher-overlay .dbgHeader {' . n
. '    background: #f7f7f7;' . n
. '    border: 1px solid #d8d8d8;' . n
. '    border-left: 4px solid #c00000;' . n
. '    padding: 0.5em 1em;' . n
. '    margin-top: 0.5em;' . n
. '    cursor: pointer;' . n
. '}' . n
. '.bugcatcher-overlay .dbgHeader.caused-by {' . n
. '    border-left-color: #c08040;' . n
. '    margin-left: 1.5em;' . n
. '}' . n
. '.bugcatcher-overlay .dbgHeader.reported {' . n
. '    border-left-color: #4070c8;' . n
. '    background: #f0f4fa;' . n
. '    margin-top: 1em;' . n
. '}' . n
. '.bugcatcher-overlay .dbgHeader h3 {' . n
. '    margin: 0;' . n
. '    font-size: 1em;' . n
. '    color: #1a1a1a;' . n
. '    font-weight: 600;' . n
. '}' . n
. '.bugcatcher-overlay .dbgContainer {' . n
. '    background: #fafafa;' . n
. '    border: 1px solid #d8d8d8;' . n
. '    border-top: 0;' . n
. '    padding: 1em;' . n
. '    overflow: hidden;' . n
. '}' . n
. '.bugcatcher-overlay .alert {' . n
. '    padding: 0.75em 1em;' . n
. '    border: 1px solid #d8d8d8;' . n
. '    border-radius: 3px;' . n
. '    margin: 0.5em 0;' . n
. '}' . n
. '.bugcatcher-overlay .alert.alert-info {' . n
. '    background: #fff8d6;' . n
. '    color: #66501a;' . n
. '    border-color: #f0e0a0;' . n
. '}' . n
. '.bugcatcher-overlay .alert.alert-error,' . n
. '.bugcatcher-overlay .alert.alert-danger {' . n
. '    background: #fdecec;' . n
. '    color: #882020;' . n
. '    border-color: #f4c0c0;' . n
. '}' . n
. '.bugcatcher-overlay .backtrace-table {' . n
. '    width: 100%;' . n
. '    border-collapse: collapse;' . n
. '    background: #ffffff;' . n
. '    margin-top: 0.75em;' . n
. '    table-layout: fixed;' . n
. '}' . n
. '.bugcatcher-overlay .backtrace-table .TD {' . n
. '    border: 1px solid #d8d8d8;' . n
. '    padding: 0.4em 0.75em;' . n
. '    vertical-align: top;' . n
. '    text-align: left;' . n
. '    word-break: break-word;' . n
. '    color: #222222;' . n
. '    font-size: 13px;' . n
. '}' . n
. '.bugcatcher-overlay .backtrace-table thead .TD {' . n
. '    background: #ececec;' . n
. '    color: #1a1a1a;' . n
. '    font-weight: 600;' . n
. '}' . n
. '.bugcatcher-overlay .accordion-toggle {' . n
. '    color: #1a1a1a;' . n
. '    font-weight: 600;' . n
. '    font-size: 1.05em;' . n
. '}' . n
. '.bugcatcher-overlay code, .bugcatcher-overlay pre,' . n
. '.bugcatcher-overlay .alert, .bugcatcher-overlay .alert b {' . n
. '    line-height: 1.5;' . n
. '}' . n
/* ------------------ Dark mode (modern browsers only) ------------------ */
. '@media (prefers-color-scheme: dark) {' . n
. '    .bugcatcher-overlay {' . n
. '        background: #1e1e1e;' . n
. '        color: #d8d8d8;' . n
. '        border-top-color: #ff6b6b;' . n
. '    }' . n
. '    .bugcatcher-overlay h1,' . n
. '    .bugcatcher-overlay h2,' . n
. '    .bugcatcher-overlay h3,' . n
. '    .bugcatcher-overlay h4,' . n
. '    .bugcatcher-overlay h5,' . n
. '    .bugcatcher-overlay h6,' . n
. '    .bugcatcher-overlay .accordion-toggle,' . n
. '    .bugcatcher-overlay .dbgHeader h3 { color: #f0f0f0; }' . n
. '    .bugcatcher-overlay a,' . n
. '    .bugcatcher-overlay a:link,' . n
. '    .bugcatcher-overlay a:visited { color: #6ec3ff; }' . n
. '    .bugcatcher-overlay .dbg-header,' . n
. '    .bugcatcher-overlay .dbgHeader {' . n
. '        background: #2a2a2a;' . n
. '        border-color: #3a3a3a;' . n
. '        border-left-color: #ff6b6b;' . n
. '    }' . n
. '    .bugcatcher-overlay .dbgHeader.caused-by {' . n
. '        border-left-color: #d8a060;' . n
. '    }' . n
. '    .bugcatcher-overlay .dbgContainer {' . n
. '        background: #232323;' . n
. '        border-color: #3a3a3a;' . n
. '    }' . n
. '    .bugcatcher-overlay code,' . n
. '    .bugcatcher-overlay pre {' . n
. '        background: #2a2a2a;' . n
. '        color: #ff9090;' . n
. '    }' . n
. '    .bugcatcher-overlay pre { color: #d8d8d8; }' . n
. '    .bugcatcher-overlay .alert {' . n
. '        border-color: #3a3a3a;' . n
. '    }' . n
. '    .bugcatcher-overlay .alert.alert-info {' . n
. '        background: #332a14;' . n
. '        color: #e8d090;' . n
. '        border-color: #4a3a18;' . n
. '    }' . n
. '    .bugcatcher-overlay .alert.alert-error,' . n
. '    .bugcatcher-overlay .alert.alert-danger {' . n
. '        background: #3a1818;' . n
. '        color: #f5a8a8;' . n
. '        border-color: #5a2828;' . n
. '    }' . n
. '    .bugcatcher-overlay .backtrace-table { background: #1e1e1e; }' . n
. '    .bugcatcher-overlay .backtrace-table .TD {' . n
. '        border-color: #3a3a3a;' . n
. '        color: #d8d8d8;' . n
. '    }' . n
. '    .bugcatcher-overlay .backtrace-table thead .TD {' . n
. '        background: #2a2a2a;' . n
. '        color: #f0f0f0;' . n
. '    }' . n
. '}' . n
. '</style>' . n;
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