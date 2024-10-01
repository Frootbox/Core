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
     * @return array[]
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function onBeforeRendering(
        \Frootbox\Config\Config $configuration,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository,
    ): array
    {
        /**
         * Obtain job
         * @var \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job $job
         */
        $job = $this->getAttribute('Job');

        $date = new \DateTime($job->getDate());


        $hiringOrganization = $configuration->get('Ext.Core.HelpAndSupport.Jobs.Google.HiringOrganization')->getData();

        $structuredData = [
            '@context' => 'https://schema.org/',
            '@type' => 'JobPosting',
            'title' => trim($job->getTitle() . ' ' . $job->getSubtitle()),
            'description' => $textsRepository->fetchByUid($job->getUid('text'))?->getText(),
            'datePosted' => $date->format('Y-m-d'),
            'validThrough' => (date('Y') + 1) . $date->format('-m-d'),
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $hiringOrganization['Name'] ?? 'Unknown',
                'sameAs' => $hiringOrganization['Url'] ?? 'Unknown',
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

        $structuredData = stripslashes(json_encode($structuredData));

        return [
            'StructuredData' => $structuredData,
        ];
    }
}