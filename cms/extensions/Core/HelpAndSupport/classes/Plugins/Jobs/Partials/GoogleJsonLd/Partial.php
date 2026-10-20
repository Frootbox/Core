<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Partials\GoogleJsonLd;

class Partial extends \Frootbox\View\Partials\AbstractPartial
{
    /**
     * @return string
     */
    protected function getPath ( ): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Config\Config $configuration
     * @param \Frootbox\Persistence\Content\Repositories\Texts $textsRepository
     * @param \Frootbox\View\Viewhelper\Json $json
     * @return array[]
     * @throws \JsonException
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function onBeforeRendering(
        \Frootbox\Config\Config $configuration,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository,
        \Frootbox\View\Viewhelper\Json $json,
    ): array
    {
        /**
         * Obtain job
         * @var \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job $job
         */
        $job = $this->getAttribute('Job');

        $date = new \DateTime($job->getDate());
        $title = html_entity_decode($job->getTitle() . ' ' . $job->getSubtitle(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = trim(preg_replace('/[\s\p{Z}]+/u', ' ', $title));

        // Layouts can supply the text fields that are actually shown on the page.
        $descriptionUids = $this->hasAttribute('DescriptionUids')
            ? $this->getAttribute('DescriptionUids')
            : [$job->getUid('text')];
        $description = [];

        foreach ($descriptionUids as $uid) {
            $text = $textsRepository->fetchByUid($uid)?->getText();

            if ($text !== null && trim($text) !== '') {
                $description[] = $text;
            }
        }

        $hiringOrganization = $configuration->get('Ext.Core.HelpAndSupport.Jobs.Google.HiringOrganization')->getData();

        $structuredData = [
            '@context' => 'https://schema.org/',
            '@type' => 'JobPosting',
            'title' => $title,
            'description' => implode("\n", $description),
            'datePosted' => $date->format('Y-m-d'),
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $hiringOrganization['Name'] ?? 'Unknown',
                'sameAs' => !empty($hiringOrganization['Url']) ? $hiringOrganization['Url'] : SERVER_PATH_PROTOCOL,
                'logo' => !empty($hiringOrganization['Logo']) ? SERVER_PATH_PROTOCOL . $hiringOrganization['Logo'] : null,
            ],
        ];

        if ($location = $job->getLocation()) {

            $structuredData['jobLocation'] = [
                '@type' => "Place",
                'address' => [
                    '@type' => "PostalAddress",
                    'streetAddress' => trim($location->getStreet() . ' ' . $location->getStreetNumber()),
                    'addressLocality' => $location->getCity(),
                    'postalCode' => $location->getPostalCode(),
                    'addressCountry' => 'DE',
                ],
            ];
        }

        $structuredData = $json->encode($structuredData);

        return [
            'StructuredData' => $structuredData,
        ];
    }
}
