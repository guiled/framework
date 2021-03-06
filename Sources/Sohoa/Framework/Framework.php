<?php

namespace Sohoa\Framework {

    use Hoa\Core\Core;
    use Hoa\Router\Router;
    use Hoa\View\Viewable;
    use Sohoa\Framework\Dispatcher\Basic;
    use Sohoa\Framework\Kit\Kitable;
    use Sohoa\Framework\Router\IRouter;
    use Sohoa\Framework\Session\Session;
    use Sohoa\Framework\View\Greut;
    use Sohoa\Framework\View\Soview;

    class Framework
    {

        /**
         * @var \Sohoa\Framework\Router
         */
        protected $_router = null;

        /**
         * @var \Sohoa\Framework\Dispatcher
         */
        protected $_dispatcher = null;

        /**
         * @var \Hoa\View\Viewable
         */
        protected $_view = null;

        /**
         * @var \Sohoa\Framework\Environnement
         */
        protected $_environnement = null;

        /**
         * @var \Sohoa\Framework\ErrorHandler
         */
        protected $_errorHandler = null;

        /**
         * @var \Sohoa\Framework\Session\Session
         */
        protected $_session      = null;

        /**
         * @var array
         */
        protected $_kit = array();

        /**
         * @var bool
         */
        protected static $_initialize = false;

        public static function initialize($cwd = null)
        {
            if (static::$_initialize === true)
                return;

            static::$_initialize = true;
            /**
             * @var \Hoa\Core\Parameter\Parameter $parameters
             * @var \Hoa\Core\Core $core
             */
            $core       = Core::getInstance();
            $parameters = $core->getParameters();

            if($cwd !== null)
                $parameters->setKeyword('cwd' , $cwd);

            $parameters->setParameter('protocol.Application', '(:cwd:h:)/Application/');
            $parameters->setParameter('protocol.Public', '(:%root.application:)/Public/');
            $parameters->setParameter('namespace.prefix.Application', '(:cwd:h:)/');

            $core->setProtocol();
        }

        /**
         * @param string $environnement
         */
        public function __construct($environnement = 'production')
        {

            static::initialize();

            $this->setRouter()
                 ->setDispatcher()
                 ->setErrorHandler()
                 ->setView()
                 ->setEnvironnement($environnement)
                 ->getRouter()->construct();
        }

        /**
         * @param IRouter $router
         */
        public function setRouter(IRouter $router = null)
        {
            $this->_router = $router ? : new \Sohoa\Framework\Router();
            $this->_router->setFramework($this);

            return $this;
        }

        /**
         * @param Dispatcher $dispatcher
         */
        public function setDispatcher(Dispatcher $dispatcher = null)
        {
            $this->_dispatcher = $dispatcher ? : new Basic();

            return $this;
        }

        /**
         * @param Soview $view
         */
        public function setView(Soview $view = null)
        {
            $this->_view = $view ? : new Greut();

            $this->_view->setRouter($this->_router);
            $this->_view->setFramework($this);

            return $this;
        }

        public function setEnvironnement($useEnvironnement = null, Environnement $environnement = null)
        {
            $this->_environnement = $environnement ? : new Environnement($this, $useEnvironnement);

            return $this;
        }

        public function setSession(Session $session)
        {
            $this->_session = $session ? : new Session();

            return $this;
        }

        /**
         * @return \Hoa\Dispatcher\Basic
         */
        public function getDispatcher()
        {
            return $this->_dispatcher;
        }

        /**
         * @return \Sohoa\Framework\Router
         */
        public function getRouter()
        {
            return $this->_router;
        }

        /**
         * @return \Sohoa\Framework\View\Greut
         */
        public function getView()
        {
            return $this->_view;
        }

        public function getEnvironnement()
        {
            return $this->_environnement;
        }

        public function getSession()
        {
            return $this->_session;
        }

        public function run()
        {
            try {
                $this->_dispatcher->dispatch($this->_router, $this->_view, $this);
            } catch (\Exception $e) {

                $this->_errorHandler->manageError($e);
                $this->_dispatcher->dispatch($this->_errorHandler->getRouter(), $this->_view, $this);
            }
        }

        public function kit($identifier, Kitable $object = null)
        {

            if (empty($identifier))
                throw new \Exception('Kit identifier can\'t be empty');

            if ($object === null)
                if (array_key_exists($identifier, $this->_kit))
                    return $this->_kit[$identifier];
                else
                    throw new Exception('Kit "' . $identifier . '" has not been set');

            $object->setRouter($this->_router);
            $object->setView($this->_view);

            $this->_kit[$identifier] = $object;

            return $this;
        }

        public function getKits()
        {
            return $this->_kit;
        }

        public static function services($identifier, $object = null)
        {

            trigger_error('Framework::services has been deprecated (' . $identifier . ')', E_USER_DEPRECATED);
        }

        /**
         *
         * @return \Sohoa\Framework\ErrorHandler
         */
        public function getErrorHandler()
        {
            return $this->_errorHandler;
        }

        /**
         *
         * @param \Sohoa\Framework\ErrorHandler $errorHandler
         */
        public function setErrorHandler(ErrorHandler $errorHandler = null)
        {
            $this->_errorHandler = $errorHandler ? : new ErrorHandler();
            $this->_errorHandler->setRouter($this->getRouter())
                                ->setFramework($this)
                                ->setDispatcher($this->_dispatcher);

            return $this;
        }

    }

}
