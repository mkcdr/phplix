<?php

namespace App\Core\Http\Exception;

use RuntimeException;

class HttpException extends RuntimeException 
{
    /**
     * @var string $title
     */
    protected $title = '';

    /**
     * @var string $description
     */
    protected $description = '';

    public function __construct(string $message = '', int $code = 0, string $title = '', string $description = '')
    {
        if (!empty($message)) {
            $this->message = $message;
        }
        if (!empty($code)) {
            $this->code = $code;
        }
        if (!empty($title)) {
            $this->title = $title;
        }
        if (!empty($description)) {
            $this->description = $description;
        }
        
        parent::__construct($this->message, $this->code);
    }

    /**
     * Get title 
     * @return string
     */
    public function getTitle() : string
    {
        return $this->title;
    }

    /**
     * Get description
     * @return string
     */
    public function getDescription() : string
    {
        return $this->description;
    }
}