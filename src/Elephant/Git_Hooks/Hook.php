<?php
namespace Elephant\Git_Hooks;


use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class Hook
 */
class Hook extends Application
{
    private $queue;

    public function __construct($name = 'Hook')
    {
        $queue = new \SplQueue();
        $queue->setIteratorMode(\SplQueue::IT_MODE_DELETE);

        parent::__construct($name);
    }

    public function __invoke()
    {
        return $this->run();
    }

    public function addFunction($function)
    {
        if (!is_callable($function)) {
            throw new \InvalidArgumentException('Argument 1 must be callable!');
        }

        $this->queue[] = $function;
    }

    public function addFunctions(array $functions)
    {
        foreach ($functions as $func) {
            $this->addFunction($func);
        }
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $helper = new HookHelper($output);
        foreach ($this->queue as $function) {
            call_user_func($function, $helper);
        }

        return 0;
    }
}
