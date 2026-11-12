<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Jlms\Component\Jlms\Administrator\Domain\TeachingRules;
use Jlms\Component\Jlms\Administrator\Domain\Coverage;
$checks=0;
function rulesExpect(bool $ok,string $message):void{global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
function rulesReject(callable $work,string $key):void{try{$work();}catch(DomainException $error){rulesExpect($error->getMessage()===$key,'Expected '.$key);return;}throw new RuntimeException('Expected rejection');}
foreach(['0.01'=>1,'0.1'=>10,'50.25'=>5025,'90'=>9000,'100.00'=>10000] as $value=>$points){rulesExpect(TeachingRules::basisPoints((string)$value)===$points,'Exact hundredths');rulesExpect(TeachingRules::basisPoints(TeachingRules::percent($points))===$points,'Exact display roundtrip');}
foreach([null,[],true,false,0,'0','-1','100.01','101','1.234','1e2','50,25',' 90','90 ','<script>',90.5] as $value){rulesReject(fn()=>TeachingRules::basisPoints($value),'COM_JLMS_TEACHING_THRESHOLD_INVALID');}
$base=['_schema'=>1,'mode'=>'learning','sequence_mode'=>'free','certificate_enabled'=>false,'completion_policy'=>'all_required'];
rulesExpect(TeachingRules::decode(json_encode($base))['video_threshold_basis_points']===9000,'Legacy missing threshold retains default90');
$consultation = array_replace($base, ['mode'=>'consultation','completion_policy'=>'none']);
rulesExpect(TeachingRules::decode(json_encode($consultation))['mode']==='consultation','Explicit consultation mode is supported without course completion');
foreach([json_encode(array_replace($base,['_schema'=>2])),'[]','null','"scalar"','{',json_encode($base+['video_threshold_basis_points'=>10001]),json_encode($base+['video_threshold_basis_points'=>'9000']),json_encode(array_replace($base,['sequence_mode'=>'linear'])),json_encode(array_replace($base,['certificate_enabled'=>true])),json_encode(array_replace($consultation,['completion_policy'=>'all_required'])),json_encode(array_replace($consultation,['sequence_mode'=>'sequential']))] as $json){rulesReject(fn()=>TeachingRules::decode($json),'COM_JLMS_PUBLICATION_UNSUPPORTED');}
rulesExpect(!Coverage::isComplete(5024,10000,5025)&&Coverage::isComplete(5025,10000,5025),'Threshold compares exact coverage without rounding up');
echo json_encode(['teaching_rules_domain_checks'=>$checks,'status'=>'passed']).PHP_EOL;
