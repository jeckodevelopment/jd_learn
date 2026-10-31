<?php

declare(strict_types=1);

require dirname(__DIR__) . '/component/administrator/src/Infrastructure/MaterialFilePolicy.php';
use Jlms\Component\Jlms\Administrator\Infrastructure\MaterialFilePolicy;
$root = dirname(__DIR__) . '/.runtime/material-policy';
if (!is_dir($root)) { mkdir($root, 0700, true); }
$checks = 0;
function materialExpect(bool $ok, string $message): void { global $checks; if (!$ok) { throw new \RuntimeException($message); } $checks++; }
function materialReject(callable $call, string $message): void {
    try { $call(); } catch (\RuntimeException $error) { materialExpect(true, $message); return; }
    throw new \RuntimeException($message);
}
foreach (['../evil.pdf', 'bad.php.pdf', 'bad.html', "bad\0.pdf", 'bad\\name.pdf', '.hidden.txt'] as $bad) {
    materialReject(fn () => MaterialFilePolicy::name($bad), 'Unsafe name rejected');
}
file_put_contents($root . '/text', "Materiale didattico: à è ì ò ù\n");
materialExpect(MaterialFilePolicy::inspect($root . '/text', 'appunti.txt')['mime_type'] === 'text/plain', 'Text accepted');
materialReject(fn () => MaterialFilePolicy::inspect($root . '/text', 'fake.pdf'), 'MIME mismatch rejected');
materialReject(fn () => MaterialFilePolicy::inspect($root . '/text', 'large.txt', 2), 'Effective size limit enforced');
file_put_contents($root . '/pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n");
materialExpect(MaterialFilePolicy::inspect($root . '/pdf', 'manuale.pdf')['mime_type'] === 'application/pdf', 'PDF signature accepted');
file_put_contents($root . '/html', '<html><script>alert(1)</script></html>');
materialReject(fn () => MaterialFilePolicy::inspect($root . '/html', 'disguised.txt'), 'Active MIME rejected');
function materialZip(string $path, array $files): void {
    $zip = new \ZipArchive();$zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
    foreach ($files as $name => $content) { $zip->addFromString($name, $content); }$zip->close();
}
materialZip($root . '/safe.zip', ['notes.txt' => 'Course notes']);
materialExpect(MaterialFilePolicy::inspect($root . '/safe.zip', 'notes.zip')['mime_type'] === 'application/zip', 'ZIP accepted without extraction');
materialZip($root . '/traversal.zip', ['../evil.txt' => 'no']);
materialReject(fn () => MaterialFilePolicy::inspect($root . '/traversal.zip', 'evil.zip'), 'Archive traversal rejected');
materialZip($root . '/bomb.zip', ['bomb.txt' => str_repeat('0', 2097152)]);
materialReject(fn () => MaterialFilePolicy::inspect($root . '/bomb.zip', 'bomb.zip'), 'Expansion ratio bounded');
$parts = ['[Content_Types].xml' => '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>',
    '_rels/.rels' => '<Relationships/>', 'word/document.xml' => '<document/>'];
materialZip($root . '/document.docx', $parts);
materialExpect(MaterialFilePolicy::inspect($root . '/document.docx', 'document.docx')['mime_type'] === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'OpenXML bounded structure accepted');
materialZip($root . '/macro.docx', $parts + ['word/vbaProject.bin' => 'macro']);
materialReject(fn () => MaterialFilePolicy::inspect($root . '/macro.docx', 'macro.docx'), 'Hidden macro rejected');
materialReject(fn () => MaterialFilePolicy::inspect($root . '/safe.zip', 'fake.docx'), 'Incomplete office structure rejected');
echo json_encode(['material_policy_checks' => $checks, 'status' => 'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
