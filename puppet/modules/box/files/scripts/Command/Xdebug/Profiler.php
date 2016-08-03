<?php
namespace Command\Xdebug;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Profiler extends Xdebug
{
    protected function configure()
    {
        $output_dir  = \Helper\Ini::getPHPConfig('xdebug.profiler_output_dir');

        $this->setName('xdebug:profiler')
             ->setDescription('Toggle profiling')
            ->setHelp(<<<EOF
To profile your PHP code, you have to start the Xdebug profiler first:

    <info>box xdebug:profiler start</info>

Every PHP file that gets executed will now generate profiling information in the form of a cachegrind file.
You can analyze these files using an application like KCacheGrind, MCG or PHPStorm.

The box has Webgrind pre-installed and configured at http://webgrind.joomla.box. However, please note that it
might fail on complex and large cachegrind files. A desktop app is recommended.

By default, cachegrind files will be stored in $output_dir.

Remember to disable profiling once you are done:

    <info>box xdebug:profiler stop</info>

For more information about Xdebug profiling, refer to the official documentation at <info>http://www.xdebug.org/docs/profiler</info>.
EOF
            )
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'start|stop'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $action = strtolower($input->getArgument('action'));

        if (!in_array($action, array('start', 'stop'))) {
            throw new \RuntimeException('Action must be one of start|stop');
        }

        if (!extension_loaded('xdebug') && $action == 'start')
        {
            $output->writeln('[<comment>notice</comment>] XDebug is not loaded. Enabling ..');

            $this->getApplication()->find('xdebug:enable')->run(new ArrayInput(array('command' => 'xdebug:enable')), new NullOutput());
        }

        $inis = \Helper\Ini::findIniFiles(array('zray.ini'), false);
        $ini  = array_pop($inis);
        if ($action == 'start' && file_exists($ini))
        {
            $contents = file_get_contents($ini);
            if (preg_match('/^zend_extension\\s*=\\s*.+zray\\.so/', $contents))
            {
                $output->writeln('[<comment>warning</comment>] <info>Zend Z-Ray</info> is enabled. This will generate a lot of profiler output!');
                $output->writeln('[<comment>warning</comment>] You can disable <info>Zend Z-Ray</info> with this command: <comment>box zray:disable</comment>');
            }
        }

        $current = \Helper\Ini::getPHPConfig('xdebug.profiler_enable');
        $value   = $action == 'start' ? 1 : 0;
        $word    = $action == 'start' ? 'started' : 'stopped';

        if ($current == $value)
        {
            $output->writeln("Profiler has already been $word");
            exit();
        }

        $files = \Helper\Ini::findIniFiles(array('custom.ini', '99-custom.ini'), false);

        foreach($files as $file) {
            \Helper\Ini::update($file, 'xdebug.profiler_enable', $value);
        }

        $this->getApplication()->find('server:restart')->run(new ArrayInput(array('command' => 'server:restart')), new NullOutput());

        $output_dir  = \Helper\Ini::getPHPConfig('xdebug.profiler_output_dir');

        $output->writeln("XDebug profiler has been $word");

        if ($action == 'start') {
            $output->writeln("Profiling information will be written to <info>$output_dir</info>");
        }
    }
}