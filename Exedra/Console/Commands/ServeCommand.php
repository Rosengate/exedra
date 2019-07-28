<?php

namespace Exedra\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ServeCommand extends Command
{
    protected $path;

    public function __construct($path)
    {
        $this->path = (string)$path;

        parent::__construct();
    }

    public function configure()
    {
        $this->setName('app:serve');
        $this->setDescription('Serve the application');
        $this->addArgument('port', InputArgument::OPTIONAL, 'Port to be served on', 9000);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = new QuestionHelper();

        $validator = function ($answer) {
            if ($answer == '')
                return $answer;

            if (!is_numeric($answer))
                throw new \RuntimeException('Please specify only integer');

            if ($answer < 7000)
                throw new \RuntimeException('Please specify port greater than 7000');
            else if ($answer > 65500)
                throw new\ RuntimeException('Please specify port smaller than 65500');

            return $answer;
        };

        $port = $input->getArgument('port');

        try {
            $validator($port);
        } catch (\RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $questionHelper->ask($input, $output, (new Question('Set [<comment>port</comment>] : '))->setValidator($validator));
        }

        $dir = (string)$this->path;

        if (!file_exists($dir))
            return $output->writeln('Public folder doesn\'t exist. (' . $dir . ')');

        if (isset($arguments['router']))
            $router = ' ' . $arguments['router'];
        else
            $router = '';

        chdir($dir);

        $output->writeln('PHP server started at localhost:' . $port . ' on folder ' . realpath($dir));

        exec('php -S localhost:' . $port . $router);
    }
}