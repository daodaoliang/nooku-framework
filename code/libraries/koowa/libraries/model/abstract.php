<?php
/**
 * Koowa Framework - http://developer.joomlatools.com/koowa
 *
 * @copyright	Copyright (C) 2007 - 2013 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/joomlatools/koowa for the canonical source repository
 */

/**
 * Abstract Model
 *
 * @author  Johan Janssens <http://nooku.assembla.com/profile/johanjanssens>
 * @package Koowa\Library\Model
 */
abstract class KModelAbstract extends KObject implements KModelInterface, KCommandCallbackDelegate
{
    /**
     * A state object
     *
     * @var KModelStateInterface
     */
    private $__state;

    /**
     * Entity count
     *
     * @var integer
     */
    protected $_count;

    /**
     * Entity object
     *
     * @var KModelEntityInterface
     */
    protected $_entity;

    /**
     * Constructor
     *
     * @param  KObjectConfig $config    An optional KObjectConfig object with configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        // Set the state identifier
        $this->__state = $config->state;

        // Mixin the behavior interface
        $this->mixin('lib:behavior.mixin', $config);

        // Mixin the event interface
        $this->mixin('lib:event.mixin', $config);
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   KObjectConfig $config An optional KObjectConfig object with configuration options
     * @return  void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'state'            => 'lib:model.state',
            'command_chain'    => 'lib:command.chain',
            'command_handlers' => array('lib:command.handler.event'),
        ));

        parent::_initialize($config);
    }

    /**
     * Fetch an entity from the data store
     *
     * @return KModelEntityInterface
     */
    final public function fetch()
    {
        if(!isset($this->_entity))
        {
            $context = $this->getContext();
            $context->entity  = null;

            if ($this->invokeCommand('before.fetch', $context) !== false)
            {
                $context->entity = $this->_actionFetch($context);
                $this->invokeCommand('after.fetch', $context);
            }

            $this->_entity = KObjectConfig::unbox($context->entity);
        }

        return $this->_entity;
    }

    /**
     * Create a new entity for the data source
     *
     * @return  KModelEntityInterface
     */
    final public function create()
    {
        $context = $this->getContext();
        $context->entity  = null;

        if ($this->invokeCommand('before.create', $context) !== false)
        {
            $context->entity = $this->_actionCreate($context);
            $this->invokeCommand('after.create', $context);
        }

        $this->_entity = KObjectConfig::unbox($context->entity);

        return $this->_entity;
    }

    /**
     * Get the total number of entities
     *
     * @return  int
     */
    final public function count()
    {
        if(!isset($this->_count))
        {
            $context = $this->getContext();
            $context->count = null;

            if ($this->invokeCommand('before.count', $context) !== false)
            {
                $context->count = $this->_actionCount($context);
                $this->invokeCommand('after.count', $context);
            }

            $this->_count = KObjectConfig::unbox($context->count);
        }

        return $this->_count;
    }

    /**
     * Reset the model data and state
     *
     * @param  boolean $default If TRUE use defaults when resetting the state. Default is TRUE
     * @return KModelAbstract
     */
    final public function reset($default = true)
    {
        $context        = $this->getContext();
        $context->count = null;

        if ($this->invokeCommand('before.reset', $context) !== false)
        {
            $this->_actionReset($context);
            $this->invokeCommand('after.reset', $context);
        }

        $this->_count = KObjectConfig::unbox($context->count);

        return $this;
    }

    /**
     * Invoke a command handler
     *
     * @param string            $method   The name of the method to be executed
     * @param KCommandInterface  $command   The command
     * @return mixed Return the result of the handler.
     */
    public function invokeCommandCallback($method, KCommandInterface $command)
    {
        return $this->$method($command);
    }

    /**
     * Set the model state values
     *
     * @param  array $values Set the state values
     * @return KModelAbstract
     */
    public function setState(array $values)
    {
        $this->getState()->setValues($values);
        return $this;
    }

    /**
     * Get the model state object
     *
     * @throws UnexpectedValueException
     * @return KModelStateInterface  The model state object
     */
    public function getState()
    {
        if(!$this->__state instanceof KModelStateInterface)
        {
            $this->__state = $this->getObject($this->__state, array('model' => $this));

            if(!$this->__state instanceof KModelStateInterface)
            {
                throw new UnexpectedValueException(
                    'State: '.get_class($this->__state).' does not implement KModelStateInterface'
                );
            }
        }

        return $this->__state;
    }

    /**
     * Get the model context
     *
     * @return  KModelContext
     */
    public function getContext()
    {
        $context = new KModelContext();
        $context->setSubject($this);
        $context->setState($this->getState());

        return $context;
    }

    /**
     * Create a new entity for the data source
     *
     * @param KModelContext $context A model context object
     *
     * @return KModelEntityInterface The entity
     */
    protected function _actionCreate(KModelContext $context)
    {
        return $this->_entity;
    }

    /**
     * Fetch a new entity from the data source
     *
     * @param KModelContext $context A model context object
     * @return KModelEntityInterface The entity
     */
    protected function _actionFetch(KModelContext $context)
    {
        return $this->_entity;
    }

    /**
     * Get the total number of entities
     *
     * @param KModelContext $context A model context object
     * @return integer  The total number of entities
     */
    protected function _actionCount(KModelContext $context)
    {
        return $this->_count;
    }

    /**
     * Reset the model
     *
     * @param KModelContext $context A model context object
     * @return void
     */
    protected function _actionReset(KModelContext $context)
    {
        $this->_entity = null;
        $this->_count  = null;
    }

    /**
     * Supports a simple form Fluent Interfaces. Allows you to set states by using the state name as the method name.
     *
     * For example : $model->sort('name')->limit(10)->fetch();
     *
     * @param   string  $method Method name
     * @param   array   $args   Array containing all the arguments for the original call
     * @return  KModelAbstract
     *
     * @see http://martinfowler.com/bliki/FluentInterface.html
     */
    public function __call($method, $args)
    {
        if ($this->getState()->has($method))
        {
            $this->getState()->set($method, $args[0]);
            return $this;
        }

        return parent::__call($method, $args);
    }

    /**
     * Preform a deep clone of the object.
     *
     * @retun void
     */
    public function __clone()
    {
        parent::__clone();

        $this->__state = clone $this->__state;
    }

    /**
     * Fetch the data when model is invoked.
     *
     * @return KModelEntityInterface
     */
    public function __invoke()
    {
        return $this->fetch();
    }
}