<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010023 extends \Frootbox\AbstractMigration
{
    protected $description = 'Räumt die Überschriften auf.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository,
    ): void
    {
        // Fetch texts
        $texts = $textsRepository->fetch();

        foreach ($texts as $text) {

            $string = $text->getText();

            if (preg_match_all('#<h([0-9]+) (.*?)>(.*?)</h([0-9]+)>#s', $string, $matches)) {

                foreach ($matches[0] as $index => $tagline) {

                    $new = '<h' . $matches[1][$index] . '>' . $matches[3][$index] . '</h' . $matches[1][$index] . '>';

                    $string = str_replace($tagline, $new, $string);
                }

                $text->setText($string);
                $text->save();
            }
        }
    }
}
