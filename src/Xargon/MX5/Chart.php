<?php

namespace Xargon\MX5;

use CpChart\Chart\Data;
use CpChart\Chart\Image;
use Xargon\MX5\Model\Voting;

class Chart {
    const OUTPUT_PATH = __DIR__ . '/../../../output/mx5.png';
    const FONT_PATH = __DIR__ . '/../../../fonts/DroidSans-Bold.ttf';

    /**
     * Draw the poll chart
     *
     * @param $votings Voting[]
     */
    public static function draw($votings) {
        if (count($votings) > 0) {
            $answers = array();
            $answerData = array();
            $voteData = array();

            foreach ($votings as $voting) {
                foreach ($voting->getAnswers() as $answer) {
                    $answers[] = $answer;
                }
            }

            usort($answers, function ($a, $b) {
                if ($a['votes'] == $b['votes']) {
                    return strcmp(strtolower($a['text']), strtolower($b['text']));
                }
                return ($a['votes'] > $b['votes']) ? -1 : 1;
            });

            foreach ($answers as $answer) {
                $answerText = sprintf(
                    '%s (%d/%d/%d)',
                    $answer['text'],
                    $answer['last60'],
                    $answer['last720'],
                    $answer['last2880']
                );
                $answerData[] = $answerText;
                $voteData[] = $answer['votes'];
            }

            $data = new Data();
            $data->addPoints($voteData, 'Stimmen');
            $data->setAxisName(0, 'Stimmen');
            $data->addPoints($answerData, 'Teilnehmer');
            $data->setSerieDescription('Teilnehmer', 'Teilnehmer');
            $data->setAbscissa('Teilnehmer');
            $data->setAbscissaName('Teilnehmer');

            $myPicture = new Image(1000, 1600, $data);
            $myPicture->setFontProperties(
                array(
                    'FontName' => self::FONT_PATH,
                    'FontSize' => 10
                )
            );

            $myPicture->setGraphArea(250, 30, 950, 1520);
            $myPicture->drawScale(
                array(
                    "CycleBackground" => true,
                    "DrawSubTicks" => true,
                    "GridR" => 0,
                    "GridG" => 0,
                    "GridB" => 0,
                    "GridAlpha" => 10,
                    "Pos" => SCALE_POS_TOPBOTTOM,
                    "Mode" => SCALE_MODE_START0,
                    //"MinDivHeight" => 50
                    "Factors" => array(1)
                ));

            /* Turn on shadow computing */
            // $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));

            $palette = array();
            for ($count = 0; $count < count($answers); $count++) {
                if ($count < 12) {
                    $palette[] = array(
                        "R" => 172,
                        "G" => 172,
                        "B" => 172,
                        "Alpha" => 100
                    );
                } else {
                    $palette[] = array(
                        "R" => 207,
                        "G" => 207,
                        "B" => 207,
                        "Alpha" => 100
                    );
                }
            }

            $myPicture->drawBarChart(
                array(
                    "DisplayPos" => LABEL_POS_INSIDE,
                    "DisplayValues" => true,
                    "Rounded" => true,
                    "Surrounding" => 10,
                    "OverrideColors" => $palette
                )
            );

            /* Write the legend */
            // $myPicture->drawLegend(570,215,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));

            /* Render the picture (choose the best way) */
            $myPicture->drawText(400, 1550, "Stimmen in den letzten (1/12/48) Stunden. Stand: " . date("H:i, d.m.Y"));
            $myPicture->render(self::OUTPUT_PATH);
        } else {
            $image = imagecreatetruecolor(1000, 400);
            $bgColor = imagecolorallocate($image, 240, 240, 240);
            imagefill($image, 0, 0, $bgColor);
            $textColor = imagecolorallocate($image, 0, 0, 0);
            imagettftext($image, 12, 0, 350, 200, $textColor, self::FONT_PATH, "Keine Threads gefunden (" . date("H:i, d.m.Y") . ")");
            imagepng($image, self::OUTPUT_PATH);
        }
    }
}