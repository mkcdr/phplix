<?php

namespace App\Core\Error;

use stdClass;
use Throwable;
use ErrorException;
use App\Core\Kernel;
use App\Core\Http\Exception\{
    HttpException, 
    HttpNotFoundException,
    HttpCsrfForbiddenException,
    HttpMethodNotAllowedException,
    HttpInternalServerErrorException,
    HttpRouteNotFoundException,
};

class ErrorHandler
{
    /**
     * @var Kernel Application Instance
     */
    private $app;

    /**
     * @var array Custom error handlers for production mode
     */
    private $customHandlers=[];

    /**
     * @var int number of lines to show before and after the error occurred
     */
    const LINES_NEAR_ERROR=10;

    public function __construct(Kernel $app) 
    {
        $this->app = $app;
        $this->initHandlers();
    }

    /**
     * Set custom error handler
     * 
     * @param int $errorCode
     * @param $handler
     * @return void
     */
    public function setCustomHandler(int $errorCode, $handler) : void
    {
        $this->customHandlers[$errorCode] = $handler;
    }

    /**
     * Initialize error handlers
     * 
     * @return void
     */
    private function initHandlers() : void
    {
        register_shutdown_function([$this, 'shutdown']);
        set_exception_handler([$this, 'exception']);
        set_error_handler([$this, 'error']);
    }

    /**
     * Simple render method to render the error templates
     * so there is no need to use other template engines
     * 
     * @param string $template
     * @param array $data
     * @return void
     */
    private function render(string $template, array $data=[]) : void
    {
        ob_start();
        extract($data, EXTR_SKIP);
        include __DIR__ . '/Templates/' . $template;
        echo ob_get_clean();
    }

    /**
     * Check if client need a json formatted response
     * 
     * @return bool
     */
    private function wantsJson() : bool
	{
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
		} 
        
        if (isset($_SERVER['HTTP_ACCEPT']) && preg_match('#^([^/]+)/json(.*)$#', $_SERVER['HTTP_ACCEPT'])) {
            return true;
        }

		return false;
	}

    /**
     * Handle exception
     * 
     * @param Throwable $exception
     * @return void
     */
    public function exception(Throwable $exception) : void
    {
        $traceback = $exception->getTrace();
		
		if ($exception instanceof ErrorException)
		{
			$traceback = array_reverse($traceback);
			array_pop($traceback);
		}

        $this->handle(get_class($exception), $exception, $traceback);
    }

    /**
     * Convert errors to exceptions
     * 
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @throws ErrorException
     * @return bool
     */
    public function error(int $errno, string $errstr, string $errfile, int $errline) : bool
    {
        if (error_reporting() & $errno) {
			throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
		}
		
		return true;	
    }

    /**
     * Shutdown handler will be triggered when exception
     * handler failed to handle errors
     * 
     * @return void
     */
    public function shutdown() : void
    {
        if (($error = error_get_last()) == null) exit;
        
        $traceback = array_reverse(debug_backtrace());
        array_pop($traceback);
        $exception = new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);
        
        $this->handle('shutdown', $exception, $traceback);
    }

    /**
     * Handle error
     * 
     * @param string $exception_type
     * @param Throwable $exception
     * @param array $traceback
     * @return void
     */
    private function handle(string $exception_type, Throwable $exception, array $traceback=[]) : void
    {
        // clear all output buffer levels
        while (ob_get_level())
        {
            ob_end_clean();
        }
        
        http_response_code($exception instanceof HttpException ? $exception->getCode() : 500);
        
        try {
            // render error in debug mode
            if ($this->app->isDebug())
            {
                if ($this->wantsJson())
                {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'type'      => $exception_type,
                        'message'   => $exception->getMessage(),
                        'code'      => $exception->getCode(),
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine(),
                        'traceback' => $traceback
                    ]);
                }
                elseif ($exception instanceof HttpCsrfForbiddenException)
                {
                    echo $this->render('403_csrf.phtml');
                }
                elseif ($exception instanceof HttpNotFoundException)
                {
                    $routes     = $this->app->getRouter()->getResolvedRoutes();
                    $resolved   = $this->app->getRouter()->resolved();

                    if ($exception instanceof HttpRouteNotFoundException !== true)
                    {
                        $reason     = $exception->getMessage();
                        $raised_by  = $exception->getFile();
                        echo $this->render('404.phtml', [
                            'reason'    => $reason,
                            'raised_by' => $raised_by,
                            'resolved'  => $resolved,
                            'routes'    => $routes
                        ]);
                    }
                    else
                    {
                        echo $this->render('404.phtml', [
                            'resolved'  => $resolved,
                            'routes'    => $routes
                        ]);
                    }
                }
                elseif ($exception instanceof HttpMethodNotAllowedException)
                {
                    $allowed = $this->app->getMatchResults()['allowed'];
                    header('Allow: ' . implode(', ', $allowed));
                    $this->render('405.phtml');
                }
                else
                {
                    $source = $this->getErrorSource(
                        $exception->getMessage(), 
                        $exception->getFile(), 
                        $exception->getLine()
                    );
                    $this->render('500.phtml', [
                        'exception_type' => $exception_type,
                        'exception' => $exception,
                        'traceback' => $traceback,
                        'source'    => $source
                    ]);
                }
            }
            else
            {
                // in production mode all errors should return an internal server exception
                // TODO: add a logger here to log errors
                if (!($exception instanceof HttpException)) 
                {
                    $exception = new HttpInternalServerErrorException;
                }

                if ($this->wantsJson())
                {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'message'   => $exception->getMessage(),
                        'code'      => $exception->getCode()
                    ]);
                }
                else
                {
                    // check for custom error handler for specific error code
                    $custom_err_handler = isset($this->customHandlers[$exception->getCode()])
                                        ? $this->customHandlers[$exception->getCode()]
                                        : null;

                    if ($custom_err_handler)
                    {
                        if (is_array($custom_err_handler))
                        {
                            [$c_err_class, $c_err_method] = $custom_err_handler;
                            $c_err_obj = new $c_err_class();
                            $c_err_obj->$c_err_method($exception); 
                        }
                        else
                        {
                            $custom_err_handler($exception);
                        }
                    }
                    else
                    {
                        $this->render('default.phtml', [
                            'message'   => $exception->getMessage(),
                            'code'      => $exception->getCode(),
                            'title'     => $exception->getTitle(),
                            'description' => $exception->getDescription()
                        ]);
                    }
                }
            }
        } 
        catch (Throwable $th) 
        {
            // TODO: add logger for production because errors can't be displayed for users
            // return simple text message if everyting failed as a last resort
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');

            if ($this->app->isDebug())
            {
                printf("Error:\t%s\nCode:\t%d\nFile:\t%s\nLine:\t%d\n", $th->getMessage(), $th->getCode(), $th->getFile(), $th->getLine());
                echo "Traceback:\n";
                foreach ($th->getTrace() as $i => $frame)
                {
                    printf("#%d %s, line %s, %s()\n", $i, isset($frame['file']) ? $frame['file'] : '<unknown file>', isset($frame['line']) ? $frame['line'] : '<unknown file>', $frame['function']);
                }
            }
            else
            {
                echo 'Internal Server Error 500';
            }
        }
        
        exit;
    }

    /**
     *  Get the error source
     * 
     * @param string $message
     * @param string $file
     * @param int $line
     * @return object|null
     */
    private function getErrorSource(string $message, string $file, int $line)
	{
        $source = new stdClass;
        $source->message = $message;
        $source->file = $file;
        $source->line = $line;
        $source->lines = [];

		if (file_exists($file)) 
        {
            $start = max(0, $line - self::LINES_NEAR_ERROR);
			$end = $line + self::LINES_NEAR_ERROR;
			$fp = fopen($file, 'r');
			$i = 1; 

			while (($buf = fgets($fp)) !== false && $i <= $end) 
            {
				if ($i >= $start) 
                {
                    $source->lines[] = [$i, $buf];
				}

				$i++;
			}

			fclose($fp);

            return $source;
		}

		return null;
	}
}