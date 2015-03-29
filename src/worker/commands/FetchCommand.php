<?php
namespace rss\worker\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use rss\orm\Mapper,
	rss\orm\Finder,
	rss\worker\fetchers\Fetcher;

class FetchCommand extends Command {
	private $mapper;
	private $channelsFinder;
	private $itemsFinder;
	private $channel;
	private $out;

	public function __construct(Mapper $m, Finder $cf, Finder $if){
		parent::__construct();
		$this->mapper = $m;
		$this->channelsFinder = $cf;
		$this->itemsFinder = $if;
	}

	protected function configure(){
        $this->setName('fetch')
        	->setDescription('Fetchs an RSS or ATOM feed and store items in DB')
            ->addArgument('id', InputArgument::REQUIRED,
                'The id of the feeds channel on the DB');
    }

    protected function execute(InputInterface $input, OutputInterface $output){
    	$this->out = $output;
        $id = $input->getArgument('id');
        $this->channel = $this->channelsFinder
        	->getById($id);
        if(is_null($this->channel)){
	        $this->log('ERROR: No channel having the id ' . $id . ' !');
        } else {
       		$fetched = $this->fetchItems();
       		if($fetched){
                try {
                    $this->mapper->persist($this->channel);
            		$this->log('INFO: Added Successfully !');
                } catch (\Exception $e){
	        		$this->log('ERROR: while persisting : '. $e->getMessage());
	        	}
       		} else {
                $this->log('INFO: Nothing to add');
            }
        }
    }

    protected function log($msg){
    	$this->out->writeln($msg);
    }

    protected function fetchItems(){
        $done = true;
        $xmlString = file_get_contents($this->channel->feedLink);
        $hash = md5($xmlString);
        if($this->channel->lastHash != $hash){
            $this->channel->lastHash = $hash;
            if( false === $xmlString ){
                $this->log('ERROR: Could not fetch the XML from the URL : ' . $this->channel->feedLink);
            } else {
                $fetcher = null;
                $xml = null;
                try {
                    $xml = new \SimpleXMLElement($xmlString);
                } catch (\Exception $e){
                    $this->log('ERROR: Could not parse the XML !');
                    $done = false;
                }
                if(!is_null($xml) && $done){
                    try {
                        $fetcher = new Fetcher($xml, $this->channel);
                        $fetcher->fetch();
                    } catch (\Exception $e){
                        $this->log('ERROR: ' . $e->getMessage());
                        $done = false;
                    }
                }
            }
        } else {
            $done = false;
        }
        return $done;
    }
    
}