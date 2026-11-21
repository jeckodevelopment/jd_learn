#!/usr/bin/env python3
"""DOC-04: versioned source contracts and operational procedures must not drift."""
from pathlib import Path
import re
import xml.etree.ElementTree as ET

ROOT=Path(__file__).resolve().parents[1]
page=(ROOT/'docs/TECHNICAL_REFERENCE_1.0.0.md').read_text(encoding='utf-8')
manifest=ET.parse(ROOT/'packages/pkg_jlms.xml').getroot()
version=manifest.findtext('version')
assert f'{version}-dev' in page, 'DOC-04 must identify the exact development version'
assert '1.0.0' in page and 'non' in page.lower(), 'Development documentation cannot imply a stable release'
for child in manifest.findall('./files/file'):
    name=child.text
    assert name and name in page, f'Missing package constituent in technical reference: {name}'
ddl=(ROOT/'component/administrator/sql/install.mysql.utf8mb4.sql').read_text(encoding='utf-8')
for name in re.findall(r'`(#__jlms_[a-z_]+)`',page):
    assert name in ddl, f'Unknown table in DOC-04: {name}'
for symbol,source in [
    ('VideoProviderInterface','libraries/jlms/src/Video/VideoProviderInterface.php'),
    ('VideoProviderRegistryInterface','libraries/jlms/src/Video/VideoProviderRegistryInterface.php'),
    ('VideoProviderRegistry','libraries/jlms/src/Video/VideoProviderRegistry.php'),
    ('onJlmsRegisterVideoProviders','libraries/jlms/src/Event/RegisterVideoProvidersEvent.php'),
]:
    assert symbol in page and symbol in (ROOT/source).read_text(), f'Unsupported API contract {symbol}'
for cmd in [
    'python3 tools/build.py', 'python3 tools/check.py',
    'python3 tools/functional_audit.py --check',
    'python3 tests/requirements-status.py',
    'python3 tools/functional_audit.py --sync',
]:
    assert cmd in page, f'DOC-04 missing reproducible command: {cmd}'
for term in ['Backup','rollback','MariaDB','Scheduler','SHA256SUMS.txt','CSRF','ripristin','artefatto']:
    assert term.lower() in page.lower(), f'DOC-04 missing operational coverage: {term}'
# Ensure linked local sources exist. External URLs aren't used in source mappings.
for target in re.findall(r'\]\(([^)]+)\)',page):
    if '://' in target or target.startswith('#'):
        continue
    link=target.split('#')[0]
    assert (ROOT/'docs'/link).exists(),f'DOC-04 broken local reference: {link}'
print('DOC-04 technical reference: manifests, provider contracts, DDL, links and procedures verified')
