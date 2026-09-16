<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Admin\Index;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch job
        $job = $jobsRepository->fetchById($get->get('jobId'));

        $job->delete();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#jobsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Admin\Index\Partials\ListJobs\Partial::class, [
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        d($post);
    }

    /**
     *
     */
    public function downloadJsonAction(
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository,
    ): void
    {
        $jobs = $jobsRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
            'order' => [ 'isSticky DESC', 'orderId DESC' ],
        ]);

        $payload = [
            'pluginId' => $this->plugin->getId(),
            'pageId' => $this->plugin->getPageId(),
            'exportedAt' => date('c'),
            'jobs' => [],
        ];

        foreach ($jobs as $job) {
            $payload['jobs'][] = $this->getJobExportData($job, $textsRepository);
        }

        $filename = 'jobs-export-' . date('Y-m-d-H-i-s') . '.json';

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        return self::getResponse();
    }

    /**
     *
     */
    private function getJobExportData(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job $job,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository,
    ): array
    {
        return [
            'id' => $job->getId(),
            'title' => $job->getTitle(),
            'subtitle' => $job->getSubtitle(),
            'titles' => $job->getConfig('titles') ?? [],
            'display' => $this->getDisplayExportData($job, $textsRepository),
            'locationId' => $job->getLocationId(),
            'location' => $this->getLocationExportData($job),
            'dateStart' => $job->getDataRaw('dateStart'),
            'start' => $job->getConfig('start'),
            'asSoonAsPossible' => !empty($job->getConfig('asSoonAsPossible')),
            'limitation' => $job->getConfig('limitation'),
            'type' => $job->getType(),
            'typeId' => $job->getConfig('typeId'),
            'types' => $job->getTypes(),
            'remote' => [
                'available' => !empty($job->getConfig('Remote.Available')),
            ],
            'salary' => [
                'from' => $job->getConfig('SalaryFrom'),
                'to' => $job->getConfig('SalaryTo'),
            ],
            'link' => $job->getConfig('link'),
            'formId' => $job->getConfig('formId'),
            'tags' => $this->getTagsExportData($job),
            'texts' => $this->getTextsExportData($job),
            'images' => $this->getImagesExportData($job),
            'visibility' => $job->getVisibility(),
            'isSticky' => (bool) $job->getIsSticky(),
            'orderId' => $job->getOrderId(),
            'alias' => $job->getDataRaw('alias'),
            'aliases' => json_decode($job->getDataRaw('aliases') ?? '[]', true) ?: [],
            'uri' => $job->getUri(),
            'config' => $job->getConfig(),
        ];
    }

    /**
     *
     */
    private function getDisplayExportData(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job $job,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository,
    ): array
    {
        $language = $_SESSION['frontend']['language'] ?? DEFAULT_LANGUAGE;
        $where = ['uid' => $job->getUid('title')];

        if (MULTI_LANGUAGE) {
            $where['language'] = $language;
        }

        $text = $textsRepository->fetchOne(['where' => $where]);

        // Match the headline renderer's fallback for repository-based UIDs.
        if (MULTI_LANGUAGE && !$text && $language !== DEFAULT_LANGUAGE) {
            $text = $textsRepository->fetchOne(['where' => ['uid' => $job->getUid('title')]]);
        }

        return [
            'title' => $text?->getConfig('headline') ?: $job->getTitle($language),
            'subtitle' => $text ? ($text->getConfig('subtitle') ?: null) : $job->getSubtitle(),
            'employmentLabel' => $text?->getConfig('supertitle') ?: null,
        ];
    }

    private function getLocationExportData(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job $job,
    ): ?array
    {
        try {
            $location = $job->getLocation();
        }
        catch (\Frootbox\Exceptions\NotFound $e) {
            return null;
        }

        if ($location === null) {
            return null;
        }

        return [
            'id' => $location->getId(),
            'title' => $location->getTitle(),
            'addition' => $location->getAddition(),
            'street' => $location->getStreet(),
            'streetNumber' => $location->getStreetNumber(),
            'zipcode' => $location->getZipcode(),
            'city' => $location->getCity(),
        ];
    }

    /**
     *
     */
    private function getTagsExportData(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job $job,
    ): array
    {
        $tags = [];

        foreach ($job->getTags() as $tag) {
            $tags[] = $tag->getTag();
        }

        return $tags;
    }

    /**
     * @return list<string>
     */
    private function getImagesExportData(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job $job,
    ): array
    {
        $images = [];

        // Include the custom job header image used by customer layouts.
        foreach ([ 'topimage' ] as $segment) {
            $file = $job->getFileByUid($segment, [ 'fallbackLanguageDefault' => true ]);

            if ($file !== null) {
                $images[] = $file->getUriThumbnail();
            }
        }

        return $images;
    }

    /**
     *
     */
    private function getTextsExportData(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job $job,
    ): array
    {
        $texts = [];

        foreach ([ 'teaser-text', 'teaser', 'text', 'text-above-columns', 'text-tasks', 'text-profile', 'text-benefits', 'text-below' ] as $segment) {
            $texts[$segment] = $job->getTextByUid($segment);
        }

        return $texts;
    }
}
