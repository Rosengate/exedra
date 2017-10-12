<?php
namespace Exedra\Console\Commands;

use Exedra\Routing\Group;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RouteListCommand extends Command
{
    protected $group;

    public function __construct(Group $group, $name = 'app:routes')
    {
        $this->group = $group;

        parent::__construct($name);
    }

    public function configure()
    {
        $this->setDescription('List all routes');
        $this->addArgument('property', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Route property(s)', array('name', 'method', 'tag', 'uri'));
        $this->addOption('name', null, InputOption::VALUE_REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);

        $header = $input->getArgument('property');

        $table->setHeaders($header);

        $previousRoute = null;

        $total = 0;

        $this->group->each(function(\Exedra\Routing\Route $route) use($table, $header, $input, &$total)
        {
            $routeName = $route->getAbsoluteName();

            $methods = $route->getMethod();

            if(count($methods) == 4)
                $methods = 'any';
            else
                $methods = implode(', ', $methods);

            // list only routes that is executable
            if(!$route->hasExecution())
                return;

            if($name = $input->getOption('name'))
                if(strpos($routeName, $name) !== 0)
                    return;

            $row = array();

            $data = array(
                'name' => $route->getAbsoluteName(),
                'method' => $methods,
                'uri' => '/'.$route->getPath(true),
                'tag' => $route->hasProperty('tag') ? $route->getProperty('tag') : ''
            );

            foreach($header as $col) {
                $col = strtolower($col);

                if(!isset($data[$col]))
                    throw new \RuntimeException('Can\'t find property : ' . $col);

                $row[] = $data[$col];
            }

            $table->addRow($row);

            $total++;
        }, true);

        if($total == 0)
            $table->addRow(array(new TableCell('<info>Can\'t find any route</info>', array(
                'colspan' => count($header)
            ))));

        $output->writeln('Showing list of routes : ');

        $table->render();
    }
}