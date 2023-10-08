<?php
/**
 *
 */

namespace Frootbox\Persistence\Repositories\Traits;

use Frootbox\Db\Result;

trait Tags
{
    /**
     * @param string $tag
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

        // Compose sql
        $sql = 'SELECT 
            p.*
        FROM ';

        foreach ($tags as $index => $tag) {
            $sql .= ' tags t' . $index . ',';
        }

            $sql .= ' ' . $this->getTable() . ' p
        WHERE
            p.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' AND 
        ( ';

        $tagsGlue = $parameters['mode'] == 'matchAll' ? ' AND ' : ' OR ';

        foreach ($tags as $index => $tag) {

            $sql .= ($index > 0 ? $tagsGlue : '') . '
                (
                    t' . $index . '.tag = :tag' . $index . ' AND
                    t' . $index . '.itemClass = :class AND
                    t' . $index . '.itemId = p.id 
                ) ';
        }

        $sql .= '
         ) ';

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
                    else {

                    }

                    $or = ' OR ';
                }

                $sql.= ' ) ';
            }
        }

        $sql .= ' GROUP BY p.id ';

        if (!empty($parameters['order'])) {

            $sql .= ' ORDER BY ';

            foreach ($parameters['order'] as $order) {
                $sql .= $order;
            }
        }


        if (!empty($parameters['limit'])) {
            $sql .= ' LIMIT ' . $parameters['limit'];
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
