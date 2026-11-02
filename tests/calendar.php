<?php

declare(strict_types=1);
require dirname(__DIR__).'/component/administrator/src/Application/CourseSchedule.php';
use Jlms\Component\Jlms\Administrator\Application\CourseSchedule as Schedule;
$checks=0;
function calendarCheck(bool $ok,string $message):void { global $checks; if (!$ok) { throw new RuntimeException($message); } $checks++; }
function calendarReject(callable $call,string $message):void { try { $call(); } catch (InvalidArgumentException) { calendarCheck(true,$message); return; } throw new RuntimeException($message); }
calendarCheck(Schedule::toUtc('2030-01-15 10:00:00','Europe/Rome')==='2030-01-15 09:00:00','Winter zone conversion');
calendarCheck(Schedule::toUtc('2030-07-15 10:00:00','Europe/Rome')==='2030-07-15 08:00:00','Summer zone conversion');
calendarReject(fn()=>Schedule::toUtc('2027-03-28 02:30:00','Europe/Rome'),'DST nonexistent local time rejected');
calendarReject(fn()=>Schedule::toUtc('2027-10-31 02:30:00','Europe/Rome'),'DST ambiguous local time rejected');
calendarCheck(Schedule::display('2027-10-31 00:30:00','Europe/Rome',true)==='2027-10-31 02:30:00 +02:00','First autumn instant is explicit');
calendarCheck(Schedule::display('2027-10-31 01:30:00','Europe/Rome',true)==='2027-10-31 02:30:00 +01:00','Second autumn instant is explicit');
foreach (['2027-02-30 10:00:00','2027-01-01','2027-01-01 24:00:00','2027-01-01 12:00:00+02:00','0000-00-00 00:00:00','2027-01-01 12:00:00 trailing',['bad'],true] as $value) { calendarReject(fn()=>Schedule::toUtc($value,'UTC'),'Malformed dates not normalised'); }
calendarReject(fn()=>Schedule::toUtc('2030-01-01 00:00:00','Not/A_Zone'),'Unknown zone rejected');
$oldZone=date_default_timezone_get();date_default_timezone_set('America/Los_Angeles');
try { calendarCheck(Schedule::toUtc('2030-01-15 10:00:00','Europe/Rome')==='2030-01-15 09:00:00','Host timezone does not affect conversion'); }
finally { date_default_timezone_set($oldZone); }
$course=(object)['publish_up'=>'2030-01-15 09:00:00.123456','publish_down'=>null,'params_json'=>'{"other":"retain","calendar":{"_schema":1,"timezone":"Europe/Rome","starts_at":"2030-01-15 09:00:00","ends_at":null}}'];
$merged=Schedule::merge(['calendar_timezone'=>'Europe/Rome','calendar_publish_up'=>'2030-01-15 10:00:00'],$course,$course->params_json,'UTC');
calendarCheck($merged['publish_up']===$course->publish_up&&json_decode($merged['params_json'],true)['other']==='retain','Unchanged precision and unrelated params retained');
$merged=Schedule::merge(['calendar_timezone'=>'UTC'],$course,$course->params_json,'UTC');
calendarCheck($merged['publish_up']===$course->publish_up&&json_decode($merged['params_json'],true)['calendar']['starts_at']==='2030-01-15 09:00:00','API timezone-only update retains UTC instants');
calendarReject(fn()=>Schedule::merge(['calendar_ends_at'=>'2029-01-01 00:00:00'],$course,$course->params_json,'UTC'),'Reversed interval rejected');
calendarReject(fn()=>Schedule::merge(['calendar_timezone'=>['UTC']],$course,$course->params_json,'UTC'),'Array zone rejected');
calendarCheck(Schedule::merge([],$course,$course->params_json,'UTC')===[],'Old clients leave calendar untouched');
$start='2030-01-01 00:00:00.000000';$end='2030-01-01 00:00:00.000001';
calendarCheck(Schedule::within($start,$end,new DateTimeImmutable($start,new DateTimeZone('UTC'))),'Start included at exact microsecond');
calendarCheck(!Schedule::within($start,$end,new DateTimeImmutable($end,new DateTimeZone('UTC'))),'End excluded at exact microsecond');
calendarCheck(Schedule::fromSnapshot('{"_schema":1,"summary":"Legacy"}')===null,'Old snapshots require no backfill');
echo json_encode(['calendar_domain_checks'=>$checks,'status'=>'passed']).PHP_EOL;
