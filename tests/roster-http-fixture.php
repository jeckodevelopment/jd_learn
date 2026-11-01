<?php
declare(strict_types=1);
$root=realpath($argv[1]??'');if(!$root||getenv('JLMS_TEST_DISPOSABLE')!=='1')throw new \RuntimeException('Disposable Joomla site required');
$db=new \PDO(getenv('JLMS_TEST_DSN'),getenv('JLMS_TEST_USER'),getenv('JLMS_TEST_PASSWORD'),[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION]);
$f=json_decode(file_get_contents(dirname($root).'/roster-fixture.json'),true,16,JSON_THROW_ON_ERROR);$mode=$argv[2]??'';
if($mode==='snapshot'){
 echo json_encode($db->query('SELECT id,revision,completed_total,completed_at,last_activity_at FROM joomla_jlms_enrollments ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC),JSON_THROW_ON_ERROR);
}elseif(in_array($mode,['revoke','grant','restore'],true)){
 $q=$db->prepare('SELECT username FROM joomla_users WHERE id=?');$q->execute([$f['manager']]);if($q->fetchColumn()!=='cirosterreader')throw new \RuntimeException('Unexpected fixture user');
 if($mode==='revoke'){$q=$db->prepare('DELETE FROM joomla_user_usergroup_map WHERE user_id=? AND group_id=?');$q->execute([$f['manager'],$f['group']]);}
 else{$q=$db->prepare('INSERT IGNORE INTO joomla_user_usergroup_map(user_id,group_id) VALUES(?,?)');$q->execute([$f['manager'],$f['group']]);}
 if($mode==='restore'){$q=$db->prepare('UPDATE joomla_users SET name=? WHERE id=?');$q->execute([$f['original_name'],$f['owner']]);}
}else{throw new \RuntimeException('Unknown fixture mode');}
