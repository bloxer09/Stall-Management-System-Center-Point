import json
from collections import defaultdict
from pathlib import Path

root = Path('test-results/qa-2026-10-10')
runs = ['initial-chromium', 'expanded-initial', 'expanded-verified', 'cross-browser', 'mobile-verified', 'mobile-final']
latest = {}
history = []

def specs(suites):
    for suite in suites:
        yield from suite.get('specs', [])
        yield from specs(suite.get('suites', []))

for name in runs:
    path = root / name / 'e2e-results.json'
    if not path.exists():
        continue
    report = json.loads(path.read_text(encoding='utf-8'))
    history.append({'run': name, 'stats': report['stats']})
    for spec in specs(report['suites']):
        for test in spec.get('tests', []):
            key = (spec['file'], spec['title'], test['projectName'])
            latest[key] = {
                'file': spec['file'], 'title': spec['title'], 'project': test['projectName'],
                'status': test['status'], 'evidenceRun': name,
                'errors': [error.get('message', '') for result in test.get('results', []) for error in result.get('errors', [])],
            }

totals = defaultdict(lambda: defaultdict(int))
for result in latest.values():
    totals[result['project']][result['status']] += 1

summary = {'date': '2026-10-10', 'latestResultPerScenario': list(latest.values()), 'totals': dict(totals), 'runHistory': history}
(root / 'summary.json').write_text(json.dumps(summary, indent=2), encoding='utf-8')
print(json.dumps({'totals': summary['totals'], 'scenarios': len(latest), 'runs': history}, indent=2))
