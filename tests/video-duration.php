<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Jlms\Component\Jlms\Administrator\Domain\VideoDuration;
use Jlms\Component\Jlms\Administrator\Domain\Coverage;
$checks=0;
foreach([[10000,12000,true],[10000,12001,false],[10000,8000,true],[10000,7999,false],[600000,605000,true],[600000,605001,false],[28800000,28800001,false],[10000,0,false],[10000,-1,false]] as [$reference,$actual,$expected]){
 if(VideoDuration::compatible($reference,$actual)!==$expected)throw new RuntimeException('Duration tolerance boundary');$checks++;
}
foreach([null,'10000',INF,NAN,1.1,[],true] as $bad){if(VideoDuration::valid($bad))throw new RuntimeException('Untrusted duration type');$checks++;}
if(Coverage::isComplete(7199,8000,9000)||!Coverage::isComplete(7200,8000,9000))throw new RuntimeException('Observed duration threshold');$checks++;
echo json_encode(['video_duration_domain_checks'=>$checks,'status'=>'passed']).PHP_EOL;
