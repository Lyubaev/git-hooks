<?php

namespace Elephant\Git_Hooks;

use SplQueue;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;

/**
 * Class Hook
 */
class Hook extends Application
{
    private $logger;
    private $queue;
    private $output;

    public function __construct($name = 'git-hook')
    {
        $this->queue = new SplQueue();
        $this->queue->setIteratorMode(SplQueue::IT_MODE_DELETE);
        $this->output = new ConsoleOutput();
        $this->logger = new ConsoleLogger(
            $this->output,
            array(
                LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::INFO   => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::DEBUG  => OutputInterface::VERBOSITY_NORMAL,
            )
        );

        parent::__construct($name);
    }

    public function __call($name, $args)
    {
        if (method_exists($this->logger, $name)) {
            call_user_func_array(array($this->logger, $name), $args);
            return ;
        }

        $this->renderException(
            new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', __CLASS__, $name)),
            $this->output
        );

        exit(1);
    }

    public function addFunction($function)
    {
        if (is_callable($function)) {
            $this->queue[] = $function;
            return ;
        }

        $this->renderException(
            new \InvalidArgumentException('Argument 1 must be callable!'),
            $this->output
        );

        exit(1);
    }

    public function addFunctions(array $functions)
    {
        foreach ($functions as $func) {
            $this->addFunction($func);
        }
    }

    public function run()
    {
        parent::run(null, $this->output);
    }

    public function doRun()
    {
        $helper = new HookHelper($this->logger);
        foreach ($this->queue as $function) {
            call_user_func($function, $helper);
        }

        return 0;
    }
}
