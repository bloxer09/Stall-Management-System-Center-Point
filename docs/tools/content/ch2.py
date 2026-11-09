"""Chapter II: literature unchanged; only the synthesis wording is aligned (handled by ch1's global rewrites)."""


def apply(b):
    # The synthesis lists the system's features; make sure it reflects the current set.
    p = b.find('These gaps justify the development of the Web Based Stall Management System')
    text = p.text
    if 'announcements' not in text:
        b.set(p, text.rstrip('.') + ', together with announcements, statements of account, encrypted backups, and layered web security protections.')
