<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class CurriculumModel extends FormModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm(
            'com_jlms.curriculum_module',
            'curriculum_module',
            ['control' => 'jform', 'load_data' => false]
        );
    }

    public function getRulesForm()
    {
        return $this->loadForm(
            'com_jlms.curriculum_rules',
            'curriculum_rules',
            ['control' => 'jform', 'load_data' => false]
        );
    }

    public function getLessonForm(string $language = '*')
    {
        $form = $this->loadForm(
            'com_jlms.curriculum_lesson',
            'curriculum_lesson',
            ['control' => 'jform', 'load_data' => false]
        );
        // Keep lesson definitions in Joomla com_fields; never add a parallel LMS schema.
        \Joomla\Component\Fields\Administrator\Helper\FieldsHelper::prepareForm(
            'com_jlms.lesson',
            $form,
            (object) ['language' => $language]
        );
        // FieldsHelper prepares definitions for the context but does not enforce their language.
        // Remove localized definitions that cannot be used by this lesson form.
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['name', 'language']))
            ->from($db->quoteName('#__fields'))
            ->where($db->quoteName('context') . ' = ' . $db->quote('com_jlms.lesson'));
        foreach ($db->setQuery($query)->loadObjectList() as $definition) {
            if (!in_array((string) $definition->language, ['*', $language], true)) {
                $form->removeField((string) $definition->name, 'com_fields');
            }
        }
        $field = new \SimpleXMLElement(('<field name="provider_key" type="list" '
            . 'label="COM_JLMS_VIDEO_PROVIDER" showon="kind:video" />'));
        foreach (
            Factory::getApplication()->bootComponent('com_jlms')->getCurriculumService()
            ->providers() as $provider
        ) {
            $field->addChild(
                'option',
                htmlspecialchars($provider->labelKey, ENT_XML1)
            )->addAttribute(
                'value',
                $provider->key
            );
        }
        $form->setField($field);
        return $form;
    }

    public function getOutline(): array
    {
        $app = Factory::getApplication();
        try {
            return $app->bootComponent('com_jlms')->getCurriculumService()->outline(
                $app->getIdentity(),
                $app->getInput()->getInt('id', 0),
                $app->getInput()->getInt('version_id', 0),
            );
        } catch (\DomainException $error) {
            $code = $error->getMessage() === 'JERROR_ALERTNOAUTHOR' ? 403 : 404;
            throw new \RuntimeException(\Joomla\CMS\Language\Text::_($error->getMessage()), $code);
        } catch (\Throwable $error) {
            throw new \RuntimeException(\Joomla\CMS\Language\Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
