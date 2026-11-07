#!/usr/bin/env python3
"""Select expensive CI only for a review-ready runtime candidate or manual verification."""
import argparse
import json
import os
from pathlib import Path
import re
import subprocess

STANDARD = [{'php': '8.3', 'database': 'mysql:8.4'},
            {'php': '8.4', 'database': 'mariadb:10.6'}]
FULL = [{'php': php, 'database': database}
        for php in ('8.3', '8.4') for database in ('mysql:8.4', 'mariadb:10.6')]
DOC_FILES = {'README.md', 'CHANGELOG.md', 'AGENTS.md', 'LICENSE'}


def documentation_only(path):
    # Shipped documentation still receives build/package checks in the lightweight job.
    # Unknown paths and CI/test/build changes always require integration verification.
    return path in DOC_FILES or path.startswith(('docs/', 'component/administrator/docs/'))


def plan(event_name, event, files):
    if event_name == 'workflow_dispatch':
        scope = event.get('inputs', {}).get('matrix', 'standard')
        if scope not in ('standard', 'full'):
            raise ValueError('Unsupported manual matrix')
        integration = True
        reason = 'Manual verification'
    elif event_name == 'pull_request':
        draft = event['pull_request']['draft']
        if not isinstance(draft, bool):
            raise ValueError('Missing or invalid draft state')
        scope = 'standard'
        runtime = any(not documentation_only(path) for path in files)
        integration = not draft and runtime
        reason = 'Draft: lightweight checks only' if draft else ('Review-ready runtime candidate' if runtime else 'Documentation: lightweight checks only')
    else:
        raise ValueError('Unsupported event')
    return {'integration': integration, 'scope': scope, 'reason': reason,
            'matrix': {'include': FULL if scope == 'full' else STANDARD}}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--event', default=os.environ.get('GITHUB_EVENT_PATH'), required=False)
    args = parser.parse_args()
    if not args.event:
        parser.error('An event payload is required')
    event = json.loads(Path(args.event).read_text())
    name = os.environ['GITHUB_EVENT_NAME']
    files = []
    if name == 'pull_request':
        base = event['pull_request']['base']['sha']
        if not isinstance(base, str) or not re.fullmatch(r'[a-f0-9]{40}', base):
            raise ValueError('Invalid base SHA')
        files = subprocess.check_output(['git', 'diff', '--name-only', '-z', base, 'HEAD']).decode().rstrip('\0').split('\0')
        files = [path for path in files if path]
    result = plan(name, event, files)
    print(json.dumps(result))
    if output := os.environ.get('GITHUB_OUTPUT'):
        with open(output, 'a') as stream:
            stream.write('integration=' + str(result['integration']).lower() + '\n')
            stream.write('matrix=' + json.dumps(result['matrix'], separators=(',', ':')) + '\n')


if __name__ == '__main__':
    main()
