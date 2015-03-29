<?php
namespace rss\worker\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

use rss\orm\Mapper,
	rss\orm\Finder,
	rss\worker\fetchers\Fetcher;

class FetchAllCommand extends Command {
	private $channelsFinder;

	public function __construct(Finder $cf){
		parent::__construct();
		$this->channelsFinder = $cf;
	}

	protected function configure(){
        $this->setName('fetch-all')
        	->setDescription('Fetchs all RSS and ATOM feeds and store items in DB');
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $channels = $this->channelsFinder->get();
        $command = $this->getApplication()->find('fetch');
        if(is_array($channels)) {
            foreach( $channels as $channel ){
                $output->writeln('Fetching Feed : ' . $channel->id);
                $in = new ArrayInput(['command' => 'fetch', 'id' => $channel->id]);
                $command->run($in, $output);
            }            
        }
    }

}