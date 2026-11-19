"""Gane & Sarson Data Flow Diagrams (Context + Level 0).

Notation (Gane & Sarson):
  - External entity: square / sharp rectangle
  - Process: rounded rectangle (NOT a circle — circles are Yourdon/DeMarco)
  - Data store: open rectangle (top + bottom + right; left open with inner vertical)
  - Data flow: labeled arrow
"""

from __future__ import annotations

from pathlib import Path

import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch, FancyArrowPatch, Rectangle

ROOT = Path(r"C:\xampp new\htdocs\CAPSTONE\Docs")
OUT_CONTEXT = ROOT / "Figure_DFD_Context_GaneSarson.png"
OUT_LEVEL0 = ROOT / "Figure_DFD_Level0_GaneSarson.png"


def _setup(w: float, h: float):
    fig, ax = plt.subplots(figsize=(w, h), dpi=220)
    ax.set_xlim(0, w)
    ax.set_ylim(0, h)
    ax.axis("off")
    fig.patch.set_facecolor("white")
    ax.set_facecolor("white")
    return fig, ax


def entity(ax, x, y, w, h, text, fs=9):
    ax.add_patch(
        FancyBboxPatch(
            (x - w / 2, y - h / 2),
            w,
            h,
            boxstyle="square,pad=0.01",
            lw=1.6,
            edgecolor="#111",
            facecolor="#f3f4f6",
            zorder=3,
        )
    )
    ax.text(x, y, text, ha="center", va="center", fontsize=fs, color="#111", zorder=4, linespacing=1.15)


def process(ax, x, y, w, h, num, text, fs=8.2):
    """Gane & Sarson process = rounded rectangle."""
    ax.add_patch(
        FancyBboxPatch(
            (x - w / 2, y - h / 2),
            w,
            h,
            boxstyle="round,pad=0.02,rounding_size=0.35",
            lw=1.7,
            edgecolor="#0b5d3b",
            facecolor="#ecfdf5",
            zorder=3,
        )
    )
    ax.text(x, y + h * 0.22, num, ha="center", va="center", fontsize=8, fontweight="bold", color="#065f46", zorder=4)
    ax.text(x, y - h * 0.18, text, ha="center", va="center", fontsize=fs, color="#111", zorder=4, linespacing=1.1)


def store(ax, x, y, w, h, text, fs=8):
    left, right = x - w / 2, x + w / 2
    top, bot = y + h / 2, y - h / 2
    ax.plot([left, right], [top, top], color="#111", lw=1.55, zorder=3)
    ax.plot([left, right], [bot, bot], color="#111", lw=1.55, zorder=3)
    ax.plot([right, right], [bot, top], color="#111", lw=1.55, zorder=3)
    ax.plot([left + 0.16, left + 0.16], [bot, top], color="#111", lw=1.55, zorder=3)
    ax.add_patch(Rectangle((left + 0.16, bot), w - 0.16, h, facecolor="#fff7ed", edgecolor="none", zorder=2))
    ax.text(x + 0.08, y, text, ha="center", va="center", fontsize=fs, zorder=4, linespacing=1.1)


def flow(ax, x1, y1, x2, y2, label=None, loff=(0, 0.18), color="#1f2937"):
    ax.add_patch(
        FancyArrowPatch(
            (x1, y1),
            (x2, y2),
            arrowstyle="-|>",
            mutation_scale=12,
            lw=1.25,
            color=color,
            zorder=2,
        )
    )
    if label:
        ax.text(
            (x1 + x2) / 2 + loff[0],
            (y1 + y2) / 2 + loff[1],
            label,
            ha="center",
            va="center",
            fontsize=7,
            color="#111",
            zorder=5,
            bbox=dict(boxstyle="round,pad=0.12", fc="white", ec="none", alpha=0.95),
        )


def legend_gane(ax, x, y):
    ax.text(x, y + 0.55, "Gane & Sarson notation", ha="left", fontsize=9, fontweight="bold")
    ax.add_patch(Rectangle((x, y), 0.55, 0.32, facecolor="#f3f4f6", edgecolor="#111", lw=1.1))
    ax.text(x + 0.7, y + 0.16, "External entity", ha="left", va="center", fontsize=7.5)
    ax.add_patch(
        FancyBboxPatch((x + 3.2, y), 0.7, 0.32, boxstyle="round,pad=0.01,rounding_size=0.12", facecolor="#ecfdf5", edgecolor="#0b5d3b", lw=1.2)
    )
    ax.text(x + 4.05, y + 0.16, "Process", ha="left", va="center", fontsize=7.5)
    ax.plot([x + 5.6, x + 6.45], [y + 0.28, y + 0.28], color="#111", lw=1.2)
    ax.plot([x + 5.6, x + 6.45], [y + 0.04, y + 0.04], color="#111", lw=1.2)
    ax.plot([x + 6.45, x + 6.45], [y + 0.04, y + 0.28], color="#111", lw=1.2)
    ax.plot([x + 5.72, x + 5.72], [y + 0.04, y + 0.28], color="#111", lw=1.2)
    ax.text(x + 6.6, y + 0.16, "Data store", ha="left", va="center", fontsize=7.5)
    ax.annotate("", xy=(x + 9.1, y + 0.16), xytext=(x + 8.3, y + 0.16), arrowprops=dict(arrowstyle="-|>", color="#333", lw=1.1))
    ax.text(x + 9.25, y + 0.16, "Data flow", ha="left", va="center", fontsize=7.5)


def build_context():
    fig, ax = _setup(14, 9.2)
    ax.text(7, 8.75, "Enrollment Analytics & Decision Support System", ha="center", fontsize=13, fontweight="bold")
    ax.text(7, 8.35, "Context Diagram (Level 0 Context) — Gane & Sarson", ha="center", fontsize=10.5, color="#333")

    process(ax, 7.0, 4.6, 3.6, 1.7, "0", "Enrollment Analytics &\nDecision Support System", fs=8.5)

    entity(ax, 1.7, 7.0, 2.3, 0.95, "CEIT Administrator\n& Staff")
    entity(ax, 1.7, 4.6, 2.3, 0.95, "Faculty Adviser")
    entity(ax, 1.7, 2.2, 2.3, 0.95, "Student\n(SPR form)")
    entity(ax, 12.3, 7.0, 2.3, 0.95, "Decision Maker\n/ Dean")
    entity(ax, 12.3, 2.4, 2.3, 0.95, "System\nAdministrator")

    flow(ax, 2.85, 7.0, 5.2, 5.2, "login / CSV-Excel\nimport / filters", loff=(0.1, 0.35))
    flow(ax, 5.2, 5.0, 2.85, 6.7, "dashboard / records\n/ risk reports", loff=(-0.15, -0.35))
    flow(ax, 2.85, 4.6, 5.2, 4.6, "student filters\n& interventions", loff=(0, 0.22))
    flow(ax, 5.2, 4.3, 2.85, 4.3, "at-risk analysis\n& support plan", loff=(0, -0.22))
    flow(ax, 2.85, 2.4, 5.2, 3.9, "SPR form data", loff=(-0.2, 0.15))
    flow(ax, 5.2, 3.95, 2.85, 2.55, "SPR confirmation", loff=(0.25, -0.25))
    flow(ax, 8.8, 5.2, 11.15, 6.7, "forecasts /\ninsights / alerts", loff=(0.15, 0.2))
    flow(ax, 11.15, 6.7, 8.8, 5.15, "review request", loff=(-0.1, -0.35))
    flow(ax, 11.15, 2.6, 8.8, 3.95, "user / role /\ntheme config", loff=(0.2, 0.25))
    flow(ax, 8.8, 4.0, 11.15, 2.7, "account status\n/ audit summary", loff=(-0.15, -0.2))

    legend_gane(ax, 0.6, 0.35)
    ax.text(7, 0.08, "Figure. Context Diagram using Gane & Sarson notation", ha="center", fontsize=10, fontstyle="italic")
    fig.savefig(OUT_CONTEXT, bbox_inches="tight", facecolor="white")
    plt.close(fig)
    print("Saved:", OUT_CONTEXT)


def build_level0():
    fig, ax = _setup(16, 11.2)
    ax.text(8, 10.7, "Enrollment Analytics & Decision Support System", ha="center", fontsize=13, fontweight="bold")
    ax.text(8, 10.3, "Data Flow Diagram (Level 0) — Gane & Sarson", ha="center", fontsize=10.5, color="#333")

    # Entities
    entity(ax, 1.5, 8.4, 2.2, 0.95, "Authorized\nUsers")
    entity(ax, 1.5, 3.2, 2.2, 0.95, "System\nAdministrator")
    entity(ax, 14.5, 7.6, 2.2, 0.95, "Decision\nMaker")

    # Processes (rounded rectangles — Gane & Sarson)
    process(ax, 4.5, 8.4, 2.0, 1.15, "1.0", "Login &\nAuthorization")
    process(ax, 7.5, 8.4, 2.15, 1.15, "2.0", "Data\nManagement")
    process(ax, 10.7, 8.4, 2.15, 1.15, "3.0", "Analytics &\nReporting")
    process(ax, 10.7, 5.3, 2.15, 1.15, "4.0", "Forecasting")
    process(ax, 13.5, 5.3, 2.15, 1.15, "5.0", "Decision\nSupport")
    process(ax, 4.5, 3.2, 2.15, 1.15, "6.0", "Administration")

    # Stores
    store(ax, 7.5, 6.15, 2.7, 0.7, "D1  Student &\nEnrollment Data")
    store(ax, 7.5, 4.85, 2.7, 0.65, "D2  Academic Refs\n& SPR")
    store(ax, 13.5, 3.1, 2.6, 0.75, "D3  Forecasts &\nInsights / Alerts")
    store(ax, 7.5, 2.0, 2.7, 0.65, "D4  Audit Logs")

    # Flows
    flow(ax, 2.6, 8.55, 3.5, 8.55, "credentials", loff=(0, 0.2))
    flow(ax, 3.5, 8.2, 2.6, 8.2, "session / denial", loff=(0, -0.2))
    flow(ax, 5.5, 8.4, 6.4, 8.4, "authorized access", loff=(0, 0.22))
    flow(ax, 2.6, 7.95, 6.4, 7.95, "CSV/Excel import\n& filters", loff=(0.3, -0.35))
    flow(ax, 6.45, 8.85, 2.6, 8.85, "records / status", loff=(0.2, 0.2))
    flow(ax, 7.5, 7.8, 7.5, 6.55, "save / update", loff=(0.7, 0))
    flow(ax, 6.6, 6.15, 6.55, 7.75, "read records", loff=(-0.75, 0))
    flow(ax, 7.5, 7.75, 7.5, 5.25, "read/write SPR\n& refs", loff=(0.85, -0.4))
    flow(ax, 8.6, 8.4, 9.6, 8.4, "clean dataset", loff=(0, 0.22))
    flow(ax, 9.7, 7.85, 8.4, 6.5, "query enrollment", loff=(-0.2, 0.05))
    flow(ax, 8.5, 6.4, 9.8, 7.8, "aggregates", loff=(0.55, -0.2))
    flow(ax, 10.7, 7.8, 10.7, 5.95, "trend metrics", loff=(0.7, 0))
    flow(ax, 11.8, 5.3, 12.4, 5.3, "forecast result", loff=(0, 0.2))
    flow(ax, 13.5, 4.7, 13.5, 3.55, "store insights", loff=(0.7, 0))
    flow(ax, 13.8, 5.85, 14.5, 7.15, "insights &\nrecommendations", loff=(0.35, -0.05))
    flow(ax, 14.4, 7.2, 13.8, 5.95, "review request", loff=(0.45, 0.15))
    flow(ax, 11.75, 8.55, 13.5, 7.95, "analytics summary", loff=(0.2, 0.25))
    flow(ax, 2.6, 3.35, 3.4, 3.35, "user / role changes", loff=(0, 0.2))
    flow(ax, 3.4, 3.0, 2.6, 3.0, "account status", loff=(0, -0.2))
    flow(ax, 5.55, 3.4, 6.5, 4.7, "maintain refs", loff=(0.35, 0.15))
    flow(ax, 5.5, 2.75, 6.5, 2.25, "config events", loff=(0.4, 0.1))

    # Audit dashed
    for px, py in [(4.5, 7.8), (7.5, 7.8), (10.7, 7.8), (10.7, 4.7), (13.5, 4.7), (4.5, 2.6)]:
        ax.annotate(
            "",
            xy=(7.0, 2.35),
            xytext=(px, py),
            arrowprops=dict(arrowstyle="-|>", color="#6b7280", lw=1.0, linestyle=(0, (3, 2))),
            zorder=1,
        )
    ax.text(4.2, 1.35, "Dashed arrows = audit events to D4", fontsize=7.5, color="#666", style="italic")

    legend_gane(ax, 0.5, 0.25)
    ax.text(8, 0.05, "Figure. Level 0 DFD using Gane & Sarson notation", ha="center", fontsize=10.5, fontstyle="italic")
    fig.savefig(OUT_LEVEL0, bbox_inches="tight", facecolor="white")
    plt.close(fig)
    print("Saved:", OUT_LEVEL0)


if __name__ == "__main__":
    build_context()
    build_level0()
