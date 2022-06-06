<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Forms\Persistence;

class Form extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Forms::class;

    protected $recipients = [ ];
    protected $groups = [ ];
    protected $fields = [ ];

    /**
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias ( ): ?\Frootbox\Persistence\Alias
    {
        return null;
    }

    /**
     * 
     */
    public function addField ( array $options ) {
        
        if (empty($options['id'])) {
            $options['id'] = substr(md5(microtime() . rand(0, 10000)), 0, 8);
        }
        
        $field = new \Frootbox\Ext\Core\Forms\Persistence\Field($options);
        
        $this->fields[] = $field;
        
        return $field;
    }


    /**
     *
     */
    public function addGroup ( $group ) {

        if (is_array($group)) {

        }

        if (!$group instanceof Group) {
            throw new \Frootbox\Exceptions\BadArgument();
        }

        $this->groups[] = $group;

        return groups;
    }
    
    
    /**
     * 
     */
    public function addRecipient ( $email, $name = null ) {
        
        $this->recipients[] = [
            'email' => $email,
            'name' => $name
        ];
        
        return $this;
    }
    
    
    /**
     * 
     */
    public function getAction ( ) {
        
        return SERVER_PATH . 'static/Ext/Core/Forms/Form/submit?formId=' . $this->getId();
    }
    
    
    /**
     * 
     */
    public function getFields ( ): array {
        
        return $this->fields;
    }

    /**
     *
     */
    public function getGroups(): \Frootbox\Db\Result
    {
        $respository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups::class);

        $result = $respository->fetch([
            'where' => [
                'parentId' => $this->getId(),
            ]
        ]);

        return $result;
    }
}