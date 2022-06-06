<?php 
/**
 * 
 */

namespace Frootbox\Persistence;

use Frootbox\Persistence\Repositories\AbstractLogs;

abstract class AbstractLog extends AbstractRow
{
    protected $table = 'logs';
    protected $model = Repositories\AbstractLogs::class;

    protected $logData = [ ];

    /**
     *
     */
    public function __construct(array $record = null, \Frootbox\Db\Db $db = null)
    {
        parent::__construct($record, $db);

        if (!empty($this->data['logdata'])) {

            if (is_array($this->data['logdata'])) {
                $this->logData = $this->data['logdata'];
            }
            else {
                $this->logData = unserialize($this->data['logdata']);

                if (!is_array($this->logData)) {
                    $this->logData = [];
                }
            }
        }
    }

    /**
     *
     */
    public function getLogData(): array
    {
        return $this->logData;
    }

    /**
     *
     */
    public function getLogDataRaw(): ?string
    {
        return $this->data['logdata'];
    }

    /**
     *
     */
    public function onBeforeInsert(): void
    {
        // Serialize logData before insertion
        if (!empty($this->data['logdata']) and is_array($this->data['logdata'])) {
            $this->data['logdata'] = serialize($this->data['logdata']);
        }
    }

    /**
     *
     */
    public function setLogData(array $logData): AbstractLog
    {
        $this->logData = $logData;
        $this->data['logdata'] = serialize($logData);
        $this->changed['logdata'] = true;

        return $this;
    }
}
