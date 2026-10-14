"""Checks source parsing, safe HTML conversion and the generated content contract."""
import importlib.util
import json
import pathlib
import tempfile
import unittest

ROOT = pathlib.Path(__file__).resolve().parents[3]


def load(name, filename):
    spec = importlib.util.spec_from_file_location(name, ROOT / 'scripts/legacy' / filename)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


class ArchiveTests(unittest.TestCase):
    def test_sql_parser_handles_escaped_literals_without_execution(self):
        extractor = load('extract_test', 'extract-archive.py')
        with tempfile.NamedTemporaryFile(mode='w+', suffix='.sql') as source:
            source.write("INSERT INTO `wp_posts` VALUES (1,'O\\'Brien','line\\nnext','C:\\\\data',NULL),(2,'x,y);z',3);\n")
            source.write("INSERT INTO `wp_users` VALUES (4,'excluded');\n")
            source.flush()
            self.assertEqual(list(extractor.rows(source.name, 'wp_posts')), [[1, "O'Brien", 'line\nnext', 'C:\\data', None], [2, 'x,y);z', 3]])

    def test_html_preserves_controls_and_drops_executable_content(self):
        builder = load('builder_test', 'build-fixture.py')
        parser = builder.SafeHTML(lambda value: value)
        parser.feed('<section class="article"><!-- hidden --><script>alert(1)</script><a onclick="bad()" href="javascript:bad()" data-mdb-toggle="collapse">Open</a><iframe src="https://evil.example/test"></iframe><video controls src="/media.mp4"></video></section>')
        output = ''.join(parser.output)
        self.assertNotIn('alert', output)
        self.assertNotIn('onclick', output)
        self.assertNotIn('javascript:', output)
        self.assertNotIn('evil.example', output)
        self.assertNotIn('hidden', output)
        self.assertIn('data-mdb-toggle="collapse"', output)
        self.assertIn('<video controls', output)

    def test_navigation_keeps_bare_hosts_queries_fragments_and_known_aliases_local(self):
        navigation = load('navigation_test', 'navigation.py')
        cases = {
            'http://www.dyne-tx.com': '/',
            'https://dyne-tx.com?source=letter#contact': '/?source=letter#contact',
            '//www.dyne-tx.com/about-us/?x=1#contact': '/about-us/?x=1#contact',
            'https://dynetxdev.wpengine.com/privacy-policy/': '/privacy-policy/',
            'new-clinical-trial-opportunity-in-dm1?x=1#study': '/new-clinical-trial-opportunity-in-dm1?x=1#study',
            'https://investors.dyne-tx.com/news-and-events': 'https://investors.dyne-tx.com/news-and-events',
            'https://job-boards.greenhouse.io/dynetx/jobs/123': 'https://job-boards.greenhouse.io/dynetx/jobs/123',
            'https://www.dyne-tx.com.example.org/pipeline': 'https://www.dyne-tx.com.example.org/pipeline',
        }
        aliases = {'/new-clinical-trial-opportunity-in-dm1'}
        for source, expected in cases.items():
            self.assertEqual(navigation.canonical_url(source, aliases), expected)

    def test_only_internal_html_links_lose_new_tab_target(self):
        builder = load('builder_navigation_test', 'build-fixture.py')
        parser = builder.SafeHTML(lambda value: builder.navigation.canonical_url(value))
        parser.feed('<a href="https://www.dyne-tx.com/pipeline/?a=1#DMD" target="_blank">Pipeline</a><a href="/about-us#contact" target="_new">Contact</a><a href="/document.pdf?download=1" target="_blank">PDF</a><a href="/download" download target="_blank">Download</a><a href="https://investors.dyne-tx.com/" target="_blank">Investors</a>')
        output = ''.join(parser.output)
        self.assertIn('<a href="/pipeline/?a=1#DMD">Pipeline</a>', output)
        self.assertIn('<a href="/about-us#contact">Contact</a>', output)
        self.assertIn('<a href="/document.pdf?download=1" target="_blank">PDF</a>', output)
        self.assertIn('<a href="/download" download target="_blank">Download</a>', output)
        self.assertIn('<a href="https://investors.dyne-tx.com/" target="_blank">Investors</a>', output)

    def test_navigation_does_not_rewrite_ids_or_external_modal_targets(self):
        builder = load('builder_navigation_context', 'build-fixture.py')
        parser = builder.SafeHTML(builder.navigation.canonical_embedded_urls, {'/resources', '/story'})
        parser.feed('<div id="resources" class="resources"><a href="story?view=1#details">Story</a><a href="#!" data-mdb-url="https://provider.example/" target="_blank">Provider</a></div>')
        output = ''.join(parser.output)
        self.assertIn('id="resources" class="resources"', output)
        self.assertIn('href="/story?view=1#details"', output)
        self.assertIn('data-mdb-url="https://provider.example/" target="_blank"', output)

    def test_verified_redirects_resolve_only_evidenced_source_urls(self):
        navigation = load('navigation_verified', 'navigation.py')
        self.assertEqual(len(navigation.VERIFIED_REDIRECTS), 321)
        for source, destination in navigation.VERIFIED_REDIRECTS.items():
            self.assertEqual(navigation.canonical_url(source), destination)
            self.assertTrue(destination.startswith('/'))
        unknown = 'https://www.globenewswire.com/Tracker?data=unknown'
        self.assertEqual(navigation.canonical_url(unknown), unknown)

    def test_recovered_media_fixture_has_no_missing_source_uploads(self):
        fixture = json.loads((ROOT / 'data/legacy-content.json').read_text())
        recovered = json.loads((ROOT / 'data/legacy-recovered-media.json').read_text())
        self.assertEqual(fixture['assets']['missing'], [])
        self.assertEqual(len(fixture['assets']['recovered']), 10)
        self.assertEqual(recovered['recovered_count'], 10)
        self.assertTrue(all(r['http_status'] == 200 and r['mp4_signature_verified'] for r in recovered['records']))

    def test_fixture_counts_aliases_and_component_boundaries(self):
        fixture = json.loads((ROOT / 'data/legacy-content.json').read_text())
        records = fixture['records']
        aliases = [row['alias'] for row in records]
        self.assertEqual(len(set(aliases)), len(aliases))
        counts = {bundle: sum(row['bundle'] == bundle for row in records) for bundle in {'dyne_page', 'job', 'leader', 'perspective', 'news'}}
        self.assertEqual(counts, {'dyne_page': 52, 'job': 49, 'leader': 31, 'perspective': 13, 'news': 199})
        home = next(row for row in records if row['alias'] == '/home')
        self.assertEqual([c['type'] for c in home['components']], ['dyne_hero', 'dyne_rich_text', 'dyne_news_list'])
        self.assertNotIn('section-news', home['components'][1]['fields']['field_body_text'])
        about = next(row for row in records if row['alias'] == '/about-us')
        members = [person for c in about['components'] if c['type'] == 'dyne_team_grid' for person in c['fields']['field_items_json']]
        self.assertEqual(len(members), 31)
        self.assertTrue(all(person['bio'] for person in members))
        self.assertEqual(sum(bool(person['photo']) for person in members), 15)
        jobs = [r for r in records if r['bundle'] == 'job']
        self.assertTrue(all(r['fields']['field_apply_url'].startswith('https://') for r in jobs))
        self.assertTrue(all(r['fields']['field_previous_url'] == '/careers-culture#positions' for r in jobs))
        self.assertTrue(all(r['fields']['field_next_url'] in aliases for r in jobs))

    def test_fixture_has_no_wordpress_execution_or_internal_metadata(self):
        fixture = json.loads((ROOT / 'data/legacy-content.json').read_text())
        text = json.dumps(fixture)
        for forbidden in ('wp_users', 'wp_options', 'content_mask_header_scripts_styles', 'post_password', 'wp_user_pass'):
            self.assertNotIn(forbidden, text)
        for row in fixture['records']:
            bodies = [row.get('body', '')] + [c['fields'].get('field_body_text', '') for c in row.get('components', [])]
            for body in bodies:
                self.assertNotRegex(body, r'(?i)<script\b|\sonclick=|\sonerror=|\[workbox[_-]|\[ot_webform')


if __name__ == '__main__':
    unittest.main()
