<?php

namespace Matthias\SymfonyConsoleForm\Tests\Helper;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Copied and modified from https://github.com/phpspec/phpspec/blob/master/features/bootstrap/Console/ApplicationTester.php.
 */
class ApplicationTester
{
    private $application;
    private $input;
    /** @var StreamOutput $output */
    private $output;
    /** @var resource $inputStream */
    private $inputStream;

    private $statusCode;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function run($input, array $options = [])
    {
        if (isset($options['interactive']) && $options['interactive']) {
            $this->input = new InteractiveStringInput($input);
        } else {
            $this->input = new StringInput($input);
            $this->input->setInteractive(false);
        }

        $this->output = new StreamOutput(fopen('php://memory', 'w', false), OutputInterface::VERBOSITY_DEBUG, false);
        $this->output->setDecorated(false);

        $inputStream = $this->getInputStream();
        rewind($inputStream);
        $this->setInputStream($inputStream);

        $this->application->setAutoExit(false);

        $this->disableStty();

        $this->statusCode = $this->application->run($this->input, $this->output);

        return $this->statusCode;
    }

    /**
     * @param bool
     *
     * @return string
     */
    public function getDisplay($normalize = false)
    {
        rewind($this->output->getStream());

        $display = stream_get_contents($this->output->getStream());

        if ($normalize) {
            $display = str_replace(PHP_EOL, "\n", $display);
        }

        return $display;
    }

    /**
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @return StreamOutput
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param string $input
     */
    public function putToInputStream($input)
    {
        fputs($this->getInputStream(), $input);
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return resource
     */
    private function getInputStream()
    {
        if ($this->inputStream === null) {
            $this->inputStream = fopen('php://memory', 'r+', false);
        }

        return $this->inputStream;
    }

    private function setInputStream($inputStream)
    {
        if ($this->input instanceof StreamableInputInterface) {
            $this->input->setStream($inputStream);
        } else {
            // Symfony < 4.0
            $helper = $this->application->getHelperSet()->get('question');
            /* @var $helper QuestionHelper */
            $helper->setInputStream($inputStream);
        }
    }

    private function disableStty()
    {
        $sttyProperty = new \ReflectionProperty('Symfony\Component\Console\Helper\QuestionHelper', 'stty');
        $sttyProperty->setAccessible(true);
        $sttyProperty->setValue(null, false);
    }
}
