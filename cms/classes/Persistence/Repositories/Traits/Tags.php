<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 *
 * @noinspection PhpUnnecessaryLocalVariableInspection
 * @noinspection SqlNoDataSourceInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

namespace Frootbox\Persistence\Repositories\Traits;

use Frootbox\Db\Result;

trait Tags
{
    /**
     * @param string $tag
     * @param array|null $parameters
     * @return Result
     */
    public function fetchByTag(string $tag, array $parameters = null): Result
    {
        // Compose sql
        $sql = 'SELECT 
            p.*
        FROM
            tags t,
            ' . $this->getTable() . ' p
        WHERE
            t.tag = :tag AND
            t.itemClass = :class AND
            t.itemId = p.id
        ';

        if (!empty($parameters['complyVisibility'])) {
            $sql .= ' AND p.visibility >= ' . (IS_EDITOR ? 1 : 2);
        }

        if (!empty($parameters['order'])) {
            $sql .= ' ORDER BY ' . $parameters['order'];
        }

        // Fetch result
        $result = $this->fetchByQuery($sql, [ 'tag' => $tag, 'class' => $this->class ]);

        return $result;
    }

    /**
     * @param array $tags
     * @param array|null $parameters
     * @return Result
     */
    public function fetchByTags(array $tags, array $parameters = null): Result
    {
        if (empty($tags)) {
            throw new \Exception('Parameter tags empty.');
        }

        $tags = array_values($tags);

        if (empty($parameters['mode'])) {
            $parameters['mode'] = 'matchAll';
        }

        $params = [];

        $placeholders = [];

        foreach ($tags as $i => $tag) {
            $placeholders[] = ":tag{$i}";
        }

        if ($parameters['mode'] == 'matchAny') {

            $sql = 'SELECT 
                p.*
            FROM 
                ' . $this->getTable() . ' p
            JOIN 
                tags t ON t.itemId = p.id AND t.itemClass = :class
                WHERE
                p.visibility >= 1
                AND t.tag IN (' . implode(', ', $placeholders) . ') ';

            if (!empty($parameters['where'])) {

                if (empty($parameters['where']['or']) and empty($parameters['where']['and'])) {
                    $parameters['where']['and'] = $parameters['where'];
                }

                if (!empty($parameters['where']['or'])) {

                    $sql .= ' AND ( ';
                    $or = '';

                    foreach ($parameters['where']['or'] as $key => $value) {

                        if ($value instanceof \Frootbox\Db\Conditions\AbstractCondition) {
                            $sql .= $or . ' p.' . $value->toString();

                            foreach ($value->getParameters() as $parameter) {
                                $params[$parameter->getKey()] = $parameter->getValue();
                            }
                        }

                        $or = ' OR ';
                    }

                    $sql.= ' ) ';
                }
            }

            $sql .= ' GROUP BY p.id ';


        }
        else {


            $sql = 'SELECT 
                p.*
            FROM 
                ' . $this->getTable() . ' p
            JOIN 
                tags t ON t.itemId = p.id AND t.itemClass = :class
            WHERE 
                p.visibility >= 1
                AND t.tag IN (' . implode(', ', $placeholders) . ')
            GROUP BY 
                p.id
            HAVING 
                COUNT(DISTINCT t.tag) = ' . count($tags);

        }


        if (!empty($tags)) {
            $params['class'] = $this->class;
        }

        foreach ($tags as $index => $tag) {
            $params['tag' . $index] = $tag;
        }

        // Fetch result
        $result = $this->fetchByQuery($sql, $params);


        return $result;
    }
}
