#!/usr/bin/env python3
"""Read-only public route fingerprints, excluding scripts and administrative UI."""
import concurrent.futures
import hashlib
from html.parser import HTMLParser
import json
from pathlib import Path
import re
import sys
import urllib.error
import urllib.request


def normalize(value):
    return re.sub(r"\s+", " ", value).strip()


class MainContent(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.depth = 0
        self.ignore = 0
        self.parts = []
        self.links = []
        self.images = []
        self.anchor = None
        self.found = False

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "main" and a.get("id") == "main-content":
            self.depth = 1
            self.found = True
            return
        if not self.depth:
            return
        void = tag in {"area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "param", "source", "track", "wbr"}
        if not void:
            self.depth += 1
        if self.ignore:
            if not void:
                self.ignore += 1
            return
        if tag in {"script", "style", "noscript"} or set(a.get("class", "").split()) & {"contextual", "contextual-links", "local-tasks", "local-actions", "messages"}:
            if not void:
                self.ignore = 1
            return
        if tag == "a":
            self.anchor = {"href": a.get("href", ""), "text_parts": []}
            self.links.append(self.anchor)
        if tag in {"img", "source", "video", "iframe"} or "url(" in a.get("style", ""):
            image = {k: a[k] for k in ("src", "srcset", "alt", "data-src", "data-srcset", "poster", "style") if k in a}
            image["tag"] = tag
            self.images.append(image)

    def handle_endtag(self, tag):
        if tag in {"area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "param", "source", "track", "wbr"}:
            return
        if not self.depth:
            return
        if self.ignore:
            self.ignore -= 1
        elif tag == "a":
            self.anchor = None
        self.depth -= 1

    def handle_data(self, data):
        if self.depth and not self.ignore:
            self.parts.append(data)
            if self.anchor is not None:
                self.anchor["text_parts"].append(data)

    def fingerprint(self):
        for anchor in self.links:
            anchor["text"] = normalize(" ".join(anchor.pop("text_parts")))
        data = {"found_main": self.found, "text": normalize(" ".join(self.parts)), "links": self.links, "images": self.images}
        data["sha256"] = hashlib.sha256(json.dumps(data, sort_keys=True, ensure_ascii=False).encode()).hexdigest()
        return data


def fetch(node):
    route = node["alias"]
    req = urllib.request.Request("http://127.0.0.1" + route, headers={"Host": "dyne-tx-workspace.ddev.site", "User-Agent": "DyneReadOnlyVerification/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=45) as response:
            parser = MainContent()
            html = response.read().decode("utf-8", errors="replace")
            # Preserve input so parser improvements never require another site
            # state or a database restore to reproduce a fingerprint.
            html_directory = Path(__file__).resolve().parent.parent / "output" / "structured-model" / f"{phase}-html"
            html_directory.mkdir(exist_ok=True)
            (html_directory / f"{node['nid']}.html").write_text(html)
            parser.feed(html)
            return str(node["nid"]), {"nid": node["nid"], "bundle": node["bundle"], "route": route, "http_status": response.status, **parser.fingerprint()}
    except Exception as exc:
        return str(node["nid"]), {"nid": node["nid"], "bundle": node["bundle"], "route": route, "error": str(exc)}


if __name__ == "__main__":
    phase = sys.argv[1] if len(sys.argv) > 1 else "before"
    directory = Path(__file__).resolve().parent.parent / "output" / "structured-model"
    entities = json.loads((directory / f"{phase}-entities.json").read_text())
    nodes = [n for n in entities["nodes"].values() if n["status"]]
    with concurrent.futures.ThreadPoolExecutor(max_workers=6) as executor:
        routes = dict(executor.map(fetch, nodes))
    snapshot = {"phase": phase, "count": len(routes), "bundles": entities["published_counts"], "routes": routes}
    (directory / f"{phase}-rendered.json").write_text(json.dumps(snapshot, ensure_ascii=False, indent=2) + "\n")
    errors = [r for r in routes.values() if "error" in r or r.get("http_status") != 200 or not r.get("found_main")]
    print(json.dumps({"phase": phase, "routes": len(routes), "errors": [{k:v for k,v in r.items() if k not in {"text", "links", "images"}} for r in errors], "bundles": entities["published_counts"]}, indent=2))
    if errors:
        sys.exit(1)
