<?php

namespace Xargon\MX5;

use DOMElement;
use GuzzleHttp\Client;
use Noodlehaus\Config;
use Xargon\MX5\Model\Voting;

class Parser {
    private $client;
    private $config;

    public function __construct() {
        $this->config = Config::load(__DIR__ . '/../../../config/config.yml');

        $this->client = new Client(array(
            'timeout' => 5,
            'base_uri' => $this->config->get('mx5.baseUri'),
            'cookies' => true
        ));

        // Aquire initial cookie
        $this->client->get('dcboard.php');
    }

    /**
     * Login using configured credentials
     *
     * @return bool Login successful
     */
    public function login() {
        $response = $this->client->post($this->config->get('mx5.baseEndpoint'), array(
            'query' => array(
                'az' => 'login',
                'auth_az' => 'login_now',
                'username' => $this->config->get('mx5.username'),
                'password' => $this->config->get('mx5.password')
            )
        ));

        $domDocument = new \DOMDocument();
        @$domDocument->loadHTML($response->getBody());
        foreach ($domDocument->getElementsByTagName("p") as $tag) {
            /* @var $tag DOMElement */
            if ($tag->getAttribute("class") == "dcerrorsubject") {
                break;
            }
            if ($tag->getAttribute("class") == "dcmessage") {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve a list of polls (=threads) that contain a poll and return their IDs
     *
     * @param $forumId int The forum ID that should be used as root
     * @return array A list of thread IDs
     */
    public function getPollIds($forumId) {
        $pageCount = 0;
        $pollIds = array();

        $response = $this->client->get($this->config->get('mx5.baseEndpoint'), array(
           'query' => array(
               'az' => 'show_topics',
               'forum' => $forumId
           )
        ));

        $domDocument = new \DOMDocument();
        @$domDocument->loadHTML($response->getBody());
        foreach ($domDocument->getElementsByTagName("td") as $tag) {
            /* @var $tag DOMElement */
            if ($tag->getAttribute("class") == "dcpagelink") {
                foreach ($tag->childNodes as $child) {
                    if ($child->nodeName == "a") {
                        if ($child->nodeValue > $pageCount) {
                            $pageCount = $child->nodeValue;
                        }
                    }
                }
                break;
            }
        }

        if ($pageCount > 0) {
            for ($currentPage = 1; $currentPage <= $pageCount; $currentPage++) {
                $response = $this->client->get($this->config->get('mx5.baseEndpoint'), array(
                    'az' => 'show_topics',
                    'forum' => $forumId,
                    'page' => $currentPage
                ));

                $domDocument = new \DOMDocument();
                @$domDocument->loadHTML($response->getBody());
                foreach ($domDocument->getElementsByTagName("td") as $tag) {
                    /* @var $tag DOMElement */
                    if ($tag->getAttribute("width") == "10") {
                        foreach ($tag->childNodes as $child) {
                            /* @var $child DOMElement */
                            if ($child->getAttribute("href")) {
                                foreach ($child->childNodes as $img) {
                                    /* @var $img DOMElement */
                                    if ($img->nodeName == "img") {
                                        if (preg_match("/poll/", $img->getAttribute("src"))) {
                                            $matches = array();
                                            preg_match("/topic_id=([0-9]*)/", $child->getAttribute("href"), $matches);
                                            if ($matches[1]) {
                                                array_push($pollIds, $matches[1]);
                                            }
                                            break;
                                        }
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $pollIds;
    }

    /**
     * Retrieve poll data for a single poll (=thread)
     *
     * @param $forumId int The forum ID
     * @param $pollId int The poll ID
     * @return Voting The poll data
     */
    public function getPollData($forumId, $pollId) {
        $voting = new Voting();
        $response = $this->client->get($this->config->get('mx5.baseEndpoint'), array(
            'query' => array(
                'az' => 'show_topic',
                'forum' => $forumId,
                'topic_id' => $pollId
            )
        ));

        $domDocument = new \DOMDocument();
        @$domDocument->loadHTML($response->getBody());
        foreach ($domDocument->getElementsByTagName("td") as $tag) {
            /* @var $tag DOMElement */
            if ($tag->getAttribute("class") == "dcmenu" && preg_match("/Betreff:/", $tag->nodeValue)) {
                $matches = array();
                preg_match("/\"([^\"]*)\"/", $tag->nodeValue, $matches);
                if ($matches[1]) {
                    $voting->setTitle(utf8_decode($matches[1]));
                } else {
                    return null;
                }
            }
        }

        $answers = array();
        foreach ($domDocument->getElementsByTagName("td") as $tag) {
            /* @var $tag DOMElement */
            if (preg_match("/Abstimmungsergebnis/", $tag->nodeValue) && $tag->getAttribute("colspan") == "3") {
                $parent = $tag->parentNode->parentNode->childNodes->item(1);
                $text = utf8_decode($parent->childNodes->item(0)->nodeValue);
                $matches = array();
                preg_match("/\\(([0-9]*).*\\)/", $parent->childNodes->item(1)->nodeValue, $matches);
                if ($text && isset($matches[1])) {
                    $answers[] = array('text' => $text, 'votes' => $matches[1]);
                }
            }
        }
        $voting->setAnswers($answers);

        return $voting;
    }
}