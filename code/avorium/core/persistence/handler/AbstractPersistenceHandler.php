<?php

/* 
 * The MIT License
 *
 * Copyright 2014 Ronny Hildebrandt <ronny.hildebrandt@avorium.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

require_once dirname(__FILE__).'/../AbstractPersistenceAdapter.php';

/**
 * Base class for all persistence handlers. Provides functions for event 
 * handling and persistence adapter access. A persistence handler contains
 * logical functions for persistent objects.
 */
abstract class avorium_core_persistence_handler_AbstractPersistenceHandler {
    
    private static $persistenceAdapter = null;
    
    /**
     * Returns the currently set persistence adapter for the handler.
     * 
     * @return avorium_core_persistence_AbstractPersistenceAdapter Persistence adapter of the handler
     * @throws Exception When the persistence adapter is not set (null) or when it is not a class derived from avorium_core_persistence_AbstractPersistenceAdapter
     */
    public static function getPersistenceAdapter() {
        if (!is_a(static::$persistenceAdapter, 'avorium_core_persistence_AbstractPersistenceAdapter')) {
            throw new Exception('No valid persistence adapter set.');
        }
        return static::$persistenceAdapter;
    }
    
    /**
     * Sets the persistence adapter for the handler.
     * 
     * @param avorium_core_persistence_AbstractPersistenceAdapter $persistenceAdapter Persistence adapter to set
     * @throws Exception When the given persistence adapter is null or when it is not a class derived from avorium_core_persistence_AbstractPersistenceAdapter
     */
    public static function setPersistenceAdapter(avorium_core_persistence_AbstractPersistenceAdapter $persistenceAdapter) {
        if (!is_a($persistenceAdapter, 'avorium_core_persistence_AbstractPersistenceAdapter')) {
            throw new Exception('Tried to set an invalid persistence adapter!');
        }
        static::$persistenceAdapter = $persistenceAdapter;
    }
	
    private static $eventlisteners = array();

    /**
     * Registers a listener function for a specific event name. A listener
     * can be registered multiply for an event and so will receive the event
     * multiply.
     * 
     * @param string $eventname Name to register a listener for.
     * @param callable $listener Listener to register for the event.
     * @throws Exception When the event name is null or when the listener is 
     * not a callable function.
     */
    public static function addEventListener($eventname, $listener) {
        if (is_null($eventname)) {
            throw new Exception('The event name must not be null!');
        }
        if (!is_callable($listener)) {
            throw new Exception('The listener must be a callable function!');
        }
        if (!isset(static::$eventlisteners[$eventname])) {
            static::$eventlisteners[$eventname] = array();
        }
        static::$eventlisteners[$eventname][] = $listener;
    }

    /**
     * Sends an event with the given name to all registered listeners.
     * When no listener is registered for the given event name, no event
     * will be sent. Caller must make sure, that the listeners can handle
     * the parameters sent by the event and must validate the parameters
     * for themselves.
     * 
     * @param string $eventname Name of the event to send.
     * @param object $args Parameters to forward to the listeners.
     * @throws Exception When the given event name is null.
     */
    public static function sendEvent($eventname, $args) {
        if (is_null($eventname)) {
            throw new Exception('The event name must not be null!');
        }
        if (isset(static::$eventlisteners[$eventname])) {
            foreach (static::$eventlisteners[$eventname] as $eventlistener) {
                call_user_func($eventlistener, $args);
            }
        }
    }

}
