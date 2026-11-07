<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Factory;

final class CurriculumModel extends FormModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_jlms.curriculum_module', 'curriculum_module', ['control' => 'jform', 'load_data' => false]);
    }

    public function getRulesForm()
    {
        return $this->loadForm('com_jlms.curriculum_rules', 'curriculum_rules', ['control'=>'jform', 'load_data'=>false]);
    }

    public function getLessonForm()
    {
        $form = $this->loadForm('com_jlms.curriculum_lesson', 'curriculum_lesson', ['control' => 'jform', 'load_data' => false]);
        $field = new \SimpleXMLElement('<field name="provider_key" type="list" label="COM_JLMS_VIDEO_PROVIDER" showon="kind:video" />');
        foreach (Factory::getApplication()->bootComponent('com_jlms')->getCurriculumService()->providers() as $provider) {
            $field->addChild('option', htmlspecialchars($provider->labelKey, ENT_XML1))->addAttribute('value', $provider->key);
        }
        $form->setField($field);
        return $form;
    }

    public function getOutline(): array
    {
        $app = Factory::getApplication();
        try {
            return $app->bootComponent('com_jlms')->getCurriculumService()->outline(
            $app->getIdentity(), $app->getInput()->getInt('id', 0), $app->getInput()->getInt('version_id', 0),
            );
        } catch (\DomainException $error) {
            $code = $error->getMessage() === 'JERROR_ALERTNOAUTHOR' ? 403 : 404;
            throw new \RuntimeException(\Joomla\CMS\Language\Text::_($error->getMessage()), $code);
        } catch (\Throwable $error) {
            throw new \RuntimeException(\Joomla\CMS\Language\Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
