<?php

// Run: php tests/headline_inline_text_test.php
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');
require CORE_DIR . 'cms/extensions/Core/Editing/classes/Editables/AbstractController.php';
require CORE_DIR . 'cms/admin/classes/Controller/Response.php';
require CORE_DIR . 'cms/extensions/Core/System/classes/Editables/EditableInterface.php';
require CORE_DIR . 'cms/extensions/Core/System/classes/Editables/Headline/Editable.php';
require CORE_DIR . 'cms/extensions/Core/System/classes/Editables/Headline/Admin/Controller.php';
define('MULTI_LANGUAGE', false);

use Frootbox\View\InlineText;

function checkInline(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

$cases = [
    ['Was unsere Arbeit <strong>auszeichnet</strong>', 'Was unsere Arbeit <strong>auszeichnet</strong>'],
    ['<p><b class="x" onclick="evil()">Fett <i>Kursiv</i></b></p><p>Weiter</p>', "<strong>Fett <em>Kursiv</em></strong>\nWeiter"],
    ['<a href="javascript:evil()">Link</a> <u>ohne Linie</u><img src=x onerror=evil()><script>evil()</script><svg><text>weg</text></svg>', 'Link ohne Linie'],
    ['Büro &amp; Spaß &lt;3 😀', 'Büro &amp; Spaß &lt;3 😀'],
    ["Alt\r\nNeu<br />\nWeiter", "Alt\nNeu\nWeiter"],
    ['<div>eins</div><div>zwei</div>', "eins\nzwei"],
    ['<strong>offen <em>gemischt</strong> Ende', '<strong>offen <em>gemischt</em></strong> Ende'],
    ['<p><br></p>', ''],
    ['', ''],
];
foreach ($cases as [$input, $expected]) {
    $actual = InlineText::sanitize($input);
    checkInline($actual === $expected, "Normalization failed: " . var_export([$input, $actual, $expected], true));
    checkInline(InlineText::sanitize($actual) === $actual, 'Normalization must be stable across repeated saves');
}
checkInline(InlineText::plainText('<strong>Fett</strong> &amp; <em>kursiv</em>') === 'Fett & kursiv', 'Entity title must be plain text');
echo "Inline normalization: emphasis, attributes, blocks, pasted markup, Unicode, line endings and empty values PASS\n";

class InlineTestText extends \Frootbox\Persistence\Content\Text
{
    public function save(): \Frootbox\Db\Row { return $this; }
}
class InlineTestTexts extends \Frootbox\Persistence\Content\Repositories\Texts
{
    public function __construct(public InlineTestText $text) {}
    public function fetchByUid($uid, array $options = null) { return $this->text; }
    public function fetchOne(array $params = null, array $options = null): ?\Frootbox\Db\Row { return $this->text; }
}

$text = new InlineTestText();
$texts = new InlineTestTexts($text);
$db = (new ReflectionClass(\Frootbox\Db\Db::class))->newInstanceWithoutConstructor();
$config = new \Frootbox\Config\Config();
$controller = new \Frootbox\Ext\Core\System\Editables\Headline\Admin\Controller();
$_GET = ['uid' => 'test:headline'];
$_GET['headline'] = 'Vorlage <strong>fett</strong> und <i>kursiv</i>';
$initial = $controller->ajaxModalEdit(new \Frootbox\Http\Get(), $texts)->getBodyData();
checkInline($initial['fields']['headline'] === 'Vorlage <strong>fett</strong> und <em>kursiv</em>', 'Initial template markup must survive the first edit');
$_POST = [
    'headline' => '<p>Was uns <b>auszeichnet</b> &amp; <i>bewegt</i></p>',
    'supertitle' => '<div><i>Für Familien</i></div><div>Heute</div>',
    'subtitle' => '<strong onclick="evil()">Gemeinsam</strong> <a href="/">weiter</a>',
    'headlineLevel' => 'H3',
];
$response = $controller->ajaxUpdate(new \Frootbox\Http\Post(), new \Frootbox\Http\Get(), $db, $config, $texts)->getBodyData();
foreach (['headline', 'subtitle', 'supertitle'] as $field) {
    checkInline($text->getConfig($field) === InlineText::sanitize($_POST[$field]), "$field must be normalized before storage");
}
checkInline($response['headline']['text'] === 'Was uns <strong>auszeichnet</strong> &amp; <em>bewegt</em>', 'AJAX returns normalized inline HTML');
checkInline($response['headline']['supertitle'] === "<em>Für Familien</em><br />\nHeute", 'AJAX preserves supertitle line breaks');

$editable = new \Frootbox\Ext\Core\System\Editables\Headline\Editable();
$rendered = $editable->parse('<main><h2 data-editable data-uid="test:headline">Vorlage</h2></main>', $config, $texts);
$crawler = \Frootbox\View\HtmlPageCrawler::create($rendered);
checkInline($crawler->filter('h3 strong')->text() === 'auszeichnet', 'Saved heading renders bold');
checkInline($crawler->filter('h3 em')->text() === 'bewegt', 'Saved heading renders italic');
checkInline($crawler->filter('.supertitle em')->text() === 'Für Familien', 'Saved supertitle renders italic');
checkInline($crawler->filter('.supertitle br')->count() === 1, 'Supertitle line break renders once');
checkInline($crawler->filter('.subtitle strong')->text() === 'Gemeinsam', 'Saved subtitle renders bold');
checkInline($crawler->filter('h3 p, h3 div, h3 a')->count() === 0, 'Heading contains no forbidden wrapper or link');

$_GET['headline'] = 'Stale plain text';
$modal = $controller->ajaxModalEdit(new \Frootbox\Http\Get(), $texts)->getBodyData();
checkInline($modal['fields']['headline'] === $text->getConfig('headline'), 'Reopening uses saved rich text');
$_POST = ['headline' => '', 'subtitle' => ''];
$controller->ajaxUpdate(new \Frootbox\Http\Post(), new \Frootbox\Http\Get(), $db, $config, $texts);
checkInline($text->getConfig('supertitle') === "<em>Für Familien</em>\nHeute", 'A hidden supertitle must not be cleared');
$crawler = \Frootbox\View\HtmlPageCrawler::create($editable->parse('<main><h2 data-editable data-uid="test:headline">Vorlage</h2></main>', $config, $texts));
checkInline($crawler->filter('h2')->text() === '', 'An explicitly cleared heading stays empty');
echo "Headline integration: three fields, storage, AJAX response, frontend rendering, reopening and clearing PASS\n";

class InlineTitleModel
{
    public static InlineTestText $row;
    public function __construct(\Frootbox\Db\Db $db) {}
    public function fetchById($id): \Frootbox\Db\Row { return self::$row; }
}
InlineTitleModel::$row = new InlineTestText(['title' => 'Alt', 'subtitle' => 'Alt']);
$_GET['uid'] = 'InlineTitleModel:1:title';
$_POST = ['headline' => '<strong>Fett</strong> &amp; <i>kursiv</i>', 'subtitle' => '<em>Untertitel</em>'];
$controller->ajaxUpdate(new \Frootbox\Http\Post(), new \Frootbox\Http\Get(), $db, $config, $texts);
checkInline(InlineTitleModel::$row->getTitle() === 'Fett & kursiv', 'Model title remains usable in navigation and metadata');
checkInline(InlineTitleModel::$row->getSubtitle() === 'Untertitel', 'Model subtitle remains plain text');
checkInline($text->getConfig('headline') === '<strong>Fett</strong> &amp; <em>kursiv</em>', 'Editable retains markup while model title stays plain');
echo "Model integration: plain entity titles alongside formatted editable content PASS\n";
