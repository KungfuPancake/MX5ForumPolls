<?php

namespace Xargon\MX5\Model;

class Voting {
    private $title;
    private $answers = array();

    /**
     * @return mixed
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return array
     */
    public function getAnswers(): array {
        return $this->answers;
    }

    /**
     * @param array $answers
     */
    public function setAnswers(array $answers) {
        $this->answers = $answers;
    }
}