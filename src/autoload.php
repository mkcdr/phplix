<?php
/**
* Reference to the PSR-4 autoloader function to require classes
*
* @param string $prefix     The classes namespace prefix.
* @param string $base_dir   Classes base directory. 
* @return callback
*/

return function ($prefix, $base_dir) {
    /**
     * @param string $class The fully-qualified class name.
     * @return void
     */
    spl_autoload_register(function($class) use ($prefix, $base_dir) {

        // does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            // no, move to the next registered autoloader
            return;
        }

        // get the relative class name
        $relative_class = substr($class, $len);

        // replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';

        // if the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    });
};