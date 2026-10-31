<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

final class QuizController extends BaseController
{
    public function execute($task)
    {
        if(!in_array(strtolower($task),['start','save','submit','state'],true))throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND',404);
        return parent::execute($task);
    }
    private function respond(string $operation):void
    {
        $this->app->setHeader('Cache-Control','private, no-store',true);
        $this->app->setHeader('Content-Type','application/json; charset=utf-8',true);
        $request=bin2hex(random_bytes(8));
        try{
            $read=$operation==='state';
            if(strtoupper($this->input->getMethod())!==($read?'GET':'POST'))throw new \RuntimeException('COM_JLMS_METHOD_NOT_ALLOWED',405);
            if((int)$this->input->server->get('CONTENT_LENGTH',0,'UINT')>1048576)throw new \RuntimeException('COM_JLMS_QUIZ_ANSWERS_INVALID',413);
            if(!$read&&!$this->checkToken('post',false))throw new \RuntimeException('JINVALID_TOKEN',403);
            $input=$read?$this->input->get:$this->input->post;
            $integer=static function(string $key)use($input):int{
                try{return \Jlms\Component\Jlms\Administrator\Application\CurriculumService::id($input->get($key,null,'RAW'));}
                catch(\InvalidArgumentException $error){throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400);}
            };
            $service=$this->app->bootComponent('com_jlms')->getQuizAttemptService();
            $args=[$this->app->getIdentity(),$this->app->getLanguage()->getTag(),$integer('enrollment_id'),$integer('lesson_id')];
            $answers=$input->get('answers_json','{}','RAW');if(!is_string($answers))throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400);
            $data=match($operation){
                'start'=>$service->start(...[...$args,$input->getString('mutation_id','')]),
                'state'=>$service->state(...[...$args,$input->getInt('attempt_id',0)]),
                default=>$service->mutate(...[...$args,$integer('attempt_id'),$integer('revision'),$input->getString('mutation_id',''),$answers,$operation==='submit']),
            };
            echo new JsonResponse($data,null,false,true);
        }catch(\Throwable $error){
            $code=(int)$error->getCode();$key=$error->getMessage();
            if(!in_array($code,[400,401,403,404,405,409,413,422,429],true)||(!preg_match('/\ACOM_JLMS_[A-Z_]+\z/D',$key)&&$key!=='JINVALID_TOKEN')){
                Log::add('Quiz operation failed; correlation='.$request,Log::ERROR,'com_jlms');$key='COM_JLMS_SAVE_FAILED';$code=500;
            }
            $this->app->setHeader('status',(string)$code,true);http_response_code($code);
            echo new JsonResponse(['code'=>$key,'request_id'=>$request],Text::_($key),true,true);
        }
        $this->app->close();
    }
    public function start():void{$this->respond('start');}
    public function save():void{$this->respond('save');}
    public function submit():void{$this->respond('submit');}
    public function state():void{$this->respond('state');}
}
