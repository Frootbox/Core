<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 * @date 2019-06-15
 */

namespace Frootbox\Persistence;

use Frootbox\Db\Row;

class FileTrash extends AbstractRow
{
    protected $table = 'files_trash';
    protected $model = Repositories\FilesTrash::class;
}
