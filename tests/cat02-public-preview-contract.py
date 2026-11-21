#!/usr/bin/env python3
"""CAT-02: public preview affordances must match the server's text-only policy."""
from pathlib import Path

root = Path(__file__).resolve().parents[1]
template = (root / "component/site/tmpl/course/default.php").read_text()
catalog = (root / "component/administrator/src/Application/CatalogService.php").read_text()
link_guard = "!empty($this->item['public_previews']) && (string) $lesson->kind === 'text' && (int) $lesson->preview === 1"
assert link_guard in template, "Public preview link must require text, an enabled course rule and lesson preview flag"
assert "l.kind = :previewKind" in catalog and "$previewKind = 'text'" in catalog, "Server must enforce the same text-only preview policy"
assert "l.preview = 1" in catalog and "if (!$rules['public_previews'])" in catalog, "Server preview opt-ins must remain enforced"
print("CAT-02 text-only public preview link policy: OK")
