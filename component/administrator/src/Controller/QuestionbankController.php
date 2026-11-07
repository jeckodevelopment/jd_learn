<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Log\Log;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;

final class QuestionbankController extends BaseController
{
    public function execute($task)
    {
        if (!in_array(strtolower($task), ['save','checkout','checkin'], true)) { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404); }
        return parent::execute($task);
    }
    private function perform(string $action): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') { throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405); }
        if (!$this->checkToken('post', false)) { throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403); }
        $formValidationMessage = null;
        try {
            $data = $this->input->post->get('jform', [], 'array');
            $model = $this->getModel('Questionbank', 'Administrator', ['ignore_request' => true]);
            $service = $this->app->bootComponent('com_jlms')->getQuestionBankService();
            if ($action === 'save') {
                $form = $model->getForm(); $data = $form->filter($data);
                if (!$form->validate($data)) {
                    $messages = [];
                    foreach ($form->getErrors() as $formError) {
                        if ($formError instanceof \Throwable && trim($formError->getMessage()) !== '') {
                            $messages[] = trim($formError->getMessage());
                        }
                    }
                    $formValidationMessage = Text::_('COM_JLMS_QUESTION_FORM_INVALID');
                    if ($messages) { $formValidationMessage .= ' ' . implode(' ', array_unique($messages)); }
                    throw new \RuntimeException('COM_JLMS_QUESTION_FORM_INVALID', 422);
                }
                $id = $service->save($this->app->getIdentity(), $data);
            } else { $id = CurriculumService::id($data['id'] ?? null); $service->lock($this->app->getIdentity(), $id, $action === 'checkin'); }
            $this->setMessage(Text::_('COM_JLMS_QUESTION_SAVED'));
            $this->setRedirect(Route::link('administrator', 'index.php?option=com_jlms&view=questionbank&id=' . $id, false));
        } catch (\Throwable $error) {
            $key = $error->getMessage(); $code = (int) $error->getCode();
            if ($formValidationMessage !== null && $code === 422) { throw new \RuntimeException($formValidationMessage, 422); }
            if ($error instanceof \InvalidArgumentException) { $key = 'COM_JLMS_INVALID_INPUT'; $code = 400; }
            if (in_array($code,[400,401,403,404,409,422],true) && preg_match('/\ACOM_JLMS_[A-Z_]+\z/D',$key)) { throw new \RuntimeException(Text::_($key),$code); }
            Log::add('Question mutation failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'),500);
        }
    }
    public function save(): void { $this->perform('save'); }
    public function checkout(): void { $this->perform('checkout'); }
    public function checkin(): void { $this->perform('checkin'); }
}
