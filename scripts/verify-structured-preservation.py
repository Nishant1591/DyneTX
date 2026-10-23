#!/usr/bin/env python3
"""Verify immutable original identity/source copy and absence of test artifacts."""
import json
from pathlib import Path
import sys

directory = Path(__file__).resolve().parent.parent / "output" / "structured-model"
phase = sys.argv[1] if len(sys.argv) > 1 else "after"
before = json.loads((directory / "before-entities.json").read_text())
after = json.loads((directory / f"{phase}-entities.json").read_text())
identity_changes = []
copy_changes = []
other_preexisting_changes = []
for nid, original in before["nodes"].items():
    current = after["nodes"].get(nid)
    if current is None:
        identity_changes.append({"nid": int(nid), "missing": True})
        continue
    for key in ("nid", "uuid", "bundle", "title", "status", "created", "alias"):
        if original[key] != current[key]:
            identity_changes.append({"nid": int(nid), "key": key})
    for field, value in original["fields"].items():
        if value == current["fields"].get(field):
            continue
        if field in {"field_body_text", "field_wp_source_data"}:
            copy_changes.append({"nid": int(nid), "field": field})
        elif field != "field_components":
            other_preexisting_changes.append({"nid": int(nid), "field": field})
markers = []
for entity_type in ("nodes", "paragraphs"):
    for entity_id, row in after[entity_type].items():
        if "DYNE_STRUCTURED_TEST_" in json.dumps(row):
            markers.append({"type": entity_type, "id": int(entity_id)})
extra_nodes = sorted(set(after["nodes"]) - set(before["nodes"]), key=int)
report = {
    "original_nodes_checked": len(before["nodes"]),
    "original_node_identity_changes": identity_changes,
    "original_body_or_source_archive_changes": copy_changes,
    "other_preexisting_field_changes_excluding_component_revision_refs": other_preexisting_changes,
    "current_entities_containing_test_markers": markers,
    "extra_nodes": extra_nodes,
    "final_entity_and_revision_counts": after.get("entity_and_revision_counts", {}),
    "passed": not (identity_changes or copy_changes or other_preexisting_changes or markers or extra_nodes),
}
(directory / "preservation-verification.json").write_text(json.dumps(report, indent=2) + "\n")
print(json.dumps(report, indent=2))
if not report["passed"]:
    sys.exit(1)
