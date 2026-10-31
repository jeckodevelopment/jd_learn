<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Controller;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
use Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition;

final class QuiztimeController extends BaseController
{
    public function execute($task){if(strtolower($task)!=='save')throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND',404);return parent::execute($task);}
    public function save():void
    {
        $this->app->setHeader('Cache-Control','private, no-store',true);
        if(strtoupper($this->input->getMethod())!=='POST')throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'),405);
        if(!$this->checkToken('post',false))throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'),403);
        try{
            if((int)$this->input->server->get('CONTENT_LENGTH',0,'UINT')>16384)throw new \RuntimeException('COM_JLMS_INVALID_INPUT',413);
            $model=$this->getModel('Quiztime','Administrator',['ignore_request'=>true]);$form=$model->getForm();
            $raw=$this->input->post->get('jform',[],'array');
            // Validate scalar shape before native string/integer filters can coerce malicious arrays.
            foreach(['course_id','lesson_id','enrollment_id','revision','extra_minutes','reason','mutation_id']as$key)if(!is_string($raw[$key]??null))throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400);
            $data=$form->filter($raw);if(!$form->validate($data))throw new \RuntimeException('COM_JLMS_INVALID_INPUT',422);
            $course=CurriculumService::id($raw['course_id']);$lesson=CurriculumService::id($raw['lesson_id']);$enrollment=CurriculumService::id($raw['enrollment_id']);
            $this->app->bootComponent('com_jlms')->getQuizTimeService()->save($this->app->getIdentity(),$course,$lesson,$enrollment,
                QuestionDefinition::integer($raw['revision'],0,10000),QuestionDefinition::integer($raw['extra_minutes'],0,1440),$raw['reason'],$raw['mutation_id']);
            $this->setMessage(Text::_('COM_JLMS_QUIZ_TIME_SAVED'));
            $this->setRedirect(Route::link('administrator','index.php?option=com_jlms&view=quiztime&id='.$course.'&lesson_id='.$lesson.'&enrollment_id='.$enrollment,false));
        }catch(\Throwable $error){
            $key=$error->getMessage();$code=(int)$error->getCode();
            if($error instanceof \InvalidArgumentException){$key='COM_JLMS_INVALID_INPUT';$code=400;}
            if(in_array($code,[400,401,403,404,409,413,422,503],true)&&preg_match('/\ACOM_JLMS_[A-Z_]+\z/D',$key))throw new \RuntimeException(Text::_($key),$code);
            \Joomla\CMS\Log\Log::add('Quiz time adjustment failed; correlation='.bin2hex(random_bytes(8)),\Joomla\CMS\Log\Log::ERROR,'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'),500);
        }
    }
}
