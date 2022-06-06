<?php
/**
 *
 */

namespace Frootbox;

abstract class AbstractMigration
{
    protected $description = null;
    protected $queries = [];

    /**
     *
     */
    protected function addSql(string $sqlQuery): void
    {
        $this->queries[] = $sqlQuery;
    }

    /**
     *
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     *
     */
    public function getVersion(): string
    {
        $da = str_split(substr(get_class($this), -6), 2);

        return (int) $da[0] . '.' . (int) $da[1] . '.' . (int) $da[2];
    }

    /**
     *
     */
    public function pushUp(
        \DI\Container $container,
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {

        if (method_exists($this, 'getPreQueries')) {
            $queries = $this->getPreQueries();

            // Execute sql
            foreach ($queries as $sql) {

                try {
                    $dbms->query($sql);
                }
                catch ( \Exception $e ) {
                    // Ignore
                }
            }
        }

        // Prepare up
        $container->call([ $this, 'up' ]);

        // Execute post sql
        foreach ($this->queries as $sql) {

            try {
                $dbms->query($sql);
            }
            catch ( \Exception $e ) {
                // Ignore
            }
        }
    }
}
