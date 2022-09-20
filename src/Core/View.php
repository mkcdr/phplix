<?php

namespace App\Core;

use Exception;
use App\Core\Security;

class View
{
    /**
     * @var string $MAINBLOCK Main block key name
     */
    private const MAINBLOCK = 'content';

    /**
     * @var string $path Views directory path
     */
    private static $path = '';

    /**
     * @var array $data Array of data for views
     */
    private $data;

    /**
     * @var string $view View filename
     */
    private $view;

    /**
     * @var string $layout Layout filename
     */
    private $layout;

    /**
     * @var array $blocks Array of blocks
     */
    private $blocks = [];

    /**
     * @var string $currentBlock Current block key name
     */
    private $currentBlock;

    /**
     * Set the main directory for the templates and views
     * 
     * @param string $path Path to views directory
     * @return void
     */
    public static function setPath($path) : void
    {
        $path = str_replace('\\/', DIRECTORY_SEPARATOR, rtrim($path, '\\/'));
        if (!is_dir($path)) 
        {
            throw new Exception('Invalid views directory: ' . $path);
        }
        self::$path = $path;
    }

    /**
     * Create and return a View object
     * 
     * @return self
     */
    public static function create() : self
    {
        return new self;
    }

    /**
     * Render the view content
     * 
     * @param string $view The view path to be rendered
     * @param array $data The data to be rendered in the view
     * @return string
     */
    public function render($view, $data=[]) : string
    {
        $this->layout = null;
        $this->data = $data;
        unset($data);

        $this->view = self::$path . DIRECTORY_SEPARATOR . $view;

        if (!file_exists($this->view)) 
        {
            throw new Exception(sprintf('View %s not found', $view));
        }
        
        extract($this->data, EXTR_SKIP);

        ob_start();
        include $this->view;
        $this->blocks[self::MAINBLOCK] = ob_get_clean();

        if (!empty($this->layout)) 
        {
            $this->render($this->layout, $this->data);
        }

        return $this->blocks[self::MAINBLOCK];
    }

    /**
     * Set the layout to be extended
     * This method is used within the view file
     * 
     * @param string $layout Layout's path to be extended
     * @return void
     */
    private function extends($layout) : void
    {
        $this->layout = $layout;
    }
    
    /**
     * Return the main block content
     * Alias for block('content')
     * 
     * @return string
     */
    private function content() : string
    {
        return $this->block(self::MAINBLOCK);
    }

    /**
     * Render partial view
     * 
     * @param string $view The view path to be rendered
     * @param array $data Array of additional data
     * @return string
     */
    private function partial($view, $data=[]) : string
    {
        $data = array_merge($this->data, $data);
        return (new self)->render($view, $data);
    }

    /**
     * Render the blocks content
     * 
     * @param string $name The block name
     * @return string
     */
    private function block($name) : string
    {
        if (!isset($this->blocks[$name]))
        {
            return null;
        }
        return $this->blocks[$name];
    }

    /**
     * Start a block section that will store 
     * content in a new buffer
     * 
     * @param string $name The block name
     * @return void
     */
    private function startBlock($name) : void
    {
        if ($name == self::MAINBLOCK) 
        {
            throw new Exception(sprintf('Block name `%s` is preserved', self::MAINBLOCK));
        }
        $this->currentBlock = $name;
        ob_start();
    }

    /**
     * End a block section and store the 
     * collected output in the blocks array
     * 
     * @return void
     */
    private function endBlock() : void
    {
        if (empty($this->currentBlock)) 
        {
            throw new Exception('No block have been started to end');
        }
        $this->blocks[$this->currentBlock] = ob_get_clean();
        $this->currentBlock = null;
    }

    /**
     * Print execution time
     * 
     * @return void
     */
    private function printExecutionTime() : void
    {
        echo sprintf("\n<!-- Total Execution Time: %.8f sec -->", microtime(true) - START_TIME);
    }

    /**
     * Get CSRF Token
     * 
     * @return string
     */
    private function csrfToken() : string
    {
        return (new Security())->getCsrfToken();
    }
}