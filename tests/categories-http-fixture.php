<?php
declare(strict_types=1);
$root=realpath($argv[1]??'');
if(!$root || getenv('JLMS_TEST_DISPOSABLE')!=='1')throw new \RuntimeException('Disposable Joomla site required');
$pdo=new \PDO(getenv('JLMS_TEST_DSN'),getenv('JLMS_TEST_USER'),getenv('JLMS_TEST_PASSWORD'),[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION]);
$state=dirname($root).'/categories-http-plugin.json';
$f=json_decode(file_get_contents(dirname($root).'/categories-fixture.json'),true,16,JSON_THROW_ON_ERROR);
$mode=$argv[2]??'';
if($mode==='enable'){
    file_put_contents($state,json_encode($pdo->query("SELECT enabled,params FROM joomla_extensions WHERE type='plugin' AND folder='system' AND element='languagefilter'")->fetch(\PDO::FETCH_ASSOC),JSON_THROW_ON_ERROR));
    $pdo->exec("UPDATE joomla_extensions SET enabled=1,params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.item_associations',1,'$.detect_browser',0,'$.remove_default_prefix',0) WHERE type='plugin' AND folder='system' AND element='languagefilter'");
}elseif($mode==='disable'){
    $s=json_decode(file_get_contents($state),true,16,JSON_THROW_ON_ERROR);
    $q=$pdo->prepare("UPDATE joomla_extensions SET enabled=?,params=? WHERE type='plugin' AND folder='system' AND element='languagefilter'");$q->execute([$s['enabled'],$s['params']]);
    $q=$pdo->prepare('UPDATE joomla_categories SET access=1 WHERE id IN (?,?)');$q->execute([$f['en'],$f['it']]);
}elseif(in_array($mode,['hide_parent','restore_parent','hide_target','restore_target'],true)){
    $id=str_ends_with($mode,'parent')?$f['en']:$f['it'];$access=str_starts_with($mode,'hide')?2:1;
    $q=$pdo->prepare('UPDATE joomla_categories SET access=? WHERE id=?');$q->execute([$access,$id]);
}else{throw new \RuntimeException('Unknown fixture mode');}
