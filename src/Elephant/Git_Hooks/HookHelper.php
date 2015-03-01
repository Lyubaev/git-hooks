<?php

namespace Elephant\Git_Hooks;


use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;

/**
 * Class HookHelper
 *
 * @property-read array $config    Конфигурация приложения.
 * @property-read array $arguments Аргументы командной строки.
 */
class HookHelper
{
    private $logger;
    private $hook = '';
    private $argv = array();
    private $data = array();
    private $config = array();

    public function __construct(ConsoleLogger $logger = null)
    {
        $this->logger = (null === $logger) ? new ConsoleLogger(new ConsoleOutput()) : $logger;

        // Fetch raw arguments.
        $this->argv = $_SERVER['argv'];
        $this->hook = array_pop(explode('/', array_shift($this->argv)));

        $git_dir = $_SERVER['PWD'] . DIRECTORY_SEPARATOR . $_SERVER['GIT_DIR'];
        $hooks_dir = $git_dir . DIRECTORY_SEPARATOR . 'hooks';

        // Set user options
        if (is_readable($hooks_dir . DIRECTORY_SEPARATOR . 'hooks-config.yaml')) {
            $this->config = Yaml::parse(file_get_contents($hooks_dir . DIRECTORY_SEPARATOR . 'hooks-config.yaml'));
        }
    }

    public function __call($name, $args)
    {
        if (method_exists($this->logger, $name)) {
            call_user_func_array(array($this->logger, $name), $args);
            return ;
        }

        throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', __CLASS__, $name));
    }

    public function __get($name)
    {
        switch ($name) {
            case 'config':
                return $this->config;
            case 'arguments':
                return $this->argv;
            case 'hook':
                return $this->hook;
            default:
                throw new \RuntimeException("Property '$name' not found!");
        }
    }

    public function __set($name, $value)
    {
        throw new \RuntimeException(
            sprintf('The object \'%s\' is closed for writing!', __CLASS__)
        );
    }

    public function hasData($key)
    {
        return isset($this->data[$key]);
    }

    public function sendData($key, $data, $replace = false)
    {
        if (true === $replace || !isset($this->data[$key])) {
            $this->data[$key] = $data;
        }
    }

    public function receiveData($key = null)
    {
        if (null === $key) {
            return $this->data;
        }

        if ($this->hasData($key)) {
            return $this->data[$key];
        }

        return null;
    }

    public function process(array $arguments, $cwd = null)
    {
        $processBuilder = new ProcessBuilder($arguments);
        null !== $cwd && $processBuilder->setWorkingDirectory($cwd);
        $process = $processBuilder->getProcess();
        $process->run();

        return $process;
    }

    private function extractIndexedFiles()
    {
        $output = array();
        $rc = 0;

        # If rc = 0, set HEAD.
        exec('git rev-parse --verify HEAD 2> /dev/null', $output, $rc);

        # Initial commit: diff against an empty tree object
        $against = (0 === $rc) ? 'HEAD' : '4b825dc642cb6eb9a060e54bf8d69288fbee4904';

        exec("git diff-index --cached --name-only $against --diff-filter=AM", $output);

        return $output;
    }

    public function getFiles()
    {
        switch ($this->hook) {
            case 'pre-commit':
            case 'prepare-commit-msg':
                return $this->extractIndexedFiles();
            default:
                return null;
        }
    }
}
