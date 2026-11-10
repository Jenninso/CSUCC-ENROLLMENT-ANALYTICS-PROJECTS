from pathlib import Path

p = Path(r"C:\xampp new\htdocs\CAPSTONE\_generate_full_paper.py")
t = p.read_text(encoding="utf-8")

replacements = [
    ("Laravel (PHP), MySQL,", "Laravel (PHP), PostgreSQL,"),
    (
        "XAMPP/Apache with \"\n    \"MySQL during development and demonstration).",
        "XAMPP/Apache with \"\n    \"PostgreSQL during development and demonstration).",
    ),
    (
        "to run on a campus or local server environment (for example, XAMPP/Apache with "
        '"\n    "MySQL during development and demonstration).',
        "to run on a campus or local server environment (for example, XAMPP/Apache with "
        '"\n    "PostgreSQL during development and demonstration).',
    ),
    ("stored in MySQL tables linked", "stored in PostgreSQL tables linked"),
    (
        "MySQL / MariaDB – relational database for users, academic structures,",
        "PostgreSQL (with pgAdmin 4) – relational database for users, academic structures,",
    ),
    (
        "XAMPP (Apache, MySQL, PHP) – local development and demonstration environment",
        "XAMPP (Apache, PHP) + PostgreSQL/pgAdmin 4 – local development and demonstration environment",
    ),
    (
        '("XAMPP (Apache, MySQL, PHP 8.x)", "Local server stack"),',
        '("XAMPP (Apache, PHP 8.x)", "Local web server stack"),\n'
        '    ("PostgreSQL / pgAdmin 4", "Database server and administration tool"),',
    ),
    (
        "→ Eloquent Models → MySQL. Presentation uses Blade views with Chart.js for",
        "→ Eloquent Models → PostgreSQL (managed via pgAdmin 4). Presentation uses Blade views with Chart.js for",
    ),
    (
        "database design (MySQL), basic data analytics and visualization",
        "database design (PostgreSQL / pgAdmin 4), basic data analytics and visualization",
    ),
]

for old, new in replacements:
    if old not in t:
        print("MISSING:", old[:70])
    else:
        t = t.replace(old, new)
        print("OK:", old[:50])

p.write_text(t, encoding="utf-8")
print("generator updated")
