#!/usr/bin/env python3
"""SEC-P1: package-wide high-risk PHP sink and trust-boundary regression.

Mechanical coverage is across all shipped PHP source, not a penetration test.
Reviewed exceptions must be explicit. Endpoint-specific authorisation is tested
independently by sec-p1-controller-gates.py and disposable Joomla fixtures.
"""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
RUNTIME = ("component", "plugins", "libraries")
paths = sorted(p for part in RUNTIME for p in (ROOT / part).rglob("*.php"))
assert len(paths) >= 300, ("Unexpectedly small runtime audit surface", len(paths))
dangerous = re.compile(r"(?<![A-Za-z_])(?:eval|shell_exec|system|passthru|popen|unserialize)\s*\(", re.I)
execution = re.compile(r"(?<![A-Za-z_])proc_open\s*\(")
remote = re.compile(r"(?:new\s+\\?Joomla\\Http\\HttpFactory\s*\(|curl_init\s*\()", re.I)
tainted = re.compile(r"(?:file_get_contents|fopen|require|include)\s*\(\s*\$(?:_GET|_POST|_REQUEST|_COOKIE)", re.I)
process_paths = set()
network_paths = set()
for path in paths:
    source = path.read_text(encoding="utf-8")
    relative = path.relative_to(ROOT).as_posix()
    assert not dangerous.search(source), ("Unreviewed PHP execution/deserialisation sink", relative)
    assert not tainted.search(source), ("Tainted direct IO/include sink", relative)
    if execution.search(source):
        process_paths.add(relative)
    if remote.search(source):
        network_paths.add(relative)
assert process_paths == {"component/administrator/src/Infrastructure/VideoFilePolicy.php"}, (
    "New child-process surface requires explicit review", sorted(process_paths)
)
assert network_paths == {"plugins/jlmsvideo/cloudflarestream/src/Extension/Cloudflarestream.php"}, (
    "New remote HTTP sink requires explicit review", sorted(network_paths)
)
video_process = (ROOT / next(iter(process_paths))).read_text(encoding="utf-8")
assert re.search(r"proc_open\s*\(\s*\[\s*\$binary\s*,", video_process) and "'-protocol_whitelist', 'file'" in video_process
assert "is_link($binary)" in video_process and "'bypass_shell' => true" in video_process, (
    "Video probing must remain array-argument, local-only, non-shell execution"
)
transport = (ROOT / next(iter(network_paths))).read_text(encoding="utf-8")
assert "'follow_location' => false" in transport and "max(1, min(10," in transport
cloudflare = (ROOT / "plugins/jlmsvideo/cloudflarestream/src/Provider/Provider.php").read_text(encoding="utf-8")
assert "'https://api.cloudflare.com/client/v4/accounts/'" in cloudflare
assert "preg_match('/\\A[a-f0-9]{32}\\z/D'" in cloudflare
assert "normaliseSource(new SourceInput($uid))" in cloudflare
storage = (ROOT / "component/administrator/src/Infrastructure/ProtectedVideoStorage.php").read_text(encoding="utf-8")
assert "trim((string) $this->config()->get('video_storage_root', '')) === ''" in storage
assert "return $this->files->root();" in storage and "COM_JLMS_STORAGE_UNAVAILABLE" in storage
material = (ROOT / "component/administrator/src/Infrastructure/ProtectedMaterialStorage.php").read_text(encoding="utf-8")
assert "str_starts_with($root . DIRECTORY_SEPARATOR, $base . DIRECTORY_SEPARATOR)" in material
assert "aes-256-gcm" in material and "GUARDED_HEADER" in material
print(f"SEC-P1 source gate: scanned {len(paths)} PHP runtime files; 1 reviewed child-process sink; 1 reviewed HTTP transport; private-root boundaries enforced")
