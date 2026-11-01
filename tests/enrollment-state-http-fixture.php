<?php
declare(strict_types=1);
$root=realpath($argv[1]??'');if(!$root||getenv('JLMS_TEST_DISPOSABLE')!=='1')throw new \RuntimeException('Disposable Joomla site required');
$db=new \PDO(getenv('JLMS_TEST_DSN'),getenv('JLMS_TEST_USER'),getenv('JLMS_TEST_PASSWORD'),[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION]);
$f=json_decode(file_get_contents(dirname($root).'/enrollment-state-fixture.json'),true,16,JSON_THROW_ON_ERROR);$mode=$argv[2]??'';
if($mode==='snapshot'){
 $q=$db->prepare('SELECT user_id,subject_key,course_id,course_version_id,edition_id,required_total,completed_total,started_at,completed_at,last_activity_at,last_lesson_id,starts_at,ends_at FROM joomla_jlms_enrollments WHERE id=?');$q->execute([$f['http_enrollment']]);$stable=$q->fetch(\PDO::FETCH_ASSOC);
 $q=$db->prepare('SELECT status,revision FROM joomla_jlms_enrollments WHERE id=?');$q->execute([$f['http_enrollment']]);$state=$q->fetch(\PDO::FETCH_ASSOC);
 $q=$db->prepare('SELECT COUNT(*) FROM joomla_jlms_enrollment_state_changes WHERE enrollment_id=?');$q->execute([$f['http_enrollment']]);$count=(int)$q->fetchColumn();
 $q=$db->prepare("SELECT COUNT(*) FROM joomla_action_logs AS a INNER JOIN joomla_jlms_enrollment_state_changes AS c ON c.id=a.item_id WHERE a.extension='com_jlms.enrollmentstate' AND c.enrollment_id=?");$q->execute([$f['http_enrollment']]);$audit=(int)$q->fetchColumn();
 echo json_encode(['stable'=>$stable,'state'=>$state,'history'=>$count,'audit'=>$audit,'groups'=>$db->query('SELECT user_id,group_id FROM joomla_user_usergroup_map ORDER BY user_id,group_id')->fetchAll(\PDO::FETCH_ASSOC)],JSON_THROW_ON_ERROR);
}elseif(in_array($mode,['revoke','grant'],true)){
 $q=$db->prepare('SELECT username FROM joomla_users WHERE id=?');$q->execute([$f['manager']]);if($q->fetchColumn()!=='cirosterreader')throw new \RuntimeException('Unexpected fixture user');
 $sql=$mode==='revoke'?'DELETE FROM joomla_user_usergroup_map WHERE user_id=? AND group_id=?':'INSERT IGNORE INTO joomla_user_usergroup_map(user_id,group_id) VALUES(?,?)';$q=$db->prepare($sql);$q->execute([$f['manager'],$f['manager_group']]);
}else{throw new \RuntimeException('Unknown fixture mode');}
