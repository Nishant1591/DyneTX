#!/usr/bin/env python3
"""Prove Drupal's internal trailing-slash canonicalizations keep identical routes."""
import importlib.util
import json
from pathlib import Path
import urllib.parse
import urllib.request

scripts = Path(__file__).resolve().parent
directory = scripts.parent / "output" / "structured-model"
spec = importlib.util.spec_from_file_location("route_fingerprints", scripts / "verify-structured-rendering.py")
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
before = json.loads((directory / "source-before-rendered.json").read_text())["routes"]
after = json.loads((directory / "source-after-rendered.json").read_text())["routes"]
pairs = set()
for nid, original in before.items():
    current = after[nid]
    if len(original["links"]) != len(current["links"]):
        continue
    for old, new in zip(original["links"], current["links"]):
        if old == new or old["text"] != new["text"]:
            continue
        a, b = urllib.parse.urlsplit(old["href"]), urllib.parse.urlsplit(new["href"])
        if a.scheme or a.netloc or b.scheme or b.netloc or not (a.path.startswith("/") and b.path.startswith("/")):
            continue
        if a.path.rstrip("/") == b.path.rstrip("/") and (a.query, a.fragment) == (b.query, b.fragment):
            pairs.add((old["href"], new["href"]))
rows = []
for old, new in sorted(pairs):
    fingerprints = []
    statuses = []
    for route in (old, new):
        req = urllib.request.Request("http://127.0.0.1" + route, headers={"Host": "dyne-tx-workspace.ddev.site"})
        with urllib.request.urlopen(req, timeout=45) as response:
            parser = module.MainContent()
            parser.feed(response.read().decode("utf-8", errors="replace"))
            fingerprints.append(parser.fingerprint())
            statuses.append(response.status)
    same = fingerprints[0] == fingerprints[1] and statuses == [200, 200]
    rows.append({"before": old, "after": new, "http_statuses": statuses, "identical_main_text_links_images_styles": same, "fragment_preserved": urllib.parse.urlsplit(old).fragment == urllib.parse.urlsplit(new).fragment, "passed": same})
report = {"pairs": rows, "passed": all(row["passed"] for row in rows)}
(directory / "canonical-link-verification.json").write_text(json.dumps(report, indent=2) + "\n")
print(json.dumps(report, indent=2))
if not report["passed"]:
    raise SystemExit(1)
