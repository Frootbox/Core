<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;

class StaticPages extends AbstractViewhelper
{

    protected $arguments = [
        'getUri' => [
            'classFragment',
            ['name' => 'action', 'default' => 'index'],
            ['name' => 'payloadData', 'default' => [ ] ],
            ['name' => 'query', 'default' => [ ] ],
            ['name' => 'filename', 'default' => '' ],
        ]
    ];

    /**
     *
     */
    public function getUriAction(
        $classFragment,
        $action,
        array $payloadData,
        array $query,
        string $filename,
        \Frootbox\Payload $payload,
    ): string
    {
        // Obtain class structure
        $classData = explode('/', $classFragment);

        $uri = 'static/Ext/' . $classData[0] . '/' . $classData[1] . '/' . $classData[2] . '/' . $action;

        // Inject payload into uri
        if (!empty($payloadData)) {

            $payload->clear();
            $payload->addData($payloadData);

            $payloadData = $payload->export();

            $uri .= '/qs';

            foreach ($payloadData as $key => $value) {
                $uri .= '/' . $key . '/' . urlencode($value);
            }
        }

        if (!empty($filename)) {
            $uri .= '/qt/' . $filename;
        }

        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        return $uri;
    }
}
