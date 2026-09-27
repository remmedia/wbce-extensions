<?php
/**
 *
 * @category        admintool / preinit / initialize
 * @package         errorlogger
 * @author          Ruud Eisinga - www.dev4me.com
 * @link			https://dev4me.com/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WBCE 1.4+ / WB2.10+
 * @version         1.1.4.1
 * @lastmodified    July 30, 2022
 *
 */

/**
 * preinit.php is used to activate the errorhandler
 *
 * initialize.php is used to set the errorlevel to E_ALL regardless the WB setting
 *
 */

if (!defined('WB_PATH')) {
    die("Go");
}

/* Worker-free fallback for the former Log Rotate behaviour. It runs at most
 * once per PHP session and only checks the file size before an atomic rename.
 * This is deliberately independent of the original Error Logger, which may
 * remain installed unchanged. */
require_once __DIR__.'/Language.php';
$logCenterWorkerRegistry = WB_PATH.'/modules/worker/Registry.php';
$logCenterWorkerActive = is_file($logCenterWorkerRegistry)
    && (!function_exists('wbce_addon_is_active') || wbce_addon_is_active('worker', 'module'));
if (!$logCenterWorkerActive && (!isset($_SESSION['log_center_rotation_checked']) || $_SESSION['log_center_rotation_checked'] !== date('Y-m-d-H'))) {
    $_SESSION['log_center_rotation_checked'] = date('Y-m-d-H');
    $logCenterMaintenance = __DIR__.'/LogMaintenance.php';
    if (is_file($logCenterMaintenance)) {
        try { require_once $logCenterMaintenance; WbceLogCenterMaintenance::run(); } catch (Throwable $ignored) { }
    }
}

// The original Error Logger remains an independent, supported module.  If it
// was loaded first, it owns PHP error capture and Log Center only provides the
// local/remote log client functions.  No original class is replaced.
if (class_exists('WBCE_Error', false)) { return; }

require_once __DIR__.'/Client.php';


$logDir = WB_PATH.'/var/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0750, true);
}
$errorLogFilename = $logDir . '/php_error.log.php';
if (!file_exists($errorLogFilename)) {
    $sTmp = '<?php die(\'No access\'); ?>created: ['.gmdate('c').']'.PHP_EOL;
    file_put_contents($errorLogFilename, $sTmp, FILE_APPEND | LOCK_EX);
}

class WbceLogCenterErrorHandler
{
    private $errorLogFilename;
	private $url;
    private $remoteLogger;

    public function __construct($errorLogFilename)
    {
        ini_set("display_errors", "off");
        ini_set('log_errors', 0);
        ini_set('error_log', $errorLogFilename);
		error_reporting(E_ALL);
        $this->errorLogFilename = $errorLogFilename;
        $this->remoteLogger = null;
        $this->registerHandlers();
        register_shutdown_function(array($this, 'shutdownError'));
        // Flush after shutdownError so a fatal error captured during shutdown
        // is included in the same single remote batch.

		 $host = isset($_SERVER['HTTP_HOST']) ? self::singleLine((string)$_SERVER['HTTP_HOST']) : '';
		 $uri = isset($_SERVER['REQUEST_URI']) ? self::singleLine((string)$_SERVER['REQUEST_URI']) : '';
		 $this->url = $host === '' ? 'CLI/worker request' : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $host . $uri);
    }

    private function registerHandlers()
    {
        set_error_handler(array($this, 'scriptError'));
        set_exception_handler(array($this, 'exceptionError'));
    }

    public function scriptError($errno, $errstr, $errfile, $errline)
    {
        if ((error_reporting() & $errno) === 0) { // disabled or suppressed with @
            return;
        }

        //if($errno == E_DEPRECATED) return;
        //if($errno == E_USER_DEPRECATED) return;

        switch ($errno) {
            case E_ERROR:               $errseverity = "Error";             break;
            case E_WARNING:             $errseverity = "Warning";           break;
            case E_NOTICE:              $errseverity = "Notice";            break;
            case E_CORE_ERROR:          $errseverity = "Core Error";        break;
            case E_CORE_WARNING:        $errseverity = "Core Warning";      break;
            case E_COMPILE_ERROR:       $errseverity = "Compile Error";     break;
            case E_COMPILE_WARNING:     $errseverity = "Compile Warning";   break;
            case E_USER_ERROR:          $errseverity = "User Error";        break;
            case E_USER_WARNING:        $errseverity = "User Warning";      break;
            case E_USER_NOTICE:         $errseverity = "User Notice";       break;
            //case E_STRICT:              $errseverity = "Strict Standards";  break;
            case E_RECOVERABLE_ERROR:   $errseverity = "Recoverable Error"; break;
            case E_DEPRECATED:          $errseverity = "Deprecated";        break;
            case E_USER_DEPRECATED:     $errseverity = "User Deprecated";   break;
            default:                    $errseverity = "Error";             break;
        }

        $str_err = debug_backtrace();

        $x = sizeof($str_err) -1;
        $x = $x < 2 ? $x : 2;
        $frame = isset($str_err[$x]) && is_array($str_err[$x]) ? $str_err[$x] : array();
        if (empty($frame['class'])) {
            if ((substr((string)($frame['function'] ?? ''), 0, 7) == 'include') || (substr((string)($frame['function'] ?? ''), 0, 7) == 'require')) {
                $str_err[$x]['function'] = '';
            }
        }

        $trace = $this->formatTrace($str_err);
        $out = gmdate('c').' '.'['.$errseverity.'] '.str_replace(dirname(dirname(__DIR__)), '', $errfile).':['.$errline.'] '
            . ' from '.str_replace(dirname(dirname(__DIR__)), '', (string)($frame['file'] ?? $errfile)).':['.(int)($frame['line'] ?? $errline).'] '
            . (!empty($frame['class']) ? $frame['class'].($frame['type'] ?? '') : '').($frame['function'] ?? '').' '
            . '"'.self::singleLine((string)$errstr).'"'.($trace!==''?' | Trace: '.$trace:'').PHP_EOL;


        $this->writeError($out, $errseverity, str_replace(dirname(dirname(__DIR__)), '', (string)$errfile).':'.(int)$errline, (string)$errstr, $trace);
        return true;
    }

    public function exceptionError($exception)
    {
        $file = str_replace(dirname(dirname(__DIR__)), '', $exception->getFile());
        $trace = $this->formatTrace($exception->getTrace());
        $out = gmdate('c').' '.'[Exception] '.'There was an unknown exception: '.self::singleLine((string)$exception->getMessage()).' in line (' . $exception->getLine() . ') of ' . $file . ($trace!==''?' | Trace: '.$trace:'') . PHP_EOL;
        $this->writeError($out, 'Exception', $file.':'.(int)$exception->getLine(), (string)$exception->getMessage(), $trace);
        $visibleTrace = defined('ERRORLOGGER_FATAL_TRACE') && filter_var(ERRORLOGGER_FATAL_TRACE, FILTER_VALIDATE_BOOLEAN) ? $trace : '';
        $this->renderDiagnostic((string)$exception->getMessage(), $file, (int)$exception->getLine(), $visibleTrace);
        return true;
    }

    public function shutdownError()
    {
        $error = error_get_last();
        if (!is_array($error) || !in_array((int)$error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
            return;
        }
        $file = str_replace(dirname(dirname(__DIR__)), '', (string)$error['file']);
        $out = gmdate('c').' [Fatal Error] '.self::singleLine((string)$error['message'])
            .' in line ('.(int)$error['line'].') of '.$file.PHP_EOL;
        $this->writeError($out, 'Fatal Error', $file.':'.(int)$error['line'], (string)$error['message'], '');
        $this->renderDiagnostic((string)$error['message'], $file, (int)$error['line'], '');
    }


    private function writeError($out, $severity = 'Error', $source = '', $message = '', $trace = '')
    {
		$remoteEntries = array();
		$localWritten = true;
		if($this->url) {
			$urlOut = self::singleLine(strip_tags($this->url));
			$preout = gmdate('c').' '.'[Visitor Request] '.$urlOut.PHP_EOL;
			$localWritten = @file_put_contents($this->errorLogFilename, $preout, FILE_APPEND | LOCK_EX) !== false;
			$remoteEntries[] = array('time'=>gmdate('c'),'severity'=>'Visitor Request','source'=>'','message'=>$urlOut,'trace'=>'','line'=>rtrim($preout));
			$this->url = '';
		}

        $localWritten = (@file_put_contents($this->errorLogFilename, $out, FILE_APPEND | LOCK_EX) !== false) && $localWritten;
        $remoteEntries[] = array('time'=>gmdate('c'),'severity'=>self::singleLine($severity),'source'=>self::singleLine($source),'message'=>self::singleLine($message),'trace'=>self::singleLine($trace),'line'=>rtrim((string)$out));
        // The remote endpoint receives an optional copy only. The mandatory
        // local log is always written first and is never changed here.
        if ($localWritten && class_exists('WbceLogCenterClient')) WbceLogCenterClient::send('error', $remoteEntries);
    }

    private static function singleLine($value)
    {
        return trim((string)preg_replace('/[\r\n\x00]+/', ' ', (string)$value));
    }

    private function formatTrace($trace)
    {
        if (!is_array($trace)) return '';
        $root = dirname(dirname(__DIR__));
        $frames = array();
        foreach (array_slice($trace, 0, 30) as $index => $frame) {
            if (!is_array($frame)) continue;
            $file = str_replace($root, '', (string)($frame['file'] ?? ''));
            $line = (int)($frame['line'] ?? 0);
            $call = (string)($frame['class'] ?? '').(string)($frame['type'] ?? '').(string)($frame['function'] ?? '');
            $frames[] = '#'.$index.' '.($file !== '' ? $file.($line > 0 ? ':'.$line : '').' ' : '').$call.'()';
        }
        // Keep each trace frame on its own line in the diagnostic and log.
        return implode(" <-\n", $frames);
    }

    private function renderDiagnostic($message, $file, $line, $trace)
    {
        if (PHP_SAPI === 'cli') return;
        // Never leave a browser request with a blank page.  Technical details
        // stay opt-in, but every fatal error gets a small safe response.
        $showDetails = defined('ERRORLOGGER_GENERAL_DISPLAY')
            && filter_var(ERRORLOGGER_GENERAL_DISPLAY, FILTER_VALIDATE_BOOLEAN);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store, max-age=0');
        }
        $title = defined('LANGUAGE') && LANGUAGE === 'DE' ? 'Ein fataler Fehler ist aufgetreten.' : 'A fatal error occurred.';
        $generic = defined('LANGUAGE') && LANGUAGE === 'DE'
            ? 'Die Anfrage konnte nicht verarbeitet werden. Weitere Informationen stehen im Fehlerprotokoll.'
            : 'The request could not be processed. Further information is available in the error log.';
        echo '<div class="errorlogger-fatal" role="alert"><h1>'.htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</h1>';
        if ($showDetails) {
            echo '<p>'.htmlspecialchars(self::singleLine($message), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>';
            echo '<p><code>'.htmlspecialchars((string)$file.':'.(int)$line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</code></p>';
            if ($trace !== '') echo '<pre>'.htmlspecialchars(ltrim((string)$trace, ' |'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</pre>';
        } else {
            echo '<p>'.htmlspecialchars($generic, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>';
        }
        echo '</div>';
    }
}

$_wbceLogCenterErrors = new WbceLogCenterErrorHandler($errorLogFilename);
