#!/usr/bin/env python3
"""Collect leads and apply explicit, source-backed functional audit decisions."""
from __future__ import annotations

import argparse
from collections import Counter
import csv
import io
import json
from pathlib import Path
import re

from functional_progress import CRITERIA, REGISTER, ROOT, calculate

REPORT = ROOT / "docs/FUNCTIONAL_AUDIT_CURRENT.md"
DASHBOARD = ROOT / "docs/RELEASE_DASHBOARD.md"
AUDIT_COLUMN = "Audit funzionale 1.0.0 per criterio"
REVIEWED = {"implemented", "missing", "unverifiable"}


def read_json(path):
    return json.loads(path.read_text(encoding="utf-8"))


def validate_evidence(reference):
    path, separator, anchor = reference.partition("#")
    candidate = ROOT / path
    if (not separator or not anchor.strip() or Path(path).is_absolute()
            or not candidate.resolve().is_relative_to(ROOT) or not candidate.is_file()):
        raise ValueError(f"Invalid evidence reference: {reference}")
    line_range = re.fullmatch(r"L([1-9][0-9]*)(?:-L([1-9][0-9]*))?", anchor)
    if line_range:
        start = int(line_range[1])
        end = int(line_range[2] or line_range[1])
        if not start <= end <= len(candidate.read_text(encoding="utf-8").splitlines()):
            raise ValueError(f"Invalid evidence line range: {reference}")
    elif anchor not in candidate.read_text(encoding="utf-8"):
        raise ValueError(f"Evidence anchor not found: {reference}")
    return path


def apply_decisions(inventory, decisions):
    seen = set()
    for decision in decisions:
        requirement, criterion = decision["requirement"], decision["criterion"]
        key = requirement, criterion
        if key in seen:
            raise ValueError(f"Duplicate decision: {key}")
        seen.add(key)
        unit = next((unit for unit in inventory["requirements"].get(requirement, [])
                     if unit["id"] == criterion), None)
        if unit is None:
            raise ValueError(f"Unknown criterion: {key}")
        if (decision["status"] not in REVIEWED or not decision["reason"].strip()
                or not decision["references"]):
            raise ValueError(f"Incomplete decision: {key}")
        paths = list(dict.fromkeys(validate_evidence(ref) for ref in decision["references"]))
        update = {"status": decision["status"], "evidence": paths,
                  "review": {"reason": decision["reason"], "references": decision["references"]}}
        if unit["status"] != "unassessed" and any(unit.get(k) != v for k, v in update.items()):
            expected_status = decision.get("supersedes_status")
            if expected_status != unit["status"]:
                raise ValueError(f"Decision conflicts with a previously reviewed criterion: {key}")
        unit.update(update)


def candidates(rows, inventory, limit):
    pending = []
    for row in rows:
        units = inventory["requirements"].get(row["Requisito"], [])
        fields = ("Fonti implementazione", "Fonti test parziali")
        leads = list(dict.fromkeys(
            path for field in fields for value in row[field].split(";")
            if (path := value.strip()) and not Path(path).is_absolute()
            and (ROOT / path).resolve().is_relative_to(ROOT) and (ROOT / path).is_file()
        ))
        for unit in units:
            if unit["status"] == "unassessed":
                pending.append({"requirement": row["Requisito"], "criterion": unit["id"],
                                "label": unit["label"], "candidate_paths": leads})
    return pending[:limit]


def generate_report(rows, inventory):
    progress = calculate(rows, inventory)
    counts = Counter(unit["status"] for units in inventory["requirements"].values() for unit in units)
    score = progress["functional_implementation_percent"]
    lines = [
        "# Audit funzionale JD Learn 1.0.0", "",
        f"Requisiti in perimetro: **{progress['in_scope_requirements']}**. "
        f"Criteri: **{sum(counts.values())}**.", "",
        f"- Implementati: **{counts['implemented']}**.",
        f"- Mancanti con lacuna accertata: **{counts['missing']}**.",
        f"- Esaminati con esito non verificabile: **{counts['unverifiable']}**.",
        f"- Da esaminare: **{counts['unassessed']}**.", "",
        f"Percentuale funzionale globale: **{f'{score:.2f}%' if score is not None else 'non determinabile'}**.",
        "Il calcolo pesa allo stesso modo ogni requisito. Gli esiti dell’audit sono distinti "
        "dall’esecuzione dei test e dall’accettazione manuale Joomla.", "",
    ]
    headings = {"missing": "Lacune confermate", "unverifiable": "Verifiche ancora necessarie",
                "implemented": "Implementazione riscontrata"}
    for status, heading in headings.items():
        lines.extend((f"## {heading}", ""))
        for requirement, units in inventory["requirements"].items():
            for unit in units:
                if unit["status"] != status or "review" not in unit:
                    continue
                review = unit["review"]
                lines.append(f"- `{requirement}/{unit['id']}` — {unit['label']}. {review['reason']}")
                for ref in review["references"]:
                    path, _, anchor = ref.partition("#")
                    target = f"../{path}#{anchor}" if re.fullmatch(r"L\d+(?:-L\d+)?", anchor) else f"../{path}"
                    lines.append(f"  - [{ref}]({target})")
        lines.append("")
    return "\n".join(lines)


def generate_dashboard(progress):
    manual = progress["manual_acceptance"]
    floor = progress["development_evidence_floor_percent"]
    c = progress["criteria_by_requirement"]
    implemented = sum(x["implemented_criteria"] for x in c.values())
    missing = sum(x["missing_criteria"] for x in c.values())
    unverifiable = sum(x["unverifiable_criteria"] for x in c.values())
    unassessed = sum(x["unassessed_criteria"] for x in c.values())
    return "\n".join([
        "# Riepilogo progetto/release JD Learn 1.0.0", "",
        "Generato da `tools/functional_audit.py --sync`; verificato da `--check`. Fonte: `docs/FUNCTIONAL_CRITERIA_1.0.0.json` e `docs/REQUIREMENTS_STATUS.csv`.", "",
        "## Sviluppo documentato (minimo confermato)", "",
        f"**{floor:.2f}%** — media a peso uguale della quota di criteri `implemented` documentati per ciascun requisito in scope ({progress['in_scope_requirements']} requisiti).",
        f"Il registro contiene {implemented} criteri implementati con evidenza, {missing} lacune accertate, {unverifiable} non verificabili e {unassessed} non ancora esaminati. I criteri non verificabili/non esaminati non entrano nel numeratore: il valore è un minimo supportato dalle evidenze, non una stima del codice che potrebbe esistere senza riscontro.", "",
        "La percentuale funzionale globale rigorosa resta **" + (f"{progress['functional_implementation_percent']:.2f}%" if progress["functional_implementation_percent"] is not None else "non determinabile") + "** finché i criteri non verificabili non vengono risolti.", "",
        "## Collaudo manuale Joomla", "",
        f"**{manual['coverage_percent']:.2f}%** — requisiti con almeno un esito registrato ({manual['requirements_with_recorded_result']}/{progress['in_scope_requirements']}). È copertura per requisito, non percentuale di casi di test superati.",
        f"- Parziale / avviato: **{manual['requirements_partial']}**",
        f"- Superato integralmente: **{manual['requirements_passed']}** ({manual['fully_accepted_percent']:.2f}% del perimetro)",
        f"- Fallito: **{manual['requirements_failed']}**",
        f"- Non eseguito: **{manual['requirements_not_run']}**",
        "",
        "La verifica automatica CI è un asse separato e non modifica il dato di accettazione Joomla. Un esito parziale conta come collaudo avviato; un esito fallito conta nella copertura perché è stato eseguito, ma resta distinto dai superati.",
        ""
    ])

def generate_matrix(rows, columns, inventory):
    if AUDIT_COLUMN not in columns:
        columns = [*columns, AUDIT_COLUMN]
    output = io.StringIO(newline="")
    writer = csv.DictWriter(output, fieldnames=columns, lineterminator="\r\n")
    writer.writeheader()
    for row in rows:
        units = inventory["requirements"].get(row["Requisito"], [])
        counts = Counter(unit["status"] for unit in units)
        summary = "; ".join(f"{status}: {counts[status]}" for status in sorted(counts))
        writer.writerow({**row, AUDIT_COLUMN: summary})
    return output.getvalue()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    actions = parser.add_mutually_exclusive_group(required=True)
    actions.add_argument("--collect", type=int, metavar="LIMIT", help="List candidates without classifying")
    actions.add_argument("--apply", type=Path, metavar="DECISIONS", help="Apply reviewed JSON decisions")
    actions.add_argument("--check", action="store_true", help="Validate the ledger and generated files")
    actions.add_argument("--sync", action="store_true", help="Regenerate the audit report and CSV from the canonical criteria ledger")
    args = parser.parse_args()
    with REGISTER.open(encoding="utf-8-sig", newline="") as stream:
        reader = csv.DictReader(stream)
        rows = list(reader)
    inventory = read_json(CRITERIA)
    if args.collect is not None:
        if args.collect < 1:
            parser.error("--collect requires a positive limit")
        print(json.dumps(candidates(rows, inventory, args.collect), indent=2, ensure_ascii=False))
        return
    if args.apply:
        apply_decisions(inventory, read_json(args.apply))
    for units in inventory["requirements"].values():
        for unit in units:
            if "review" in unit:
                review = unit["review"]
                paths = list(dict.fromkeys(validate_evidence(ref) for ref in review["references"]))
                if not paths or paths != unit["evidence"] or not review["reason"].strip():
                    raise ValueError(f"Inconsistent review: {unit['id']}")
            elif unit["status"] in {"missing", "unverifiable"}:
                raise ValueError(f"Missing review: {unit['id']}")
    report = generate_report(rows, inventory)
    matrix = generate_matrix(rows, reader.fieldnames, inventory)
    original_matrix = REGISTER.read_bytes()
    encoding = "utf-8-sig" if original_matrix.startswith(b"\xef\xbb\xbf") else "utf-8"
    if b"\r\n" not in original_matrix.splitlines(keepends=True)[0]:
        matrix = matrix.replace("\r\n", "\n")
    if args.check:
        if REPORT.read_text(encoding="utf-8") != report or matrix.encode(encoding) != original_matrix:
            raise ValueError("Functional audit report or matrix differs from the criteria ledger")
        return
    if args.apply:
        CRITERIA.write_text(json.dumps(inventory, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    REGISTER.write_bytes(matrix.encode(encoding))
    REPORT.write_text(report, encoding="utf-8")
    DASHBOARD.write_text(dashboard, encoding="utf-8")


if __name__ == "__main__":
    main()
