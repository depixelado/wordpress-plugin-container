<?php
/**
 * Provide an app container for you WordPress Plugin.
 *
 * PluginContainer can store any kind of data as a property. 
 * - All callable values will be executed after running 'run' command.
 * - Every callable element is considered a service.
 * - ArrayAccess interface implemented to access to properties as an array
 * - Allow deferred services, which won´t be initialized until the property is explicitly accessed
 *      i.e $app['mailer']
 * - Implement sugar syntax to define deferred services 
 *
 *
 * @author  Daniel Jimenez <jimenezdaniel87@gmail.com>
 * @version 1.0.0
 */
class PluginContainer implements \ArrayAccess {

    /** @const DEFERRED_SERVICE_INIT_SYMBOL Symbol to consider property 
     * as a Deferred Service on definition 
     */
    const DEFERRED_SERVICE_INIT_SYMBOL = '*';

    /** @var array $app Contain all plugin properties and services */
    private $app = array();

    /**
     * Execute all callable values on $this->app
     * @since 1.0.0
     */
    public function run() {
        // Loop through $this->app
        foreach ($this->app as $key => $value) {
            if (is_callable($value)) {
                // Execute callable property and store the result
                $this->app[$key] = call_user_func($value);
            }
        }
    }

    /**
     * Set a deferred service which will avoid the first run call. It allows a deferred initialization:w
     * when the property is directly accessed.
     * @param string $key Name of the service
     * @param mixed $value Callable element
     * @throws Exception When $value is not callable
     * @since 1.0.0 
     */
    public function deferredService($key, $value) {
        if (!is_callable($value)) {
            throw new Exception("Not callable value");
        }

        // Wrap function in other to avoid run it
        $this->app[$key] = function() use ($value) {
            return $value;
        };
    }

    /**
     * Access to App property
     * @param  string $offset Specify the property to return
     * @return mixed          Return property value
     * @since 1.0.0
     */
    public function get($offset){
        return $this->offsetGet($offset);
    }

    /**
     * Set a value in an specified offset
     * @param  string $offset Specific key to point the value to store
     * @param  mixed $value Value to be stored
     * @since  1.0.0
     */
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            // Append $value at the end of the array
            $this->app[] = $value;
        } else {
            if ($this->isDefferreable($offset, $value)) {
                // Get offset without symbol 
                $offsetNoSymbol = $this->getOffsetWithoutSymbol($offset);

                // Set the callable value as a deferred service
                $this->deferredService($offsetNoSymbol, $value);
            } else {
                // Insert $value at the specified offset
                $this->app[$offset] = $value;
            }
        }
    }

    /**
     * Check if a new property definition is a defferreable service
     * @param string $key Name of the service
     * @param mixed $value Callable elementa
     * @return bool if the key start with the deferred service init symbol and the value is callable
     * @since 1.0.0
     */
    public function isDefferreable($key, $value)
    {
        // Get first $key character
        $symbol = substr($key, 0, 1);

        if ($symbol === self::DEFERRED_SERVICE_INIT_SYMBOL && is_callable($value)) {
            // Return true if it is suitable to be deferred
            return true;
        }

        return false;
    }

    /**
     * Remove deferred service init symbol from the given offset
     * @param string $offset Key with deferred service init symbol
     * @return string Key without deferred service init symbol
     * @since 1.0.0
     */
    public function getOffsetWithoutSymbol($offset)
    {
        // Get name without symbol
        return substr($offset, 1);
    }

    /**
     * Indicate whether the specified value exists or not
     * @param  string $offset Offset to check
     * @return bool Return true if the specified offset exists, otherwise, false
     * @since  1.0.0
     */
    public function offsetExists($offset) {
        // Returns if the specified position exists
        return isset($this->app[$offset]);
    }

    /**
     * Delete the specified value
     * @param  string $offset Position to delete
     * @since  1.0.0
     */
    public function offsetUnset($offset) {
        // Delete the specified position
        unset($this->app[$offset]);
    }

    /**
     * Retrieve a value by its offset
     * @param  string $offset [description]
     * @return mixed         [description]
     * @since  1.0.0
     */
    public function offsetGet($offset) {
        if (!isset($this->app[$offset])) {
            return null;
        }

        if (is_callable($this->app[$offset])) {
            // Execute property if it is callable and 
            // set in its position the returned value
            $this->app[$offset] = $this->app[$offset]();
        }

        return $this->app[$offset];
    }
}
