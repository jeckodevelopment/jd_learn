#!/usr/bin/env python3
"""Pin the ASVS reference without asserting certification or test completion."""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PIN = 'OWASP ASVS 5.0.0'
CERTIFICATION = 'non una certificazione'
GATE = ROOT / 'docs/SECURITY_PRIVACY_RELEASE_GATE_1.0.0.md'
text = GATE.read_text(encoding='utf-8')
assert f'## {PIN} — riferimento di verifica fissato' in text
assert 'v5.0.0-1.2.5' in text
assert 'https://asvs.owasp.org/downloads/' in text
assert CERTIFICATION in text
assert 'SEC-P1/SEC-P5' in text
assert 'release stabile del 30 maggio 2025' in text
print('SECDEV-07: pinned ASVS v5.0.0 reference, traceability format and no-certification disclaimer OK')
