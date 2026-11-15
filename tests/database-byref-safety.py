#!/usr/bin/env python3
"""Prevent invalid PHP temporaries passed by reference to Joomla's DB API."""
from pathlib import Path
import re

root = Path(__file__).resolve().parents[1]
# Joomla DatabaseDriver::updateObject takes object &$object. PHP cannot pass an
# inline cast or a new temporary array/object expression by reference.
pattern = re.compile(r"->updateObject\s*\(\s*[^,\n]+,\s*(?:\(object\)\s*\[|new\s+\w+\s*\(|\(object\)\s*\$)")
failures = []
for file in (root / "component").rglob("*.php"):
    source = file.read_text()
    for match in pattern.finditer(source):
        failures.append(f"{file.relative_to(root)}:{source.count(chr(10),0,match.start()) + 1}")
# The mail queue has to be able to persist retry/failure after an SMTP
# exception. A ternary expression passed to DatabaseQuery::bind() raises
# a PHP Error *inside the catch block*, stranding the row in "sending".
mail_queue = (root / "component/administrator/src/Infrastructure/MailQueue.php").read_text()
assert "$safeError = $invalidTemplate" in mail_queue
assert "->bind(':error', $safeError)" in mail_queue
assert not re.search(r"->bind\\(\\s*['\\\"]:error['\\\"]\\s*,\\s*\\$invalidTemplate\\s*\\?", mail_queue), "by-reference ternary in mail retry error binding"
assert 'claimed_at = NULL' in mail_queue
assert 'attempts < 5' in mail_queue
assert not failures, "updateObject() requires a named mutable object, not a temporary: " + ", ".join(failures)
print("Database updateObject by-reference safety: passed")
