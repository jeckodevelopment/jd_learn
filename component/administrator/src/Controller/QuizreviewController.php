<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Controller;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
final class QuizreviewController extends BaseController
{
    public function execute($task){if(strtolower($task)!=='cancel')throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND',404);return parent::execute($task);}
    public function cancel():void
    {
        $this->app->setHeader('Cache-Control','private, no-store',true);
        if(strtoupper($this->input->getMethod())!=='POST')throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'),405);
        if(!$this->checkToken('post',false))throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'),403);
        try{
            if((int)$this->input->server->get('CONTENT_LENGTH',0,'UINT')>16384)throw new \RuntimeException('COM_JLMS_INVALID_INPUT',413);
            $raw=$this->input->post->get('jform',[],'array');
            foreach(['course_id','lesson_id','enrollment_id','attempt_id','enrollment_revision','attempt_revision','impact_hash','return_attempt','reason','mutation_id','confirmation']as$key)if(!is_string($raw[$key]??null))throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400);
            if(!in_array($raw['return_attempt'],['0','1'],true)||$raw['confirmation']!=='1')throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400);
            $form=$this->getModel('Quizreview','Administrator',['ignore_request'=>true])->getForm();$data=$form->filter($raw);if(!$form->validate($data))throw new \RuntimeException('COM_JLMS_INVALID_INPUT',422);
            $course=CurriculumService::id($raw['course_id']);$lesson=CurriculumService::id($raw['lesson_id']);$enrollment=CurriculumService::id($raw['enrollment_id']);
            $attempt=CurriculumService::id($raw['attempt_id']);
            $this->app->bootComponent('com_jlms')->getQuizReviewService()->cancel($this->app->getIdentity(),$course,$lesson,$enrollment,$attempt,
                CurriculumService::id($raw['enrollment_revision']),CurriculumService::id($raw['attempt_revision']),$raw['return_attempt']==='1',$raw['reason'],$raw['mutation_id'],$raw['impact_hash'],true);
            $this->setMessage(Text::_('COM_JLMS_QUIZ_REVIEW_SAVED'));
            $this->setRedirect(Route::link('administrator','index.php?option=com_jlms&view=quizreview&id='.$course.'&lesson_id='.$lesson.'&enrollment_id='.$enrollment.'&attempt_id='.$attempt,false));
        }catch(\Throwable $error){
            $key=$error->getMessage();$code=(int)$error->getCode();if($error instanceof \InvalidArgumentException){$key='COM_JLMS_INVALID_INPUT';$code=400;}
            if(in_array($code,[400,401,403,404,409,413,422,503],true)&&preg_match('/\ACOM_JLMS_[A-Z_]+\z/D',$key))throw new \RuntimeException(Text::_($key),$code);
            \Joomla\CMS\Log\Log::add('Quiz cancellation failed; correlation='.bin2hex(random_bytes(8)),\Joomla\CMS\Log\Log::ERROR,'com_jlms');throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'),500);
        }
    }
}
