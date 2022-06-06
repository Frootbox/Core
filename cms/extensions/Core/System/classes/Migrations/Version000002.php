<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version000002 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktiviert die erweiterten SEO-Optionen.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository,
        \Frootbox\Persistence\Repositories\Pages $pagesRepository
    ): void
    {
        // Fetch pages
        $result = $pagesRepository->fetch();

        foreach ($result as $page) {

            $seoTitle = $page->getConfig('seo.title') ?? null;
            $seoKeywords = $page->getConfig('seo.keywords') ?? null;
            $seoDescription = $page->getConfig('seo.description') ?? null;

            if (!empty($seoTitle) or !empty($seoKeywords) or !empty($seoDescription)) {

                $alias = $aliasesRepository->fetchOne([
                    'where' => [
                        'pageId' => $page->getId(),
                        'status'=> 200,
                    ],
                ]);

                if (empty($alias)) {
                    continue;
                }

                $alias->addConfig([
                    'seo' => [
                        'title' => $seoTitle,
                        'keywords' => $seoKeywords,
                        'description' => $seoDescription,
                    ],
                ]);

                $alias->save();
            }
        }
    }
}
