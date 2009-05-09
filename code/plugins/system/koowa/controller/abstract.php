<?php
/**
 * @version		$Id$
 * @category	Koowa
 * @package		Koowa_Controller
 * @copyright	Copyright (C) 2007 - 2009 Johan Janssens and Mathias Verraes. All rights reserved.
 * @license		GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     	http://www.koowa.org
 */

/**
 * Abstract Controller Class
 *
 * Note: Concrete controllers must have a singular name
 *
 * @author		Johan Janssens <johan@koowa.org>
 * @author		Mathias Verraes <mathias@koowa.org>
 * @category	Koowa
 * @package		Koowa_Controller
 * @uses		KMixinClass
 * @uses 		KCommandChain
 * @uses        KObject
 * @uses        KFactory
 */
abstract class KControllerAbstract extends KObject
{
	/**
	 * Array of class methods
	 *
	 * @var	array
	 */
	protected $_methods = array();

	/**
	 * Array of class methods to call for a given action.
	 *
	 * @var	array
	 */
	protected $_actionMap = array();

	/**
	 * Current or most recent action to be performed.
	 *
	 * @var	string
	 */
	protected $_action;

	/**
	 * URL for redirection.
	 *
	 * @var	string
	 */
	protected $_redirect = null;

	/**
	 * Redirect message.
	 *
	 * @var	string
	 */
	protected $_message = null;
	
	/**
	 * Redirect message type.
	 *
	 * @var	string
	 */
	protected $_messageType = 'message';
	
	/**
	 * Constructor.
	 *
	 * @param	array An optional associative array of configuration settings.
	 * Recognized key values include 'name', 'default_action', 'command_chain'
	 * (this list is not meant to be comprehensive).
	 */
	public function __construct( array $options = array() )
	{
        // Initialize the options
        $options  = $this->_initialize($options);
        
        // Mixin the command chain
        $this->mixin(new KMixinCommand($this, $options['command_chain']));

        // Mixin the KMixinClass
        $this->mixin(new KMixinClass($this, 'Controller'));

        // Assign the classname with values from the config
        $this->setClassName($options['name']);

		// Get the methods only for the final controller class
		$thisMethods	= get_class_methods( get_class( $this ) );
		$baseMethods	= get_class_methods( 'KControllerAbstract' );
		$methods		= array_diff( $thisMethods, $baseMethods );

		// Add default display method
		$methods[] = 'read';

		// Iterate through methods and map actions
		foreach ( $methods as $method )
		{
			if ( substr( $method, 0, 1 ) != '_' )
            {
				$this->_methods[] = strtolower($method);
				// auto register public methods as actions
				$this->_actionMap[strtolower($method)] = $method;
			}
		}

		// If the default action is set, register it as such
		$this->registerDefaultAction( $options['default_action'] );
	}

    /**
     * Initializes the options for the object
     * 
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   array   Options
     * @return  array   Options
     */
    protected function _initialize(array $options)
    {
        $defaults = array(
            'default_action'  => 'read',
            'name'          => array(
                        'prefix'    => 'k',
                        'base'      => 'controller',
                        'suffix'    => 'default'
                        ),
            'command_chain' =>  null
        );

        return array_merge($defaults, $options);
    }

	/**
	 * Execute an action by triggering a method in the derived class.
	 *
	 * @param	string The action to perform
	 * @return	mixed|false The value returned by the called method, false in error case.
	 */
	public function execute($action)
	{
		//Convert to lower case for lookup
		$action = strtolower( $action );
		
		//Set the action in the controller
		$this->setAction($action);
		
		//Find the mapped action
		$doMethod = $this->_actionMap['__default'];
		if (isset( $this->_actionMap[$action] )) {
			$doMethod = $this->_actionMap[$action];
		} 
		
		//Create the arguments object
		$args = new ArrayObject();
		$args['notifier']   = $this;
		$args['action']     = $action;
		$args['result']     = false;
		
		if($this->getCommandChain()->run('controller.before.'.$action, $args) === true) {
			$args['result'] = $this->$doMethod();
			$this->getCommandChain()->run('controller.after.'.$action, $args);
		}
		
		return $args['result'];
	}

	/**
	 * Gets the available actions in the controller.
	 *
	 * @return	array Array[i] of action names.
	 */
	public function getActions()
	{
		return $this->_methods;
	}

	/**
	 * Get the action that is was/will be performed.
	 *
	 * @return	 string Action name
	 */
	public function getAction()
	{
		return $this->_action;
	}
	
	/**
	 * Set the action that will be performed.
	 *
	 * @param	string Action name
	 * @return  KControllerAbstract
	 */
	public function setAction($action)
	{
		$this->_action = $action;
		return $this;
	}

	/**
	 * Method to get a reference to the current view and load it if necessary.
	 *
	 * @return	KViewAbstract	A KView object
	 * @throws KControllerException
	 */
	public function getView(array $options = array())
	{
		$application	= KFactory::get('lib.joomla.application')->getName();
		$component 		= $this->getClassName('prefix');
		$viewName		= KRequest::get('get.view', 'cmd', $this->getClassName('suffix'));

		if ( !$view = KFactory::get($application.'::com.'.$component.'.view.'.$viewName, $options) )
		{
            $format = isset($options['format']) ? $options['format'] : 'html';
			throw new KControllerException(
					'View not found [application, component, name, format]:'
                    ." $application, $component, $viewName, $format"
			);
		}
			
		return $view;
	}

	
	/**
	 * Register (map) a action to a method in the class.
	 *
	 * @param	string	The action.
	 * @param	string	The name of the method in the derived class to perform
	 *                  for this action.
	 * @return	KControllerAbstract
	 */
	public function registerAction( $action, $method )
	{
		if ( in_array( strtolower( $method ), $this->_methods ) ) {
			$this->_actionMap[strtolower( $action )] = $method;
		}
		return $this; 
	}
	
	/**
	 * Unregister a action
	 *
	 * @param	string	The action
	 * @return	KControllerAbstract
	 */
	public function unregisterAction( $action )
	{
		unset($this->_actionMap[strtolower($action)]);
		return $this;
	}

	/**
	 * Register the default action to perform if a mapping is not found.
	 *
	 * @param	string The name of the method in the derived class to perform if
	 * 				   a named action is not found.
	 * @return	KControllerAbstract
	 */
	public function registerDefaultAction( $method )
	{
		$this->registerAction( '__default', $method );
		return $this;
	}

	/**
	 * Set a URL for browser redirection.
	 *
	 * @param	string URL to redirect to.
	 * @param	string	Message to display on redirect. Optional, defaults to
	 * 			value set internally by controller, if any.
	 * @param	string	Message type. Optional, defaults to 'message'.
	 * @return	KControllerAbstract
	 */
	public function setRedirect( $url, $msg = null, $type = 'message' )
	{
		//Create the url if no full URL was passed
		if(strrpos($url, '?') === false) {
			$url = 'index.php?option=com_'.$this->getClassName('prefix').'&'.$url;
		}

		$this->_redirect =  JRoute::_($url, false);
		$this->_message	= $msg;
		$this->_messageType	= $type;
		
		return $this;
	}
	
	/**
	 * Returns an array with the redirect url, the message and the message type
	 *
	 * @return array	Named array containing url, message and messageType, or null if no redirect was set
	 */
	public function getRedirect()
	{
		$result = null;
		if(!empty($this->_redirect))
		{
			$result = array(
				'url' 			=> $this->_redirect,
				'message' 		=> $this->_message,
				'messageType' 	=> $this->_messageType,
			);
		} 
		
		return $result;
	}
}
