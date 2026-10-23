#!/usr/bin/env python3
"""Compare read-only migration snapshots; content field changes are reported, not assumed failures."""
import collections
import hashlib
import json
import re
from pathlib import Path
import sys
from urllib.parse import unquote, urlsplit

directory = Path(__file__).resolve().parent.parent / "output" / "structured-model"
after_phase = sys.argv[1] if len(sys.argv) > 1 else "after"
before = json.loads((directory / "before-entities.json").read_text())
after = json.loads((directory / f"{after_phase}-entities.json").read_text())
prefix = "source-" if "--source" in sys.argv else ""
before_render = json.loads((directory / f"{prefix}before-rendered.json").read_text())
after_render = json.loads((directory / f"{prefix}{after_phase}-rendered.json").read_text())
identity_keys = ("nid", "uuid", "bundle", "title", "status", "created", "alias")
missing_nodes = sorted(set(before["nodes"]) - set(after["nodes"]), key=int)
extra_nodes = sorted(set(after["nodes"]) - set(before["nodes"]), key=int)
identity_changes = []
archived_copy_changes = []
field_changes = collections.defaultdict(collections.Counter)
for nid, original in before["nodes"].items():
    if nid not in after["nodes"]:
        continue
    current = after["nodes"][nid]
    for key in identity_keys:
        if original[key] != current[key]:
            identity_changes.append({"nid": int(nid), "key": key, "before": original[key], "after": current[key]})
    for name in set(original["fields"]) | set(current["fields"]):
        if original["fields"].get(name) != current["fields"].get(name):
            field_changes[current["bundle"]][name] += 1
    for name in ("field_body_text", "field_wp_source_data"):
        if original["fields"].get(name) != current["fields"].get(name):
            archived_copy_changes.append({"nid": int(nid), "field": name})

render_changes = []
expected_changes = []
expected_media_changes = []
expected_link_changes = []
render_errors = []
unchanged = 0
canonical_proof_path = directory / "canonical-link-verification.json"
canonical_proof = json.loads(canonical_proof_path.read_text()) if canonical_proof_path.exists() else {"pairs": []}
verified_link_pairs = {(row["before"], row["after"]) for row in canonical_proof["pairs"] if row.get("passed") and row.get("fragment_preserved")}

def identical_media_references(original, current):
    if len(original) != len(current):
        return None
    relocations = []
    for index, (old, new) in enumerate(zip(original, current)):
        if set(old) != set(new):
            return None
        for attribute in old:
            if old[attribute] == new[attribute]:
                continue
            if attribute not in {"src", "poster", "data-src", "srcset", "data-srcset", "style"}:
                return None
            if attribute == "style":
                pattern = re.compile(r"url\(\s*(['\"]?)(.*?)\1\s*\)")
                if pattern.sub("url(ASSET)", old[attribute]) != pattern.sub("url(ASSET)", new[attribute]):
                    return None
                old_values = [[match[1]] for match in pattern.findall(old[attribute])]
                new_values = [[match[1]] for match in pattern.findall(new[attribute])]
                if not old_values:
                    return None
            else:
                old_values = [value.strip().split(maxsplit=1) for value in old[attribute].split(",")] if attribute.endswith("srcset") else [[old[attribute]]]
                new_values = [value.strip().split(maxsplit=1) for value in new[attribute].split(",")] if attribute.endswith("srcset") else [[new[attribute]]]
            if len(old_values) != len(new_values):
                return None
            assets = []
            for old_value, new_value in zip(old_values, new_values):
                if old_value[1:] != new_value[1:]:
                    return None
                paths = [directory.parent.parent / "web" / unquote(urlsplit(value[0]).path).lstrip("/") for value in (old_value, new_value)]
                if not all(path.is_file() for path in paths):
                    return None
                digest = hashlib.sha256(paths[0].read_bytes()).hexdigest()
                if digest != hashlib.sha256(paths[1].read_bytes()).hexdigest():
                    return None
                assets.append({"before": old_value[0], "after": new_value[0], "identical_file_sha256": digest})
            relocations.append({"image_index": index, "attribute": attribute, "before": old[attribute], "after": new[attribute], "assets": assets})
    return relocations

for nid, original in before_render["routes"].items():
    current = after_render["routes"].get(nid)
    if current is None or "error" in current or current.get("http_status") != 200 or not current.get("found_main"):
        render_errors.append({"nid": int(nid), "before_route": original["route"], "after": current})
        continue
    keys = [k for k in ("text", "links", "images") if original[k] != current[k]]
    expected = False
    if "links" in keys and len(original["links"]) == len(current["links"]):
        differences = [(old, new) for old, new in zip(original["links"], current["links"]) if old != new]
        if differences and all(old["text"] == new["text"] and (old["href"], new["href"]) in verified_link_pairs for old, new in differences):
            expected_link_changes.append({"nid": int(nid), "route": current["route"], "canonicalizations": [{"before": old["href"], "after": new["href"], "identical_http_content_verified": True} for old, new in differences]})
            keys.remove("links")
            expected = True
    if "images" in keys:
        relocations = identical_media_references(original["images"], current["images"])
        if relocations:
            expected_media_changes.append({"nid": int(nid), "route": current["route"], "relocations": relocations})
            keys.remove("images")
            expected = True
    if keys == ["text"] and current["bundle"] == "job":
        arrangement = after["nodes"][nid]["fields"].get("field_workplace_arrangement", [{}])
        arrangement = arrangement[0].get("value", "") if arrangement else ""
        addition = f"Workplace arrangement: {arrangement} "
        # Accept precisely the requested, source-backed metadata row. Every
        # pre-existing character plus links and images must remain unchanged.
        if arrangement and current["text"].count(addition) == 1 and current["text"].replace(addition, "", 1) == original["text"]:
            expected_changes.append({"nid": int(nid), "route": current["route"], "added_text": addition.strip(), "existing_text_preserved": True, "links_and_images_unchanged": True})
            continue
    if keys:
        detail = {"nid": int(nid), "bundle": current["bundle"], "route": current["route"], "changed": keys}
        if "text" in keys:
            # First changed window makes lost/duplicated content reviewable without dumping full pages.
            old, new = original["text"], current["text"]
            index = next((i for i, pair in enumerate(zip(old, new)) if pair[0] != pair[1]), min(len(old), len(new)))
            detail["text_difference"] = {"before_length": len(old), "after_length": len(new), "offset": index, "before": old[max(0,index-100):index+350], "after": new[max(0,index-100):index+350]}
        if "links" in keys:
            detail["links_before"] = original["links"]
            detail["links_after"] = current["links"]
        if "images" in keys:
            detail["images_before"] = original["images"]
            detail["images_after"] = current["images"]
        render_changes.append(detail)
    elif not expected:
        unchanged += 1

report = {
    "node_counts_before": before["node_counts"], "node_counts_after": after["node_counts"],
    "missing_nodes": missing_nodes, "extra_nodes": extra_nodes, "identity_changes": identity_changes,
    "original_source_copy_changes": archived_copy_changes,
    "changed_fields": {bundle: dict(counts) for bundle, counts in field_changes.items()},
    "published_routes_checked": len(before_render["routes"]), "unchanged_rendered_routes": unchanged,
    "expected_workplace_metadata_additions": expected_changes,
    "expected_media_url_relocations": expected_media_changes,
    "expected_verified_internal_link_canonicalizations": expected_link_changes,
    "render_errors": render_errors, "render_changes": render_changes,
    "passed_identity": not (missing_nodes or extra_nodes or identity_changes),
    "passed_original_identity_and_source_copy": not (missing_nodes or identity_changes or archived_copy_changes),
    "passed_rendering": not (render_errors or render_changes),
}
(directory / f"{prefix}{after_phase}-comparison.json").write_text(json.dumps(report, indent=2, ensure_ascii=False) + "\n")
print(json.dumps({k: v for k,v in report.items() if k not in {"changed_fields", "render_changes", "render_errors", "expected_workplace_metadata_additions", "expected_media_url_relocations", "expected_verified_internal_link_canonicalizations"}}, indent=2))
print(json.dumps({"expected_workplace_metadata_additions": len(expected_changes), "expected_media_url_relocation_routes": len(expected_media_changes), "identical_asset_references": sum(len(row["relocations"]) for row in expected_media_changes), "verified_internal_link_canonicalizations": sum(len(row["canonicalizations"]) for row in expected_link_changes)}, indent=2))
print(json.dumps({"render_errors": len(render_errors), "render_changes": [{k:v for k,v in change.items() if k not in {"links_before", "links_after", "images_before", "images_after"}} for change in render_changes[:10]], "render_change_count": len(render_changes)}, indent=2))
if not report["passed_identity"] or not report["passed_rendering"]:
    sys.exit(1)
