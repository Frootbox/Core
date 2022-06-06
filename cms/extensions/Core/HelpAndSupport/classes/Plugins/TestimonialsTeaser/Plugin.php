<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\TestimonialsTeaser;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function getLimit(): int
    {
        return !empty($this->getConfig('limit')) ? $this->getConfig('limit') : 10;
    }

    /**
     *
     */
    public function getTestimonials(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories\Testimonials $testimonialsRepository,
        array $parameters = null,
    ): \Frootbox\Db\Result
    {

        $limit = !empty($parameters['limit']) ? $parameters['limit'] : $this->getLimit();
        $tags = !empty($parameters['tags']) ? $parameters['tags'] : $this->getConfig('tags');

        // Build sql
        $sql = 'SELECT
            SQL_CALC_FOUND_ROWS
            COUNT(a.id) as counter,
            a.*
        FROM
            ';

        $params = [ ];

        if (!empty($tags)) {
            $sql .= 'tags t,';
        }

        $sql .= '
            assets a        
        WHERE
            a.visibility >= ' . (\IS_EDITOR ? 1 : 2) . ' AND         
            ' . (!empty($this->getConfig('source')) ? ' a.pluginId = ' . $this->getConfig('source')[0] . ' AND ' : '') . '
            a.className = "' . addslashes(\Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Testimonial::class) . '"
            ';

        if (!empty($parameters['ignore'])) {
            $sql .= ' AND a.id NOT IN ("' . implode('", "', $parameters['ignore']) . '") ';
        }

        if (!empty($maxDays = $this->getConfig('maxAgeDays'))) {

            $date = new \DateTime();
            $date->modify('-' . (int) $maxDays . ' day');

            $sql .= ' AND a.dateStart >= "' .  $date->format('Y-m-d') . '" ';

        }

        if (!empty($tags)) {
            $sql .= '
            AND
                (
                ';

            $loop = 0;

            $and = (string) null;

            foreach ($tags as $tag) {

                $sql .= $and . '(
                    t.itemId = a.id AND
                    t.itemClass = a.className AND
                    t.tag = :tag_' . ++$loop . ' 
                )';

                $params[':tag_' . $loop] = $tag;

                $and = ' OR ';
            }

            $sql .= '
                )
                GROUP BY
			        a.id
                HAVING
			        counter = ' . count($tags);
        }
        else {
            $sql .= ' GROUP BY a.id ';
        }

        if (!empty($parameters['order'])) {
            $sql .= ' ORDER BY ' . $parameters['order'] . ' DESC ';
        }

        $sql .= ' LIMIT ' . $limit;

        // Fetch testimonials
        $result = $testimonialsRepository->fetchByQuery($sql, $params);

        return $result;
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        return new \Frootbox\View\Response([

        ]);
    }
}
