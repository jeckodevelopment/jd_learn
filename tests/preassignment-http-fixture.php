<?php
declare(strict_types=1);
$root=realpath($argv[1]??'');if(!$root||getenv('JLMS_TEST_DISPOSABLE')!=='1')throw new \RuntimeException('Disposable Joomla site required');
$db=new \PDO(getenv('JLMS_TEST_DSN'),getenv('JLMS_TEST_USER'),getenv('JLMS_TEST_PASSWORD'),[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION]);
$f=json_decode(file_get_contents(dirname($root).'/preassignment-fixture.json'),true,16,JSON_THROW_ON_ERROR);$mode=$argv[2]??'';
if($mode==='snapshot'){
 $q=$db->prepare('SELECT id,status,origin,required_total,completed_total,completed_at,created_by,course_version_id,edition_id FROM joomla_jlms_enrollments WHERE course_id=? AND user_id=? ORDER BY id');$q->execute([$f['course'],$f['http_user']]);$rows=$q->fetchAll(\PDO::FETCH_ASSOC);
 $q=$db->prepare('SELECT COUNT(*) FROM joomla_jlms_subjects WHERE user_id=?');$q->execute([$f['http_user']]);$subjects=(int)$q->fetchColumn();
 $q=$db->prepare('SELECT COUNT(*) FROM joomla_jlms_manual_enrollments AS m INNER JOIN joomla_jlms_enrollments AS e ON e.id=m.enrollment_id WHERE e.course_id=? AND e.user_id=?');$q->execute([$f['course'],$f['http_user']]);$receipts=(int)$q->fetchColumn();
 $q=$db->prepare("SELECT COUNT(*) FROM joomla_action_logs AS a INNER JOIN joomla_jlms_manual_enrollments AS m ON m.id=a.item_id INNER JOIN joomla_jlms_enrollments AS e ON e.id=m.enrollment_id WHERE a.extension='com_jlms.manualenrollment' AND e.course_id=? AND e.user_id=?");$q->execute([$f['course'],$f['http_user']]);$audit=(int)$q->fetchColumn();
 echo json_encode(['enrollments'=>$rows,'subjects'=>$subjects,'receipts'=>$receipts,'audit'=>$audit,'groups'=>$db->query('SELECT user_id,group_id FROM joomla_user_usergroup_map ORDER BY user_id,group_id')->fetchAll(\PDO::FETCH_ASSOC)],JSON_THROW_ON_ERROR);
}elseif(in_array($mode,['revoke_operator','grant_operator','revoke_learner','grant_learner','restore'],true)){
 foreach($mode==='restore'?['grant_operator','revoke_learner']:[$mode]as$action){$operator=str_contains($action,'operator');$id=$operator?$f['manager']:$f['http_user'];$group=$operator?$f['manager_group']:$f['group'];$username=$operator?'cirosterreader':'cipreassignhttp';
 $q=$db->prepare('SELECT username FROM joomla_users WHERE id=?');$q->execute([$id]);if($q->fetchColumn()!==$username)throw new \RuntimeException('Unexpected fixture user');
 $sql=str_starts_with($action,'revoke')?'DELETE FROM joomla_user_usergroup_map WHERE user_id=? AND group_id=?':'INSERT IGNORE INTO joomla_user_usergroup_map(user_id,group_id) VALUES(?,?)';$q=$db->prepare($sql);$q->execute([$id,$group]);}
}else{throw new \RuntimeException('Unknown fixture mode');}
