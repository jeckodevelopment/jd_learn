<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Log\Log;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;

final class QuizController extends BaseController
{
    public function execute($task)
    {
        if (!in_array(strtolower($task), ['save','delete'], true)) { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND',404); }
        return parent::execute($task);
    }
    private function perform(bool $delete): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') { throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'),405); }
        if (!$this->checkToken('post',false)) { throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'),403); }
        try {
            $data=$this->input->post->get('jform',[],'array');
            $service=$this->app->bootComponent('com_jlms')->getQuizAuthoringService();
            if ($delete) {
                if (($data['confirmation']??'')!=='1') { throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400); }
                $course=CurriculumService::id($data['course_id']??null);$version=CurriculumService::id($data['version_id']??null);
                $service->delete($this->app->getIdentity(),$course,$version,CurriculumService::id($data['lesson_id']??null),CurriculumService::id($data['expected_version_revision']??null));
                $target='index.php?option=com_jlms&view=curriculum&id='.$course.'&version_id='.$version;
            } else {
                $form=$this->getModel('Quiz','Administrator',['ignore_request'=>true])->getForm();$data=$form->filter($data);
                if (!$form->validate($data)) { throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422); }
                $lesson=$service->save($this->app->getIdentity(),$data);
                $target='index.php?option=com_jlms&view=quiz&id='.(int)$data['course_id'].'&version_id='.(int)$data['version_id'].'&lesson_id='.$lesson;
            }
            $this->setMessage(Text::_('COM_JLMS_QUIZ_SAVED'));$this->setRedirect(Route::link('administrator',$target,false));
        } catch (\Throwable $error) {
            $key=$error->getMessage();$code=(int)$error->getCode();
            if ($error instanceof \InvalidArgumentException) { $key='COM_JLMS_INVALID_INPUT';$code=400; }
            if (in_array($code,[400,401,403,404,409,422],true)&&preg_match('/\ACOM_JLMS_[A-Z_]+\z/D',$key)) { throw new \RuntimeException(Text::_($key),$code); }
            Log::add('Quiz mutation failed; correlation='.bin2hex(random_bytes(8)),Log::ERROR,'com_jlms');throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'),500);
        }
    }
    public function save(): void { $this->perform(false); }
    public function delete(): void { $this->perform(true); }
}
