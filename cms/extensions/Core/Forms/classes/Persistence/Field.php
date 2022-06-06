<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Forms\Persistence;

class Field extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Fields::class;

    protected $value;

    protected $fieldTypes = [
        'Text',
        'Textarea',
        'Email',
        'List',
        'Date',
        'CheckboxSingle',
        'CheckboxMultiple',
        'CheckboxMultipleInline',
        'Radio',
        'StreetAndNumber',
        'ZipcodeAndCity',
        'File',
        'Channel'
    ];

    /**
     *
     */
    public function getChannelRecipients(): array
    {
        if (empty($this->getConfig('options'))) {
            return [];
        }

        $da = explode("\n", $this->getConfig('options'));

        $list = [];

        foreach ($da as $line) {

            $dax = explode(':', $line);

            $list[] = [
                'key' => md5($line),
                'address' => $dax[0],
                'name' => ($dax[1] ?? $dax[0])
            ];
        }

        return $list;
    }

    /**
     *
     */
    public function getHelpText(): ?string
    {
        $text = $this->getConfig('helpText');

        $linkText = $this->getConfig('helpTextLink');

        if (!empty($linkText)) {

            if (preg_match('#\[(.*?)\]#', $text, $match)) {
                $text = str_replace($match[0], '<a href="' . $linkText . '">' . $match[1] . '</a>', $text);
            }
            else {
                $text = '<a href="' . $linkText . '">' . $text . '</a>';
            }
        }

        return $text;
    }

    /**
     *
     */
    public function getLabel()
    {
        return $this->getTitle();
    }

    /**
     *
     */
    public function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }

    /**
     *
     */
    public function getOptions(): array
    {
        if (empty($options = $this->getConfig('options'))) {
            return [ ];
        }

        $options = explode("\n", $options);

        $list = [ ];

        foreach ($options as $key => $label) {
            $list[] = [
                'label' => $label,
                'key' => $key
            ];
        }

        return $list;
    }

    /**
     *
     */
    public function getType(): string
    {
        return $this->config['type'] ?? 'Text';
    }


    /**
     * Get available field types
     */
    public function getTypes ( ) {

        return $this->fieldTypes;
    }


    /**
     *
     */
    public function getValue ( )
    {
        return $this->value;
    }


    /**
     * 
     */
    public function getValueDisplay(): ?string
    {
        switch ($this->getType()) {

            default:
                return $this->value;

            case 'Date':

                if (empty($this->getValue())) {
                    return (string) null;
                }

                $date = new \Frootbox\Dates\Date($this->getValue());

                return $date->format('%d.%m.%Y');

            case 'List':
                $index = $this->getValue();

                return (!empty($this->getOptions()[$index]['label']) ? $this->getOptions()[$index]['label'] : (string) null);

            case 'CheckboxSingle':
                return $this->getValue() ? 'Ja' : 'Nein';

            case 'CheckboxMultiple':
                return is_array($this->getValue()) ? trim(implode("\n", $this->getValue())) : (string) null;

            case 'StreetAndNumber':
                return $this->value['street'] . ' ' . $this->value['number'];

            case 'CheckboxMultipleInline':

                if (empty($this->value)) {
                    return (string) null;
                }

                $options = $this->getOptions();

                $valueDisplay = (string) null;

                foreach ($this->value as $index => $state) {

                    $valueDisplay .= $options[$index]['label'] . PHP_EOL;
                }

                return trim($valueDisplay);

            case 'ZipcodeAndCity':
                return $this->value['zipcode'] . ' ' . $this->value['city'];
        }
    }

    /**
     *
     */
    public function isEmpty(): bool
    {
        switch ($this->getType()) {
            case 'StreetAndNumber':
                return empty($this->value['street']) OR empty($this->value['number']);

            case 'ZipcodeAndCity':
                return empty($this->value['zipcode']) OR empty($this->value['city']);

            default:
                return (strlen(trim($this->value)) == 0);
        }
    }

    /**
     *
     */
    public function isRequired(): bool
    {
        return !empty($this->getConfig('isRequired'));
    }
    
    /**
     * 
     */
    public function setValue ( $value ): Field
    {
        $this->value = $value;
        
        return $this;
    }   
}
