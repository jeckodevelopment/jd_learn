#!/usr/bin/env python3
"""Reproducible implementation-only progress, without inferring partial scores."""
from __future__ import annotations
import csv
import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
REGISTER = ROOT / "docs/REQUIREMENTS_STATUS.csv"
CRITERIA = ROOT / "docs/FUNCTIONAL_CRITERIA_1.0.0.json"
STATES = {"implemented", "missing", "unassessed", "unverifiable"}

# Nine stable, mutually exclusive reporting areas. Every in-scope requirement
# must belong to exactly one area; unknown prefixes fail closed.
MACROAREAS = {
    "catalogue": ("Catalogo, frontend e navigazione", ("CAT", "SCR", "UI")),
    "authoring": ("Corsi, versioni e pubblicazione", ("AUT", "PRO", "LIFE", "LCM")),
    "learning": ("Iscrizioni, lezioni e progressi", ("ISC", "STU", "LES", "STA")),
    "video": ("Provider e fruizione video", ("VID", "PVD", "PBASE")),
    "materials": ("Materiali, quiz e attestati", ("MAT", "QUI", "CERT")),
    "communications": ("Email e notifiche", ("MAIL", "NOT")),
    "integration": ("Joomla, ACL e configurazione", ("JOO", "ACL", "CFG", "CORE")),
    "security": ("Privacy, sicurezza e compatibilità", ("PRI", "SIC", "SEC", "SECDEV", "ACC", "HTTP", "EDGE", "PERF", "DAT")),
    "delivery": ("Packaging, documentazione e reporting", ("REL", "DOC", "REP")),
}


def calculate(rows, criteria):
    included = {r["Requisito"]: r for r in rows if r["Perimetro 1.0.0"] != "Fase 2"}
    audited = {}
    for requirement_id, units in criteria["requirements"].items():
        if requirement_id not in included:
            raise ValueError("Unknown or excluded requirement " + requirement_id)
        if not units or len({u["id"] for u in units}) != len(units):
            raise ValueError("Missing or duplicate criteria in " + requirement_id)
        for unit in units:
            if unit["status"] not in STATES:
                raise ValueError("Invalid criterion status " + requirement_id)
            if unit["status"] == "implemented" and not unit["evidence"]:
                raise ValueError("Implemented criterion lacks evidence " + requirement_id)
            for path in unit["evidence"]:
                rel = Path(path)
                if rel.is_absolute() or ".." in rel.parts or not (ROOT / rel).is_file():
                    raise ValueError("Missing or unsafe evidence " + str(rel))
        done = sum(u["status"] == "implemented" for u in units)
        unknown = sum(u["status"] == "unassessed" for u in units)
        unverifiable = sum(u["status"] == "unverifiable" for u in units)
        audited[requirement_id] = {
            "implemented_criteria": done,
            "criteria": len(units),
            "unassessed_criteria": unknown,
            "unverifiable_criteria": unverifiable,
            "missing_criteria": len(units) - done - unknown - unverifiable,
            "provisional_fraction": round(done / len(units), 6),
            "complete_audit": unknown == 0,
        }
    prefix_to_area = {}
    for area_id, (_, prefixes) in MACROAREAS.items():
        for prefix in prefixes:
            if prefix in prefix_to_area:
                raise ValueError("Duplicate macroarea prefix: " + prefix)
            prefix_to_area[prefix] = area_id
    groups = {area_id: [] for area_id in MACROAREAS}
    for requirement_id in included:
        prefix = requirement_id.split("-", 1)[0]
        if prefix not in prefix_to_area:
            raise ValueError("Unmapped macroarea requirement: " + requirement_id)
        groups[prefix_to_area[prefix]].append(requirement_id)
    macroareas = []
    for area_id, (label, _) in MACROAREAS.items():
        ids = groups[area_id]
        if not ids:
            raise ValueError("Empty macroarea: " + area_id)
        accepted = sum(included[r]["Accettazione Joomla"].strip() != "Non eseguita" for r in ids)
        passed = sum(included[r]["Accettazione Joomla"].strip() == "Superata" for r in ids)
        macroareas.append({
            "id": area_id, "label": label, "requirements": len(ids),
            "weight_percent": round(100 * len(ids) / len(included), 2),
            "development_evidence_floor_percent": round(
                100 * sum(audited[r]["provisional_fraction"] for r in ids) / len(ids), 2),
            "manual_acceptance_coverage_percent": round(100 * accepted / len(ids), 2),
            "manual_fully_accepted_percent": round(100 * passed / len(ids), 2),
            "missing_criteria": sum(audited[r]["missing_criteria"] for r in ids),
            "unverifiable_criteria": sum(audited[r]["unverifiable_criteria"] for r in ids),
            "unassessed_criteria": sum(audited[r]["unassessed_criteria"] for r in ids),
        })
    # Evidence floor: equal weight per in-scope requirement; unassessed and
    # unverifiable criteria contribute zero and remain explicitly visible.
    development_floor = round(100 * sum(v["provisional_fraction"] for v in audited.values()) / len(included), 2)
    acceptance_states = {"Non eseguita", "Parziale", "Superata", "Fallita"}
    acceptance_counts = {state: 0 for state in acceptance_states}
    for requirement_id, row in included.items():
        state = row.get("Accettazione Joomla", "").strip()
        if state not in acceptance_states:
            raise ValueError("Invalid Joomla acceptance state for " + requirement_id + ": " + state)
        acceptance_counts[state] += 1
    acceptance_started = acceptance_counts["Parziale"] + acceptance_counts["Superata"] + acceptance_counts["Fallita"]

    all_audited = len(audited) == len(included) and all(v["complete_audit"] for v in audited.values())
    resolved = all_audited and not any(v["unverifiable_criteria"] for v in audited.values())
    # Equal weight per requirement, not per technical test. A global percentage
    # must not be published until every baseline requirement has audited criteria.
    overall = (round(100 * sum(v["provisional_fraction"] for v in audited.values()) / len(included), 2)
               if resolved else None)
    return {
        "baseline": "1.0.0", "in_scope_requirements": len(included),
        "audited_requirements": len(audited),
        "fully_reviewed_requirements": sum(v["complete_audit"] for v in audited.values()),
        "not_yet_audited_requirements": len(included) - len(audited),
        "functional_implementation_percent": overall,
        "development_evidence_floor_percent": development_floor,
        "development_evidence_floor_policy": "equal_requirement_weight; unassessed and unverifiable criteria count as zero",
        "manual_acceptance": {
            "requirements_with_recorded_result": acceptance_started,
            "requirements_not_run": acceptance_counts["Non eseguita"],
            "requirements_partial": acceptance_counts["Parziale"],
            "requirements_passed": acceptance_counts["Superata"],
            "requirements_failed": acceptance_counts["Fallita"],
            "coverage_percent": round(100 * acceptance_started / len(included), 2),
            "fully_accepted_percent": round(100 * acceptance_counts["Superata"] / len(included), 2),
        },
        "progress_status": "complete" if resolved else "not_yet_measurable",
        "criteria_by_requirement": audited,
        "macroareas": macroareas,
        "note": "Provisional criterion fractions are NOT a global score; tests and manual acceptance are separate axes.",
    }

def main():
    with REGISTER.open(encoding="utf-8-sig", newline="") as handle:
        rows = list(csv.DictReader(handle))
    criteria = json.loads(CRITERIA.read_text(encoding="utf-8"))
    if criteria.get("schema") != 1 or criteria.get("policy") != "equal_requirement_weight":
        raise ValueError("Unsupported functional progress schema")
    print(json.dumps(calculate(rows, criteria), indent=2, ensure_ascii=False))

if __name__ == "__main__":
    main()
