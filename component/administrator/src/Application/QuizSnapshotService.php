<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition;

/** Published links pin immutable revisions. No bank-current pointer is consulted by a learner. */
final class QuizSnapshotService
{
    public function __construct(private readonly DatabaseInterface $db) {}

    public function definition(int $lesson, int $version): array
    {
        $q=$this->db->createQuery()->select('*')->from('#__jlms_quizzes')->where('lesson_id = :lesson')->where('course_version_id = :version')
            ->bind(':lesson',$lesson,ParameterType::INTEGER)->bind(':version',$version,ParameterType::INTEGER);
        $quiz=$this->db->setQuery($q)->loadObject()?:throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);
        $selection=json_decode($quiz->selection_json,true,16,JSON_THROW_ON_ERROR);
        if(($selection['_schema']??0)!==1||!in_array($selection['mode']??'',['fixed','random'],true)||!in_array($selection['assessment']??'',['graded','practice'],true)
            ||!is_bool($selection['shuffle_questions']??null)||!is_bool($selection['shuffle_options']??null)
            ||!in_array($quiz->feedback_policy,['pass_or_exhausted','never','after_submit'],true)
            ||($selection['assessment']==='graded'&&$quiz->feedback_policy==='after_submit'))throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);
        $rules=['_schema'=>1,'threshold'=>QuestionDefinition::integer((int)$quiz->pass_basis_points,0,10000),
            'max_attempts'=>QuestionDefinition::integer((int)$quiz->max_attempts,0,100),'time_seconds'=>$quiz->time_limit_seconds===null?0:QuestionDefinition::integer((int)$quiz->time_limit_seconds,60,86400),
            'retry_seconds'=>QuestionDefinition::integer($selection['retry_delay_seconds']??null,0,604800),'feedback'=>$quiz->feedback_policy,
            'assessment'=>$selection['assessment'],'mode'=>$selection['mode'],'shuffle_questions'=>$selection['shuffle_questions'],'shuffle_options'=>$selection['shuffle_options']];
        $id=(int)$quiz->id;
        $budget=$this->db->createQuery()->select('SUM(OCTET_LENGTH(r.prompt)+OCTET_LENGTH(r.options_json)+OCTET_LENGTH(r.grading_json)+OCTET_LENGTH(r.feedback_json))')
            ->from('#__jlms_quiz_questions AS l')->innerJoin('#__jlms_question_revisions AS r ON r.id=l.question_revision_id')
            ->where('l.quiz_id = :quiz')->bind(':quiz',$id,ParameterType::INTEGER);
        if((int)$this->db->setQuery($budget)->loadResult()>4194304)throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);
        $q=$this->db->createQuery()->select('r.*, l.points, l.ordering, q.catid, q.language, q.state AS question_state')
            ->from('#__jlms_quiz_questions AS l')->innerJoin('#__jlms_question_revisions AS r ON r.id=l.question_revision_id')
            ->innerJoin('#__jlms_questions AS q ON q.id=r.question_id')->where('l.quiz_id = :quiz')->bind(':quiz',$id,ParameterType::INTEGER)->order('l.ordering, r.id');
        $pool=$this->db->setQuery($q,0,201)->loadObjectList();
        if(!$pool||count($pool)>200)throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);
        $rules['draw_count']=$selection['mode']==='fixed'?count($pool):QuestionDefinition::integer($selection['draw_count']??null,1,count($pool));
        $seen=[];$manifest=[];
        foreach($pool as$row){
            if(isset($seen[$row->question_id]))throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);$seen[$row->question_id]=true;
            $public=json_decode($row->options_json,true,16,JSON_THROW_ON_ERROR);$grading=json_decode($row->grading_json,true,16,JSON_THROW_ON_ERROR);$feedback=json_decode($row->feedback_json,true,16,JSON_THROW_ON_ERROR);
            if(($public['_schema']??0)!==1||($grading['_schema']??0)!==1||($feedback['_schema']??0)!==1)throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);
            $options=[];
            foreach($public['items']??[]as$option){$options[]=['option_id'=>$option['id']??null,'label'=>$option['label']??null,'correct'=>in_array($option['id']??null,$grading['correct']??[],true)?'1':'0'];}
            $definition=QuestionDefinition::normalise(['kind'=>$row->kind,'prompt'=>$row->prompt,'options'=>$options,'default_points'=>$grading['default_points']??null,'explanation'=>$feedback['explanation']??null],static fn(string $html):string=>$html);
            if(!hash_equals($row->content_hash,hash('sha256',QuestionDefinition::canonical($definition))))throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);
            $row->points=QuestionDefinition::integer((int)$row->points,1,10000);
            $row->public=['_schema'=>1,'kind'=>$row->kind,'prompt'=>$row->prompt,'options'=>$public['items']];
            $row->grading=['_schema'=>1,'correct'=>$grading['correct'],'explanation'=>$feedback['explanation']];
            $manifest[]=['id'=>(int)$row->id,'hash'=>$row->content_hash,'points'=>$row->points,'ordering'=>(int)$row->ordering];
        }
        $hash=hash('sha256',QuestionDefinition::canonical(['rules'=>$rules,'pool'=>$manifest]));
        return [$quiz,$rules,$pool,$hash,$selection];
    }

    public function freeze(User $actor,object $course,object $lesson): string
    {
        [$quiz,$rules,$pool,$hash,$selection]=$this->definition((int)$lesson->id,(int)$lesson->course_version_id);
        if($lesson->completion_rule!=='quiz_pass'||$lesson->source_json!==null||$lesson->provider_key!==null
            ||((int)$lesson->required&&($rules['assessment']!=='graded'||$rules['threshold']===0))
            ||!$actor->authorise('jlms.quiz.manage','com_jlms.course.'.(int)$course->id))throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);
        foreach($pool as$row){
            if(!$actor->authorise('jlms.quiz.manage','com_jlms.question.'.(int)$row->question_id))throw new \RuntimeException('COM_JLMS_LEARNING_DENIED',403);
            $category=(int)$row->catid;
            $q=$this->db->createQuery()->select('COUNT(*)')->from('#__categories AS c')->where('c.id = :category')->where('c.extension = '.$this->db->quote('com_jlms.question'))
                ->where('NOT EXISTS (SELECT 1 FROM #__categories AS p WHERE p.extension=c.extension AND p.lft<=c.lft AND p.rgt>=c.rgt AND p.published<>1)')
                ->bind(':category',$category,ParameterType::INTEGER);
            if((int)$row->question_state!==1||!in_array($row->language,['*',$course->language],true)||!(int)$this->db->setQuery($q)->loadResult())throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);
        }
        $selection['published_pool_hash']=$hash;
        $update=(object)['id'=>(int)$quiz->id,'selection_json'=>QuestionDefinition::canonical($selection),'revision'=>(int)$quiz->revision+1,
            'modified_by'=>(int)$actor->id,'modified_at'=>gmdate('Y-m-d H:i:s')];$this->db->updateObject('#__jlms_quizzes',$update,'id');
        return $hash;
    }

    public function published(int $lesson,int $version): array
    {
        $definition=$this->definition($lesson,$version);
        if(!is_string($definition[4]['published_pool_hash']??null)||!hash_equals($definition[4]['published_pool_hash'],$definition[3]))throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);
        return $definition;
    }
}
