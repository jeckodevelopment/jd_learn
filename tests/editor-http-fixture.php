<?php

declare(strict_types=1);
if (getenv('JLMS_TEST_DISPOSABLE')!=='1') { throw new RuntimeException('Disposable site required'); }
$db=new PDO(getenv('JLMS_TEST_DSN'),getenv('JLMS_TEST_USER'),getenv('JLMS_TEST_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$q=$db->query("SELECT id,catid,revision FROM joomla_jlms_courses WHERE alias='editor-http-delegated'");
echo json_encode($q->fetch(PDO::FETCH_ASSOC),JSON_THROW_ON_ERROR).PHP_EOL;
