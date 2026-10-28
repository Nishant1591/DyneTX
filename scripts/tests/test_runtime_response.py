"""Regressions for video redirects reaching the public-route verifier."""
import importlib.util
from email.message import Message
from pathlib import Path
import unittest

SOURCE = Path(__file__).resolve().parents[1] / 'verify-legacy-runtime.py'
SPEC = importlib.util.spec_from_file_location('runtime_verifier', SOURCE)
VERIFIER = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(VERIFIER)


class Response:
    def __init__(self, content_type, payload=b'', forbid_read=False):
        self.headers = Message()
        self.headers['Content-Type'] = content_type
        self.payload = payload
        self.forbid_read = forbid_read
        self.read_count = 0

    def read(self):
        self.read_count += 1
        if self.forbid_read:
            raise AssertionError('A binary/media response body must not be downloaded for HTML parsing.')
        return self.payload


class RuntimeResponseTests(unittest.TestCase):
    def test_video_redirect_destination_is_not_read_or_decoded(self):
        response = Response('video/mp4', forbid_read=True)
        self.assertEqual(VERIFIER.read_text_response(response, True), '')
        self.assertEqual(response.read_count, 0)

    def test_other_binary_assets_are_not_read(self):
        for content_type in ['application/pdf', 'image/png', 'application/octet-stream']:
            with self.subTest(content_type=content_type):
                response = Response(content_type, forbid_read=True)
                self.assertEqual(VERIFIER.read_text_response(response, True), '')

    def test_html_xhtml_and_css_still_reach_the_existing_parsers(self):
        for content_type in ['text/html; charset=UTF-8', 'application/xhtml+xml', 'text/css']:
            with self.subTest(content_type=content_type):
                response = Response(content_type, b'<h1>Accessible text</h1>')
                self.assertEqual(VERIFIER.read_text_response(response, True), '<h1>Accessible text</h1>')
                self.assertEqual(response.read_count, 1)

    def test_status_only_asset_checks_do_not_download_text_bodies(self):
        response = Response('text/html', forbid_read=True)
        self.assertEqual(VERIFIER.read_text_response(response, False), '')
        self.assertEqual(response.read_count, 0)


if __name__ == '__main__':
    unittest.main()
