<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Widgets\Table;

class Widget extends \Frootbox\Persistence\Content\AbstractWidget
{
    /**
     * @return int
     */
    public function getColCount(): int
    {
        return !empty($this->getConfig('columns')) ? (int) $this->getConfig('columns') : 3;
    }

    /**
     * Get widgets root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @return int
     */
    public function getRowCount(): int
    {
        return !empty($this->getConfig('rows')) ? (int) $this->getConfig('rows') : 3;
    }

    /**
     * Get size of editing modal
     *
     * @return string
     */
    public function getSize(): string
    {
        return 'xl';
    }

    /**
     *
     */
    public function getTableData(): array
    {
        $rows = [];
        $header = [];

        if ($this->getConfig('withHeader')) {

            $headerdata = $this->getConfig('headerdata');

            for ($i = 0; $i < $this->getColCount(); ++$i) {
                $header[] = !empty($headerdata[$i]) ? $headerdata[$i] : null;
            }
        }

        $tabledata = $this->getConfig('tabledata');

        for ($i = 0; $i < $this->getRowCount(); ++$i) {

            $row = [];

            for ($x = 0; $x < $this->getColCount(); ++$x) {
                $row[] = !empty($tabledata[$i][$x]) ? $tabledata[$i][$x] : null;
            }

            $rows[] = $row;
        }

        return [
            'rows' => $rows,
            'header' => $header,
        ];
    }

    /**
     * Cleanup widgets resources before it gets deleted
     *
     * Dependencies get auto injected.
     */
    public function unload(
        \Frootbox\Persistence\Repositories\Files $files
    ): void
    {

    }
}
