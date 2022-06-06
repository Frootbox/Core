<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Forms\Pages\Form;

class Page extends \Frootbox\AbstractStaticPage
{
    /**
     * 
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }
    
    /**
     * 
     */
    public function submit (
        \Frootbox\Mail\Mailer $mailer,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Forms $forms
    )
    {
        // Fetch form
        $form = $forms->fetchById($get->get('formId'));
        
        // Store form data
        $formData = $post->get('fields');
        
        foreach ($form->getFields() as $field) {
            
            $field->setValue($formData[$field->getId()]);
        }
        
        // Build mail source
        $view->set('form', $form);
        
        $viewFile = $this->getPath() . 'resources/private/views/mails/mail.html';       
        
        $source = $view->render($viewFile);
    }    
}
