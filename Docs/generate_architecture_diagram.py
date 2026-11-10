"""System Architecture Diagram (Figure 2) — no XAMPP; PostgreSQL + pgAdmin 4."""

import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch

OUT = r"C:\xampp new\htdocs\CAPSTONE\Docs\Figure2_System_Architecture.png"

fig, ax = plt.subplots(figsize=(13, 11.5), dpi=240)
ax.set_xlim(0, 13)
ax.set_ylim(0, 11.5)
ax.axis("off")
fig.patch.set_facecolor("white")
ax.set_facecolor("white")

ax.text(
    6.5,
    11.05,
    "System Architecture Diagram",
    ha="center",
    va="center",
    fontsize=14,
    fontweight="bold",
    color="#111",
)
ax.text(
    6.5,
    10.65,
    "Enrollment Analytics & Decision Support System (Laravel MVC + PostgreSQL)",
    ha="center",
    va="center",
    fontsize=9.5,
    color="#333",
)


def layer_box(x, y, w, h, title, face="#f7f7f7"):
    ax.add_patch(
        FancyBboxPatch(
            (x, y),
            w,
            h,
            boxstyle="round,pad=0.02,rounding_size=0.08",
            lw=1.5,
            edgecolor="#222",
            facecolor=face,
            zorder=1,
        )
    )
    ax.text(
        x + 0.18,
        y + h - 0.28,
        title,
        ha="left",
        va="center",
        fontsize=9.5,
        fontweight="bold",
        color="#111",
        zorder=2,
    )


def chip(x, y, w, h, text, face="white"):
    ax.add_patch(
        FancyBboxPatch(
            (x, y),
            w,
            h,
            boxstyle="round,pad=0.02,rounding_size=0.06",
            lw=1.15,
            edgecolor="#333",
            facecolor=face,
            zorder=3,
        )
    )
    ax.text(
        x + w / 2,
        y + h / 2,
        text,
        ha="center",
        va="center",
        fontsize=7.8,
        color="#111",
        zorder=4,
        linespacing=1.1,
    )


def varrow(x, y1, y2):
    ax.annotate(
        "",
        xy=(x, y2),
        xytext=(x, y1),
        arrowprops=dict(arrowstyle="-|>", color="#222", lw=1.35),
        zorder=5,
    )


# 1 Client
layer_box(1.2, 8.95, 10.6, 1.25, "1  Client / Presentation", face="#f3f6f9")
chip(1.6, 9.15, 2.4, 0.7, "Web Browser")
chip(4.3, 9.15, 2.6, 0.7, "Blade Views")
chip(7.2, 9.15, 2.2, 0.7, "Chart.js")
chip(9.7, 9.15, 1.8, 0.7, "Alpine.js")
varrow(6.5, 8.95, 8.68)

# 2 Application runtime (NOT calling this PostgreSQL — that's the DB)
layer_box(1.2, 7.35, 10.6, 1.25, "2  Application Runtime", face="#f6f6f2")
chip(2.4, 7.75, 3.0, 0.55, "PHP 8.x")
chip(5.7, 7.75, 3.0, 0.55, "Laravel Framework")
chip(9.0, 7.75, 2.3, 0.55, "Composer")
ax.text(
    6.5,
    7.5,
    "Runs the web application and handles HTTP requests from the browser",
    ha="center",
    va="center",
    fontsize=7,
    color="#555",
    style="italic",
)
varrow(6.5, 7.35, 7.08)

# 3 Application
layer_box(1.2, 4.7, 10.6, 2.3, "3  Application Layer (Laravel MVC)", face="#f4f8f4")
chip(1.55, 6.15, 4.3, 0.55, "Permission / Auth Middleware")
chip(6.1, 6.15, 2.4, 0.55, "Routes")
chip(8.75, 6.15, 2.7, 0.55, "Controllers")
ax.text(1.55, 5.8, "Services (business rules)", ha="left", va="center", fontsize=7.5, color="#444", style="italic")
chip(1.55, 4.95, 2.2, 0.6, "Analytics\nService")
chip(4.0, 4.95, 2.2, 0.6, "Forecast\nService")
chip(6.45, 4.95, 2.2, 0.6, "Insight\nService")
chip(8.9, 4.95, 2.5, 0.6, "Import\nService")
varrow(6.5, 4.7, 4.43)

# 4 Data access
layer_box(1.2, 3.25, 10.6, 1.1, "4  Data Access Layer", face="#f9f5f2")
chip(2.4, 3.5, 2.8, 0.55, "Eloquent ORM")
chip(5.5, 3.5, 2.6, 0.55, "pdo_pgsql")
chip(8.4, 3.5, 2.8, 0.55, "Migrations")
varrow(6.5, 3.25, 2.98)

# 5 Database — PostgreSQL + pgAdmin 4
layer_box(1.2, 1.55, 10.6, 1.35, "5  Database Server", face="#eef6ee")
chip(2.3, 1.85, 4.0, 0.7, "PostgreSQL\nDatabase Server", face="#e8f5e9")
chip(6.8, 1.85, 4.0, 0.7, "pgAdmin 4\n(DB Administration)", face="#e8f5e9")

ax.add_patch(
    FancyBboxPatch(
        (1.2, 0.45),
        10.6,
        0.9,
        boxstyle="round,pad=0.02,rounding_size=0.06",
        lw=1.1,
        edgecolor="#777",
        facecolor="#fafafa",
        zorder=1,
    )
)
ax.text(
    6.5,
    0.9,
    "PostgreSQL is the database server that stores enrollment and system data.\n"
    "pgAdmin 4 is the tool used to manage and query that PostgreSQL database.",
    ha="center",
    va="center",
    fontsize=7.6,
    color="#333",
    linespacing=1.3,
)

ax.text(
    6.5,
    0.15,
    "Figure 2. System Architecture Diagram",
    ha="center",
    va="center",
    fontsize=12,
    fontstyle="italic",
    color="#111",
)

ax.text(12.35, 6.5, "Request\nflow\n↓", ha="center", va="center", fontsize=8, color="#555", linespacing=1.2)
ax.annotate(
    "",
    xy=(12.35, 2.1),
    xytext=(12.35, 9.3),
    arrowprops=dict(arrowstyle="-|>", color="#888", lw=1.2),
    zorder=0,
)

plt.tight_layout(pad=0.25)
fig.savefig(OUT, bbox_inches="tight", facecolor="white", edgecolor="none")
plt.close()
print("Saved:", OUT)
