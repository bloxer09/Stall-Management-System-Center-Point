"""Facts for the manuscript, read from the code so the document never states something the system doesn't do.

Output: docs/tools/facts.json. Run from the repository root: python docs/tools/collect_facts.py
"""
import json
import os
import re
import subprocess

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))


def lock_version(name):
    lock = json.load(open(os.path.join(ROOT, 'composer.lock'), encoding='utf-8'))
    for pkg in lock['packages'] + lock.get('packages-dev', []):
        if pkg['name'] == name:
            return pkg['version'].lstrip('v')
    return None


def npm_version(name):
    lock_path = os.path.join(ROOT, 'package-lock.json')
    if os.path.exists(lock_path):
        lock = json.load(open(lock_path, encoding='utf-8'))
        entry = lock.get('packages', {}).get(f'node_modules/{name}')
        if entry:
            return entry['version']
    pkg = json.load(open(os.path.join(ROOT, 'package.json'), encoding='utf-8'))
    return {**pkg.get('dependencies', {}), **pkg.get('devDependencies', {})}.get(name)


def php_version():
    out = subprocess.run(['php', '-r', 'echo PHP_VERSION;'], capture_output=True, text=True, cwd=ROOT)
    return out.stdout.strip()


def chart_version():
    path = os.path.join(ROOT, 'public', 'assets', 'js', 'chart.min.js')
    head = open(path, encoding='utf-8', errors='ignore').read(400) if os.path.exists(path) else ''
    m = re.search(r'Chart\.js v([\d.]+)', head)
    return m.group(1) if m else None


def schedule():
    text = open(os.path.join(ROOT, 'routes', 'console.php'), encoding='utf-8').read()
    return re.findall(r"Schedule::command\('([^']+)'\)->([^;]+);", text)


def routes():
    out = subprocess.run(['php', 'artisan', 'route:list', '--json'], capture_output=True, text=True, cwd=ROOT)
    rows = json.loads(out.stdout)
    pick = lambda prefix: sorted({r['uri'] for r in rows if r['uri'].startswith(prefix) and 'GET' in r['method']})
    return {'admin': pick('admin/'), 'tenant': pick('user/'), 'public': sorted({r['uri'] for r in rows if 'GET' in r['method'] and not r['uri'].startswith(('admin/', 'user/', 'livewire', '_', 'api/', 'sms-catcher', 'storage', 'up'))})}


def tests():
    out = subprocess.run(['php', 'artisan', 'test'], capture_output=True, text=True, cwd=ROOT)
    text = re.sub(r'\x1b\[[0-9;]*m', '', out.stdout)
    total = re.search(r'Tests:\s+(\d+) passed \((\d+) assertions\)', text)
    files = {}
    current = None
    for line in text.splitlines():
        m = re.match(r'\s*(PASS|FAIL)\s+Tests\\(Unit|Feature)\\(\w+)', line)
        if m:
            current = m.group(3)
            files[current] = 0
        elif current and re.match(r'\s*[✓⨯]', line):
            files[current] += 1
    return {'passed': int(total.group(1)), 'assertions': int(total.group(2)), 'by_file': files}


facts = {
    'versions': {
        'php': php_version(),
        'laravel': lock_version('laravel/framework'),
        'livewire': lock_version('livewire/livewire'),
        'socialite': lock_version('laravel/socialite'),
        'dompdf': lock_version('dompdf/dompdf'),
        'phpspreadsheet': lock_version('phpoffice/phpspreadsheet'),
        'phpunit': lock_version('phpunit/phpunit'),
        'tailwindcss': npm_version('tailwindcss'),
        'daisyui': npm_version('daisyui'),
        'vite': npm_version('vite'),
        'alpinejs': 'bundled with Livewire ' + (lock_version('livewire/livewire') or ''),
        'chartjs': chart_version(),
    },
    'roles': ['Administrator', 'Tenant', 'Visitor (not signed in)'],
    'schedule': [{'command': c, 'when': w} for c, w in schedule()],
    'routes': routes(),
    'tests': tests(),
}
open(os.path.join(ROOT, 'docs', 'tools', 'facts.json'), 'w', encoding='utf-8').write(json.dumps(facts, indent=2))
print(json.dumps({k: facts[k] for k in ('versions', 'schedule')}, indent=1))
print('tests', facts['tests']['passed'], 'files', len(facts['tests']['by_file']), 'admin pages', len(facts['routes']['admin']))
