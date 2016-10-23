<?php

namespace Xargon\MX5;

use Noodlehaus\Config;
use Xargon\MX5\Model\Voting;

/**
 * Class Database
 *
 * Hides all database specific code
 *
 * @TODO Use a sane approach (ORM or something)
 *
 * @package Xargon\MX5
 */
class Database {
    private $pdo;

    public function __construct() {
        $dbConfig = Config::load(__DIR__ . '/../../../config/config.yml');
        $this->pdo = new \PDO(
            sprintf('mysql:dbname=%s;host=%s',
                $dbConfig->get('database.name'),
                $dbConfig->get('database.host')
            ),
            $dbConfig->get('database.username'),
            $dbConfig->get('database.password')
        );
    }

    /**
     * Insert parsed voting
     *
     * @param $votings Voting[]
     */
    public function insertVotings($votings) {
        $query = $this->pdo->prepare("INSERT INTO `voting` (`name`, `votes`, `parsed`) VALUES (?, ?, ?)");
        foreach ($votings as $voting) {
            foreach ($voting->getAnswers() as $answer) {
                $query->execute(array($answer['text'], $answer['votes'], time()));
            }
        }
    }

    /**
     * Seed statistics into votings for display purposes
     *
     * @param $votings Voting[]
     * @return mixed Votings with statistics seeded into them
     */
    public function seedStatistics($votings) {
        $query = $this->pdo->prepare("SELECT `votes` FROM `voting` WHERE `name` = ? AND `parsed` <= ? ORDER BY `parsed` DESC LIMIT 1");
        foreach ($votings as $votingKey => $voting) {
            $answers = $voting->getAnswers();
            foreach ($answers as $answerKey => $answer) {
                // 59 minutes
                $query->execute(array($answer['text'], time() - 3540));
                $results = $query->fetchAll();
                if (isset($results[0])) {
                    $answers[$answerKey]['last60'] = $answer['votes'] - $results[0]['votes'];
                } else {
                    $answers[$answerKey]['last60'] = "-";
                }
                // 12 hours
                $query->execute(array($answer['text'], time() - 43140));
                $results = $query->fetchAll();
                if (isset($results[0])) {
                    $answers[$answerKey]['last720'] = $answer['votes'] - $results[0]['votes'];
                } else {
                    $answers[$answerKey]['last720'] = "-";
                }
                // 48 hours
                $query->execute(array($answer['text'], time() - 172740));
                $results = $query->fetchAll();
                if (isset($results[0])) {
                    $answers[$answerKey]['last2880'] = $answer['votes'] - $results[0]['votes'];
                } else {
                    $answers[$answerKey]['last2880'] = "-";
                }
            }
            $votings[$votingKey]->setAnswers($answers);
        }
        return $votings;
    }
}
