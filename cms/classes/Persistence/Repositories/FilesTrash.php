<?php 
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Repositories;

/**
 * 
 */
class FilesTrash extends \Frootbox\Db\Model
{
    protected $table = 'files_trash';
    protected $class = \Frootbox\Persistence\FileTrash::class;
}
