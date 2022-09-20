<?php

use App\Core\Kernel;
use App\Core\Security;
use App\Core\View;

if (!function_exists('pre'))
{
	/**
	 * Print variables 
	 * @param mixed $var
	 * @return void
	 */
	function pre($var)
	{
		echo '<pre>';
		print_r($var);
		echo '</pre>';
	}
}

if (!function_exists('redirect'))
{
	/**
	 * Redirect
	 * 
	 * @param string $location
	 * @return void
	 */
	function redirect($location) 
	{
		header("Location: $location");
		exit;
	}
}

if (!function_exists('url'))
{
	/**
	 * Reverse named route 
	 * 
	 * @param string $name
	 * @param array $params
	 * @return string
	 */
	function url($name, $params=[])
	{
		return Kernel::$App->getRouter()->url($name, $params);
	}
}

if (!function_exists('render_template'))
{
	/**
	 * Render template
	 * 
	 * @param string $template
	 * @param array $data
	 * @return string
	 */
	function render_template($template, $data=[]) 
	{
		return View::create()->render($template, $data);
	}
}

if (!function_exists('csrf_token_name'))
{
	/**
	 * Get CSRF token name
	 * 
	 * @return string
	 */
	function csrf_token_name()
	{
		return Security::CSRF_TOKEN_NAME;
	}
}

if (!function_exists('csrf_token_value'))
{
	/**
	 * Get CSRF token value
	 * 
	 * @return string
	 */
	function csrf_token_value()
	{
		return (new Security)->getCsrfToken();
	}
}

if (!function_exists('print_execution_time'))
{
	/**
	 * Print execution time
	 * 
	 * @return void
	 */
	function print_execution_time()
	{
		echo sprintf("\n<!-- Total Execution Time: %.8f sec -->", microtime(true) - START_TIME);
	}
}