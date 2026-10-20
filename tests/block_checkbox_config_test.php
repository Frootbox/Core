<?php

// Run with: php tests/block_checkbox_config_test.php
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');
require CORE_DIR . 'cms/admin/classes/Controller/Response.php';
require CORE_DIR . 'cms/extensions/Core/Editing/classes/Editables/AbstractController.php';
require CORE_DIR . 'cms/extensions/Core/System/classes/Editables/Block/Admin/Controller.php';

class CheckboxConfigBlock extends \Frootbox\Persistence\Content\Blocks\Block
{
    public int $saves = 0;

    public function __construct(string $viewFile, array $record = [])
    {
        parent::__construct($record + ['id' => 42, 'uid' => 'test:block']);
        $this->viewFile = $viewFile;
    }

    public function save(): \Frootbox\Db\Row
    {
        ++$this->saves;
        return $this;
    }
}

class CheckboxConfigBlocks extends \Frootbox\Persistence\Content\Repositories\Blocks
{
    public function __construct(public CheckboxConfigBlock $block) {}

    public function fetchById($rowId): \Frootbox\Db\Row
    {
        return $this->block;
    }
}

class CheckboxConfigPreview extends \Frootbox\View\Blocks\PreviewRenderer
{
    public function __construct(private \Twig\Environment $twig) {}

    public function render(\Frootbox\Persistence\Content\Blocks\Block $block): string
    {
        return $this->twig->createTemplate('{% if block.getVariables().textIndent %}indented{% else %}flush{% endif %}')->render(['block' => $block]);
    }
}

function checkCheckboxConfig(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// Match successful HTML form controls: unchecked checkboxes are omitted and
// repeated names retain their document order, as with jQuery.serialize().
function serializeCheckboxForm(DOMXPath $xpath, string $formId): string
{
    $pairs = [];
    foreach ($xpath->query('//form[@id="' . $formId . '"]//input[@name]') as $input) {
        if ($input->hasAttribute('disabled') || ($input->getAttribute('type') === 'checkbox' && !$input->hasAttribute('checked'))) {
            continue;
        }
        $value = $input->hasAttribute('value') ? $input->getAttribute('value') : ($input->getAttribute('type') === 'checkbox' ? 'on' : '');
        $pairs[] = urlencode($input->getAttribute('name')) . '=' . urlencode($value);
    }
    return implode('&', $pairs);
}

$fixture = tempnam(sys_get_temp_dir(), 'block-checkbox-');
file_put_contents($fixture, <<<'TWIG'
{# config
variables:
    textIndent:
        type: bool
        default: true
    optionalIcon:
        type: bool
        default: false
/config #}
TWIG);

try {
    $directory = CORE_DIR . 'cms/extensions/Core/System/classes/Editables/Block/Admin/resources/private/views/';
    $twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader($directory));
    $controller = new \Frootbox\Ext\Core\System\Editables\Block\Admin\Controller();
    $container = new \DI\Container();
    $preview = new CheckboxConfigPreview($twig);
    $configuration = new \Frootbox\Config\Config();
    $cases = 0;

    foreach (['AjaxModalConfig.html.twig' => 'updateBlockConfig', 'AjaxModalEdit.html.twig' => 'updateTemplateVariables'] as $view => $formId) {
        $block = new CheckboxConfigBlock($fixture);
        checkCheckboxConfig($block->getVariables() === ['textIndent' => true, 'optionalIcon' => false], 'New blocks must retain template defaults.');

        $renderForm = static function (CheckboxConfigBlock $block) use ($twig, $view, $configuration, $fixture): DOMXPath {
            $html = $twig->render($view, [
                'block' => $block,
                'template' => new \Frootbox\View\HtmlTemplate($fixture, ['variables' => $block->getConfig('template.variables')]),
                'configuration' => $configuration,
            ]);
            $document = new DOMDocument();
            $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
            return new DOMXPath($document);
        };

        foreach ([[false, false], [true, true], [false, true], [true, false], [false, false]] as [$indent, $icon]) {
            $states = ['textIndent' => $indent, 'optionalIcon' => $icon];
            $xpath = $renderForm($block);
            foreach ($states as $name => $checked) {
                $input = $xpath->query('//input[@type="checkbox" and @name="variables[' . $name . ']"]')->item(0);
                checkCheckboxConfig($input instanceof DOMElement, $view . ': missing checkbox ' . $name);
                $checked ? $input->setAttribute('checked', 'checked') : $input->removeAttribute('checked');
            }

            $_POST = ['blockId' => '42', 'variables' => serializeCheckboxForm($xpath, $formId)];
            $response = $controller->ajaxUpdateConfig($container, new \Frootbox\Http\Post(), $preview, new CheckboxConfigBlocks($block));
            checkCheckboxConfig($block->saves === 1, $view . ': config must be saved.');
            checkCheckboxConfig($response->getBodyData()['html'] === ($indent ? 'indented' : 'flush'), $view . ': preview must reflect the selected checkbox state.');

            // addConfig writes JSON to the row; reload it as a fresh DB record.
            $block = new CheckboxConfigBlock($fixture, $block->getData());
            $saved = $block->getConfig('template.variables');
            $reopened = $renderForm($block);
            foreach ($states as $name => $checked) {
                checkCheckboxConfig(array_key_exists($name, $saved), $view . ': unchecked state must be saved explicitly.');
                checkCheckboxConfig((bool) $block->getVariables()[$name] === $checked, $view . ': saved state must override the default.');
                $input = $reopened->query('//input[@type="checkbox" and @name="variables[' . $name . ']"]')->item(0);
                checkCheckboxConfig($input->hasAttribute('checked') === $checked, $view . ': reopened dialog must reflect the saved state.');
            }
            ++$cases;
        }
    }

    foreach ([false, 0, '0', true, 'on'] as $value) {
        $template = new \Frootbox\View\HtmlTemplate($fixture, ['variables' => ['textIndent' => $value]]);
        checkCheckboxConfig($template->getVariables()[0]['value'] === $value, 'Explicit stored values must not be replaced by defaults.');
    }

    echo "Block checkbox config: $cases form round-trips, defaults and stored values PASS\n";
}
finally {
    unlink($fixture);
}
