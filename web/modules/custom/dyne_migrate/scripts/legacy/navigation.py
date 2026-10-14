"""Canonical public links retained inside the Drupal site after migration."""
import json
from pathlib import Path
import re
from urllib.parse import urlsplit, urlunsplit

LEGACY_HOSTS = {'dyne-tx.com', 'www.dyne-tx.com', 'dynetxdev.wpengine.com'}
MEDIA_EXTENSIONS = re.compile(r'\.(?:pdf|docx?|xlsx?|pptx?|zip|jpe?g|png|gif|webp|svg|mp4|mp3|webm|wav)$', re.I)
_redirect_file = Path(__file__).resolve().parents[2] / 'data/legacy-verified-redirects.json'
VERIFIED_REDIRECTS = json.loads(_redirect_file.read_text())['urls'] if _redirect_file.exists() else {}


def canonical_url(value, aliases=()):
    """Keep fragments/query strings; never rewrite investor/provider domains."""
    if value in VERIFIED_REDIRECTS:
        return VERIFIED_REDIRECTS[value]
    try:
        parts = urlsplit(value)
    except ValueError:
        return value
    if parts.hostname and parts.hostname.lower() in LEGACY_HOSTS and parts.scheme in {'', 'http', 'https'}:
        return urlunsplit(('', '', parts.path or '/', parts.query, parts.fragment))
    if not parts.scheme and not parts.netloc and parts.path and not parts.path.startswith('/'):
        path = '/' + parts.path
        if (path.rstrip('/') or '/') in aliases:
            return urlunsplit(('', '', path, parts.query, parts.fragment))
    return value


def internal_page(value, download=False):
    """HTML pages stay in one tab; downloads and media keep their targets."""
    parts = urlsplit(value)
    return not download and not parts.scheme and not parts.netloc and not MEDIA_EXTENSIONS.search(parts.path)


def canonical_embedded_urls(value):
    """Also handle site URLs inside source inline CSS declarations."""
    pattern = r'(?:(?:https?:)?//)(?:www\.)?dyne-tx\.com(?=/|[?#]|$)|(?:(?:https?:)?//)dynetxdev\.wpengine\.com(?=/|[?#]|$)'
    return re.sub(pattern, lambda m: '' if value[m.end():].startswith('/') else '/', value, flags=re.I)
