#!/usr/bin/env python3
"""Fingerprint reconstructed before/current node views using immutable source values."""
import importlib.util
import json
from pathlib import Path
import subprocess
import sys

scripts = Path(__file__).resolve().parent
directory = scripts.parent / "output" / "structured-model"
spec = importlib.util.spec_from_file_location("route_fingerprints", scripts / "verify-structured-rendering.py")
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
snapshot = json.loads((directory / "source-rendering-pairs.json").read_text())
entities = json.loads((directory / "after-entities.json").read_text())
for phase in ("before", "after"):
    rows = {}
    for nid, pair in snapshot.items():
        parser = module.MainContent()
        parser.feed(pair[phase])
        rows[nid] = {k:v for k,v in pair.items() if k not in {"before", "after"}}
        rows[nid].update({"http_status": 200, **parser.fingerprint()})
    output = {"phase": phase, "verification_basis": "read_only_original_entity_values_and_preserved_paragraph_revisions", "count": len(rows), "bundles": entities["published_counts"], "routes": rows}
    (directory / f"source-{phase}-rendered.json").write_text(json.dumps(output, indent=2, ensure_ascii=False) + "\n")
result = subprocess.run([sys.executable, str(scripts / "compare-structured-model.py"), "after", "--source"], check=False)
sys.exit(result.returncode)
