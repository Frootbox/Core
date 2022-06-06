<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-04-16
 */

namespace Frootbox\Admin\Controller\Editor;


/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     *
     */
    protected function getEditor($htmlTag)
    {
        $targetTag = strtolower($htmlTag);

        $editors = [
            [
                'elements' => [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'h7', 'h8' ],
                'editorClass' => \Frootbox\Admin\Controller\Editor\Partials\Elements\Headline\Partial::class
            ],
            [
                'elements' => [ 'picture' ],
                'editorClass' => \Frootbox\Admin\Controller\Editor\Partials\Elements\Picture\Partial::class
            ]
        ];

        foreach ($editors as $editor) {

            if (in_array($targetTag, $editor['elements'])) {

                return $editor;
            }
        }

        return null;
    }


    /**
     *
     */
    public function ajaxModalElement ( \Frootbox\Http\Get $get, \Frootbox\Admin\Viewhelper\GeneralPurpose $gp ) {

        $editor = $this->getEditor($get->get('tag'));

        if (empty($editor)) {
            throw new \Frootbox\Exceptions\RuntimeError('HTML tag not supported.');
        }

        preg_match('#(.*?)<span class="subtitle">(.*?)</span>#is', $get->get('content'), $match);

        return self::response('json', 200, [
            'modal' => [
                'html' => $gp->injectPartial($editor['editorClass'], [
                    'meta' => $get->get('meta'),
                    'headline' => trim($match[1] ?? $get->get('content')),
                    'subtitle' => trim($match[2] ?? (string) null),
                ])
            ]
        ]);
    }


    /**
     *
     */
    public function ajaxUpdateHeadline ( \Frootbox\Db\Db $db, \Frootbox\Session $session, \Frootbox\Http\Post $post, \Frootbox\Http\Get $get, \Frootbox\Persistence\Repositories\ContentTexts $texts ) {


        if (preg_match('#^(.*?)\:([0-9]{1,})\:(.*?)$#i', $get->get('uid'), $match)) {

            if ($match[3] == 'title') {

                $modelClass = '\\' . str_replace('-', '\\', $match[1]);

                $model = $db->getModel($modelClass);

                $row = $model->fetchById($match[2]);

                $row->setTitle($post->get('title'));
                $row->save();
            }
        }


        // Check if text exists
        $result = $texts->fetch([
            'where' => ['uid' => $get->get('uid')],
            'limit' => 1
        ]);

        if ($result->getCount() > 0) {

            $text = $result->current();
        } else {

            // Insert text
            $text = $texts->insert(new \Frootbox\Persistence\ContentText([
                'userId' => $session->getUser()->getId(),
                'uid' => $get->get('uid')
            ]));
        }

        $headline = $post->get('title');

        if (!empty($post->get('subtitle'))) {
            $headline .= PHP_EOL . '<span class="subtitle">' . $post->get('subtitle') . '</span>';
        }


        $text->setText($headline);
        $text->save();


        return self::response('json');
    }





    /**
     *
     */
    public function ajaxUpdateText ( \Frootbox\Session $session, \Frootbox\Http\Post $post, \Frootbox\Persistence\Repositories\ContentTexts $texts ) {

        // Check if text exists
        $result = $texts->fetch([
            'where' => [ 'uid' => $post->get('uid') ],
            'limit' => 1
        ]);

        if ($result->getCount() > 0) {

            $text = $result->current();

            $text->setText($post->get('text'));
            $text->save();
        }
        else {

            // Insert text
            $text = $texts->insert(new \Frootbox\Persistence\ContentText([
                'userId' => $session->getUser()->getId(),
                'uid' => $post->get('uid'),
                'text' => $post->get('text')
            ]));
        }



        d("OKK");
    }


    /**
     *
     */
    public function ajaxUploadCKE ( \Frootbox\Persistence\Repositories\Folders $folders, \Frootbox\Config\Config $config, \Frootbox\Persistence\Repositories\Files $files ) {


        if (empty($config->get('statics.files.uploadDefaultFolderCKE'))) {

            // Write static config value
            $filePath = $config->get('filesRootFolder') . 'config/general.php';
            $statics = (file_exists($filePath) ? require $filePath : []);

            if (empty($config->get('statics.files.rootFolder'))) {

                // Insert root folder
                $parent = $folders->insertRoot(new \Frootbox\Persistence\Folder([
                    'title' => 'Files Index'
                ]));

                $statics['statics']['files']['rootFolder'] = (int) $folder->getId();
            }
            else {

                $parent = $folders->fetchById($config->get('statics.files.rootFolder'));
            }


            // Insert root folder
            $folder = $parent->appendChild(new \Frootbox\Persistence\Folder([
                'title' => 'Uploads vom Rich-Text-Editor'
            ]));


            $statics['statics']['files']['uploadDefaultFolderCKE'] = (int) $folder->getId();
            $source = '<?php return ' . var_export($statics, true) . ';';

            $file = new \Frootbox\Filesystem\File($filePath);
            $file->setSource($source);
            $file->write();
        }
        else {

            $folder = $folders->fetchById($config->get('statics.files.uploadDefaultFolderCKE'));
        }



        // Insert file
        $file = $files->insert(new \Frootbox\Persistence\File([
            'folderId' => $folder->getId(),
            'name' => $_FILES['upload']['name'],
            'type' => $_FILES['upload']['type'],
            'size' => $_FILES['upload']['size'],
            'sourceFile' => $_FILES['upload']['tmp_name'],
            'targetPath' => $config->get('filesRootFolder')
        ]));


        return self::response('json', 200, [
            'uploaded' => true,
            'url' => $file->getUri()
        ]);
    }


    /**
     *
     */
    public function callEditable(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \DI\Container $container,
        \Frootbox\Admin\View $view
    )
    {
        // Get editable
        $editable = $post->get('editable') ?? $get->get('editable');
        $method = $post->get('method') ?? $get->get('method');

        $class = str_replace('/', '\\', $editable . '/Admin/Controller');

        $controller =  new $class;

        if (!method_exists($controller, $method)) {
            throw new \Frootbox\Exceptions\RuntimeError('Method ' . $method . ' does not exists.');
        }

        $view->set('controller', $controller);
        $view->set('get', $get);

        $response = $container->call([ $controller, $method ]);

        if (empty($response) or !($response instanceof \Frootbox\Admin\Controller\Response)) {
            throw new \Frootbox\Exceptions\RuntimeError('UnexpectedResponseFormat');
        }

        if ($response->getType() == 'json') {
            http_response_code(200);
            header('Content-Type: application/json');
            die($response->getBody());
        }

        $viewfile = $controller->getPath() . 'resources/private/views/' . ucfirst($method) . '.html.twig';

        if (!file_exists($viewfile)) {
            $viewfile = $controller->getPath() . 'resources/private/views/' . ucfirst($method) . '.html';
        }

        if (!empty($response->getBodyData())) {
            foreach ($response->getBodyData() as $key => $value) {
                $view->set($key, $value);
            }
        }

        $source = $view->render($viewfile);

        return self::getResponse('plain', 200, $source);
    }


    /**
     *
     */
    public function index ( ) : \Frootbox\Admin\Controller\Response {

        d($_SERVER);

        return self::response();
    }


    /**
     *
     */
    public function top ( ) : \Frootbox\Admin\Controller\Response {

        return self::response();
    }
}