<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Controller;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
final class ManualenrollmentController extends BaseController
{
    public function execute($task)
    {
        if (strtolower($task) !== 'apply') { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404); }
        return parent::execute($task);
    }
    public function apply(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if (strtoupper($this->input->getMethod()) !== 'POST') { throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405); }
        if (!$this->checkToken('post', false)) { throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403); }
        try {
            if ((int) $this->input->server->get('CONTENT_LENGTH', 0, 'UINT') > 16384) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 413); }
            $raw = $this->input->post->get('jform', [], 'array');
            foreach (['course_id','user_id','language','reason','mutation_id','impact_hash','confirmation'] as $key) { if (!is_string($raw[$key] ?? null)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); } }
            $preassignment = $raw['preassignment'] ?? '0';
            if (!is_string($preassignment) || !in_array($preassignment, ['0','1'], true)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
            if ($raw['confirmation'] !== '1') { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
            $form = $this->getModel('Manualenrollment', 'Administrator', ['ignore_request' => true])->getForm();
            if (!$form->validate($form->filter($raw))) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 422); }
            $course = CurriculumService::id($raw['course_id']); $user = CurriculumService::id($raw['user_id']);
            $this->app->bootComponent('com_jlms')->getManualEnrollmentService()->apply($this->app->getIdentity(), $course, $user, $raw['language'], $raw['reason'], $raw['mutation_id'], $raw['impact_hash'], true, $preassignment === '1');
            $this->setMessage(Text::_('COM_JLMS_MANUAL_SAVED'));
            $query = http_build_query(['option'=>'com_jlms','view'=>'manualenrollment','id'=>$course,'filter'=>['user_id'=>$user,'language'=>$raw['language'],'preassignment'=>$preassignment]]);
            $this->setRedirect(Route::link('administrator', 'index.php?'.$query, false));
        } catch (\Throwable $error) {
            $key = $error->getMessage(); $code = (int) $error->getCode();
            if ($error instanceof \InvalidArgumentException) { $key = 'COM_JLMS_INVALID_INPUT'; $code = 400; }
            if (in_array($code, [400,401,403,404,409,413,422,503], true) && in_array($key, ['COM_JLMS_INVALID_INPUT','COM_JLMS_LOGIN_REQUIRED','COM_JLMS_LEARNING_DENIED','COM_JLMS_LEARNING_WINDOW','COM_JLMS_RESOURCE_NOT_FOUND','JERROR_LAYOUT_PAGE_NOT_FOUND','COM_JLMS_MANUAL_MODE_PENDING','COM_JLMS_MANUAL_CONFLICT','COM_JLMS_MANUAL_REASON','COM_JLMS_MANUAL_AUDIT_FAILED'], true)) { throw new \RuntimeException(Text::_($key), $code); }
            \Joomla\CMS\Log\Log::add('Manual enrollment failed; correlation='.bin2hex(random_bytes(8)), \Joomla\CMS\Log\Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
