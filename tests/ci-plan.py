"""Policy checks: avoid skipping changed runtime or launching integrations for draft/docs."""
import importlib.util
from pathlib import Path
import unittest

spec = importlib.util.spec_from_file_location('ci_plan', Path(__file__).resolve().parents[1] / 'tools/ci_plan.py')
ci = importlib.util.module_from_spec(spec)
spec.loader.exec_module(ci)


class PlanTests(unittest.TestCase):
    def pr(self, files, draft=False):
        return ci.plan('pull_request', {'pull_request': {'draft': draft}}, files)

    def test_draft_runtime_stays_light(self):
        self.assertFalse(self.pr(['component/administrator/src/Model/CourseModel.php'], True)['integration'])

    def test_ready_runtime_runs(self):
        self.assertTrue(self.pr(['component/administrator/src/Model/CourseModel.php'])['integration'])

    def test_documentation_and_shipped_guides_stay_light(self):
        self.assertFalse(self.pr(['README.md', 'docs/NEXT_STEPS.md', 'component/administrator/docs/COURSE_EDITOR.md'])['integration'])

    def test_mixed_changes_run(self):
        self.assertTrue(self.pr(['docs/VIDEO.md', 'plugins/jlmsvideo/youtube/src/Provider/Provider.php'])['integration'])

    def test_unknown_build_tests_and_workflows_fail_safe(self):
        for path in ['unknown.file', 'tools/build.py', 'tests/joomla-video.php', '.github/workflows/ci.yml', 'component/media/css/jlms.css']:
            with self.subTest(path=path):
                self.assertTrue(self.pr([path])['integration'])

    def test_manual_matrix(self):
        standard = ci.plan('workflow_dispatch', {}, [])
        full = ci.plan('workflow_dispatch', {'inputs': {'matrix': 'full'}}, [])
        self.assertTrue(standard['integration'])
        self.assertTrue(full['integration'])
        self.assertEqual(len(standard['matrix']['include']), 2)
        self.assertEqual(len(full['matrix']['include']), 4)
        self.assertEqual({(r['php'], r['database']) for r in full['matrix']['include']},
                         {(p, d) for p in ['8.3', '8.4'] for d in ['mysql:8.4', 'mariadb:10.6']})

    def test_standard_covers_both_axes_and_artifact_environment(self):
        rows = self.pr(['component/jlms.xml'])['matrix']['include']
        self.assertEqual({r['php'] for r in rows}, {'8.3', '8.4'})
        self.assertEqual({r['database'] for r in rows}, {'mysql:8.4', 'mariadb:10.6'})
        self.assertIn({'php': '8.4', 'database': 'mariadb:10.6'}, rows)

    def test_invalid_payload_or_scope_cannot_silently_skip(self):
        for name, event in [('push', {}), ('pull_request', {'pull_request': {'draft': 'false'}}), ('workflow_dispatch', {'inputs': {'matrix': 'none'}})]:
            with self.subTest(name=name, event=event), self.assertRaises(ValueError):
                ci.plan(name, event, ['component/jlms.xml'])


if __name__ == '__main__':
    unittest.main()
