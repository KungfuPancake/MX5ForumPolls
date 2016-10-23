<?php

use Noodlehaus\Config;
use Xargon\MX5\Chart;
use Xargon\MX5\Database;
use Xargon\MX5\Parser;

require __DIR__ . '/../vendor/autoload.php';

$config = Config::load(__DIR__ . '/../config/config.yml');

$parser = new Parser();
$votings = array();

if ($parser->login()) {
    $pollIds = $parser->getPollIds($config->get('mx5.forumId'));
    foreach($pollIds as $pollId) {
        $votings[] = $parser->getPollData($config->get('mx5.forumId'), $pollId);
    }
}

$database = new Database();
$database->insertVotings($votings);
$votings = $database->seedStatistics($votings);

printf("Parsed %d polls.\n", count($votings));

$chart = new Chart();
$chart->draw($votings);