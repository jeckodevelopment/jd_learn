<?php

declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
use Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition;
use Jlms\Component\Jlms\Administrator\Domain\QuizScorer;
$checks=0;
function qExpect(bool $ok,string $message):void{global$checks;if(!$ok)throw new \RuntimeException($message);$checks++;}
function qReject(callable $work,string $message):void{try{$work();}catch(\RuntimeException $e){qExpect(in_array($e->getCode(),[400,422],true),$message);return;}throw new \RuntimeException($message);}
$input=['kind'=>'single','prompt'=>'<p>A question?</p>','options'=>[['option_id'=>'a','label'=>'First','correct'=>'1'],['option_id'=>'b','label'=>'Second','correct'=>'0']],'default_points'=>'3','explanation'=>'<p>Explanation</p>'];
$filter=static fn(string $html):string=>strip_tags($html,'<p>');
$d=QuestionDefinition::normalise($input,$filter);
qExpect($d['grading']['correct']===['a']&&$d['grading']['default_points']===3,'correct IDs and integral points retained');
qExpect(!str_contains(QuestionDefinition::canonical($d['options']),'correct'),'public options contain no answer key');
qExpect(QuestionDefinition::canonical(['z'=>1,'a'=>['x'=>2,'b'=>3]])===QuestionDefinition::canonical(['a'=>['b'=>3,'x'=>2],'z'=>1]),'canonical object keys independent of insertion order');
qExpect(QuestionDefinition::canonical([['id'=>'a'],['id'=>'b']])!==QuestionDefinition::canonical([['id'=>'b'],['id'=>'a']]),'display list order preserved');
qReject(fn()=>QuestionDefinition::normalise(array_replace($input,['prompt'=>'<p> </p>']),$filter),'empty prompt rejected');
qReject(fn()=>QuestionDefinition::normalise(array_replace($input,['options'=>[['label'=>'First','correct'=>'0'],['label'=>'Second','correct'=>'0']]]),$filter),'no correct option rejected');
$multiple=$input;$multiple['options'][1]['correct']='1';qReject(fn()=>QuestionDefinition::normalise($multiple,$filter),'single choice cannot have two correct options');
$multiple['kind']='multiple';$m=QuestionDefinition::normalise($multiple,$filter);qExpect(QuizScorer::score(['b','a'],$m['grading']['correct'],3)===3&&QuizScorer::score(['a'],$m['grading']['correct'],3)===0,'multiple choice exact set without partial credit');
$duplicate=$input;$duplicate['options'][1]['option_id']='a';qReject(fn()=>QuestionDefinition::normalise($duplicate,$filter),'duplicate IDs rejected');
$duplicate=$input;$duplicate['options'][1]['label']='First';qReject(fn()=>QuestionDefinition::normalise($duplicate,$filter),'indistinguishable labels rejected');
$tf=$input;$tf['kind']='true_false';qExpect(QuestionDefinition::normalise($tf,$filter)['kind']==='true_false','two-option true false accepted');$tf['options'][]=['label'=>'Third','correct'=>'0'];qReject(fn()=>QuestionDefinition::normalise($tf,$filter),'true false has exactly two options');
foreach(['0','-1','1.5','1e2','10001']as$bad){qReject(fn()=>QuestionDefinition::normalise(array_replace($input,['default_points'=>$bad]),$filter),'invalid points: '.$bad);}
$bad=$input;$bad['options'][0]['option_id']='../../a';qReject(fn()=>QuestionDefinition::normalise($bad,$filter),'invalid option identifier rejected');
qReject(fn()=>QuestionDefinition::normalise(array_replace($input,['kind'=>'essay']),$filter),'unsupported kind rejected');
echo json_encode(['question_definition_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
