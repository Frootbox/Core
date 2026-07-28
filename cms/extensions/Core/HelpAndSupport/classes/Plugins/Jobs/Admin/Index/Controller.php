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
            $payload['jobs'][] = $this->getJobExportData($job);
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
    ): array
    {
        return [
            'id' => $job->getId(),
            'title' => $job->getTitle(),
            'subtitle' => $job->getSubtitle(),
            'titles' => $job->getConfig('titles') ?? [],
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
