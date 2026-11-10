"""Clean Level 0 Data Flow Diagram (Figure 4) — easy to read."""

import matplotlib.pyplot as plt
from matplotlib.patches import Circle, FancyBboxPatch, Rectangle

OUT = r"C:\xampp new\htdocs\CAPSTONE\Docs\Figure4_DFD_Level0.png"

fig, ax = plt.subplots(figsize=(16, 10.8), dpi=230)
ax.set_xlim(0, 16)
ax.set_ylim(0, 10.8)
ax.axis("off")
fig.patch.set_facecolor("white")
ax.set_facecolor("white")

ax.text(
    8.0,
    10.35,
    "Enrollment Analytics & Decision Support System",
    ha="center",
    va="center",
    fontsize=13,
    fontweight="bold",
    color="#111",
)
ax.text(
    8.0,
    9.95,
    "Data Flow Diagram (Level 0)",
    ha="center",
    va="center",
    fontsize=10.5,
    color="#333",
)


def entity(x, y, w, h, text):
    ax.add_patch(
        FancyBboxPatch(
            (x - w / 2, y - h / 2),
            w,
            h,
            boxstyle="square,pad=0.01",
            lw=1.55,
            edgecolor="#111",
            facecolor="#eeeeee",
            zorder=3,
        )
    )
    ax.text(x, y, text, ha="center", va="center", fontsize=8.3, color="#111", zorder=4, linespacing=1.15)
    return (x, y, w, h)


def proc(x, y, r, num, text):
    ax.add_patch(Circle((x, y), r, facecolor="white", edgecolor="#111", lw=1.55, zorder=3))
    ax.text(x, y + r * 0.28, num, ha="center", va="center", fontsize=8.2, fontweight="bold", zorder=4)
    ax.text(x, y - r * 0.22, text, ha="center", va="center", fontsize=7.3, zorder=4, linespacing=1.08)
    return (x, y, r)


def store(x, y, w, h, text):
    left, right = x - w / 2, x + w / 2
    top, bot = y + h / 2, y - h / 2
    ax.plot([left, right], [top, top], color="#111", lw=1.55, zorder=3)
    ax.plot([left, right], [bot, bot], color="#111", lw=1.55, zorder=3)
    ax.plot([right, right], [bot, top], color="#111", lw=1.55, zorder=3)
    ax.plot([left + 0.14, left + 0.14], [bot, top], color="#111", lw=1.55, zorder=3)
    ax.text(x + 0.06, y, text, ha="center", va="center", fontsize=7.6, zorder=4, linespacing=1.1)
    return (x, y, w, h)


def arrow(x1, y1, x2, y2, label=None, loff=(0, 0.2), rad=0.0, color="#333", ls="-"):
    ax.annotate(
        "",
        xy=(x2, y2),
        xytext=(x1, y1),
        arrowprops=dict(
            arrowstyle="-|>",
            color=color,
            lw=1.2,
            linestyle=ls,
            connectionstyle=f"arc3,rad={rad}",
            shrinkA=1,
            shrinkB=1,
        ),
        zorder=2,
    )
    if label:
        ax.text(
            (x1 + x2) / 2 + loff[0],
            (y1 + y2) / 2 + loff[1],
            label,
            ha="center",
            va="center",
            fontsize=6.7,
            color="#222",
            zorder=5,
            bbox=dict(boxstyle="round,pad=0.1", fc="white", ec="none", alpha=0.95),
        )


# ---------- External entities (left & right) ----------
entity(1.5, 7.6, 2.15, 1.0, "Authorized\nUsers")
entity(1.5, 3.4, 2.15, 1.0, "System\nAdministrator")
entity(14.5, 7.0, 2.15, 1.0, "Decision\nMaker")

# ---------- Processes (center flow) ----------
# Row 1: Auth -> Data Mgmt -> Analytics
proc(4.4, 7.6, 0.82, "1.0", "Login /\nAuth")
proc(7.3, 7.6, 0.88, "2.0", "Data\nManagement")
proc(10.4, 7.6, 0.88, "3.0", "Analytics")

# Row 2: Forecasting / Decision Support / Administration
proc(10.4, 4.9, 0.88, "4.0", "Forecasting")
proc(13.0, 4.9, 0.95, "5.0", "Decision\nSupport")
proc(4.4, 3.4, 0.88, "6.0", "Administration")

# ---------- Data stores ----------
store(7.3, 5.55, 2.6, 0.72, "D1  Enrollment Data")
store(7.3, 4.35, 2.6, 0.65, "D2  Academic Refs")
store(13.0, 2.85, 2.5, 0.75, "D3  Forecasts &\nInsights / Alerts")
store(7.3, 2.0, 2.6, 0.65, "D4  Audit Logs")

# Optional output sink
ax.add_patch(
    FancyBboxPatch(
        (12.35, 8.35),
        2.3,
        0.85,
        boxstyle="round,pad=0.02,rounding_size=0.08",
        lw=1.2,
        edgecolor="#555",
        facecolor="#f7f7f7",
        linestyle=(0, (4, 3)),
        zorder=2,
    )
)
ax.text(13.5, 8.77, "Charts &\nDashboard Views", ha="center", va="center", fontsize=7.5, color="#333")

# ===================== Main flows (kept minimal) =====================
# Users <-> Auth
arrow(2.6, 7.85, 3.6, 7.85, "login credentials", loff=(0, 0.22))
arrow(3.6, 7.35, 2.6, 7.35, "access rights", loff=(0, -0.22))

# Auth unlocks modules
arrow(5.2, 7.6, 6.4, 7.6, "authorized access", loff=(0, 0.24))
arrow(4.4, 6.78, 4.4, 4.28, "verified session", loff=(-0.7, 0))

# Users <-> Data Management / Analytics (requests & results)
arrow(2.55, 7.2, 6.45, 7.2, "encode / import / filters", loff=(0.4, -0.35), rad=-0.12)
arrow(6.5, 7.95, 2.55, 8.0, "records / status", loff=(0.2, 0.28), rad=0.12)

# Data Management <-> stores
arrow(7.3, 6.72, 7.3, 5.95, "save / update", loff=(0.75, 0))
arrow(6.55, 5.55, 6.5, 6.75, "read records", loff=(-0.8, 0))
arrow(7.9, 6.75, 7.9, 4.75, "update refs", loff=(0.7, -0.2))
arrow(6.55, 4.35, 6.5, 6.75, "read refs", loff=(-0.95, -0.55), rad=-0.05)

# Data Management -> Analytics
arrow(8.2, 7.6, 9.5, 7.6, "clean dataset", loff=(0, 0.24))

# Analytics <-> stores
arrow(9.6, 7.1, 8.2, 5.85, "query enrollment", loff=(-0.15, 0.05), rad=0.08)
arrow(8.3, 5.9, 9.7, 7.0, "aggregates", loff=(0.55, -0.25), rad=-0.05)

# Analytics -> charts + forecasting + decision support
arrow(11.25, 7.9, 12.35, 8.5, "KPIs / charts", loff=(0.35, 0.05))
arrow(10.4, 6.72, 10.4, 5.78, "trend metrics", loff=(0.7, 0))
arrow(11.2, 7.2, 12.3, 5.6, "metrics", loff=(0.75, 0.35), rad=-0.1)

# Forecasting
arrow(9.5, 4.9, 8.4, 5.3, "historical series", loff=(-0.35, 0.25), rad=-0.05)
arrow(11.25, 4.9, 12.05, 4.9, "forecast result", loff=(0, 0.22))
arrow(12.1, 4.55, 11.3, 4.55, None)
arrow(11.25, 4.55, 12.8, 3.35, "store forecast", loff=(0.55, 0.1), rad=0.1)
arrow(10.4, 4.05, 12.15, 4.55, None)
arrow(10.4, 4.02, 12.2, 3.55, None)
# Forecasting -> Decision Support
arrow(11.25, 4.55, 12.1, 4.55, "prediction", loff=(0, -0.22))

# Decision Support
arrow(13.0, 3.95, 13.0, 3.3, "save insights", loff=(0.7, 0))
arrow(12.4, 3.2, 12.4, 4.0, "load insights", loff=(-0.7, 0))
arrow(13.7, 5.4, 14.5, 6.5, "insights &\nrecommendations", loff=(0.55, -0.15))
arrow(13.9, 6.55, 13.5, 5.7, "review request", loff=(0.55, 0.2))

# Users receive analytics summary too
arrow(11.2, 7.6, 13.45, 7.35, "analytics summary", loff=(0.2, 0.28), rad=-0.08)

# Administration
arrow(2.6, 3.55, 3.5, 3.55, "user / role changes", loff=(0, 0.24))
arrow(3.5, 3.2, 2.6, 3.2, "account status", loff=(0, -0.24))
arrow(5.25, 3.6, 6.3, 4.2, "maintain refs", loff=(0.35, 0.2))
arrow(5.2, 3.0, 6.25, 2.25, "config events", loff=(0.55, 0.15))

# Audit dashed lines from major processes to D4
for px, py in [(4.4, 6.78), (7.3, 6.72), (10.4, 6.72), (10.4, 4.02), (13.0, 3.95), (4.4, 2.55)]:
    arrow(px, py, 7.0, 2.35, None, color="#777", ls=(0, (3, 2)), rad=0.08)

ax.text(
    4.5,
    1.55,
    "dashed arrows = audit events written to D4 across modules",
    ha="center",
    va="center",
    fontsize=7,
    color="#666",
    style="italic",
)

# Legend bar
ax.add_patch(FancyBboxPatch((0.45, 0.25), 15.1, 1.05, boxstyle="square,pad=0.02", lw=1.0, edgecolor="#888", facecolor="#f8f8f8"))
ax.text(8.0, 1.05, "Legend", ha="center", fontsize=8.5, fontweight="bold", color="#222")

ax.add_patch(Rectangle((0.8, 0.42), 0.7, 0.35, facecolor="#eeeeee", edgecolor="#111", lw=1.1))
ax.text(2.35, 0.6, "External Entity", ha="left", va="center", fontsize=7.5)

ax.add_patch(Circle((5.1, 0.6), 0.2, facecolor="white", edgecolor="#111", lw=1.1))
ax.text(5.5, 0.6, "Process", ha="left", va="center", fontsize=7.5)

ax.plot([7.15, 8.15], [0.78, 0.78], color="#111", lw=1.3)
ax.plot([7.15, 8.15], [0.42, 0.42], color="#111", lw=1.3)
ax.plot([8.15, 8.15], [0.42, 0.78], color="#111", lw=1.3)
ax.plot([7.3, 7.3], [0.42, 0.78], color="#111", lw=1.3)
ax.text(8.4, 0.6, "Data Store", ha="left", va="center", fontsize=7.5)

ax.annotate("", xy=(11.5, 0.6), xytext=(10.5, 0.6), arrowprops=dict(arrowstyle="-|>", color="#333", lw=1.15))
ax.text(11.7, 0.6, "Data Flow", ha="left", va="center", fontsize=7.5)

ax.plot([13.55, 14.3], [0.6, 0.6], color="#777", lw=1.05, linestyle=(0, (3, 2)))
ax.text(14.45, 0.6, "Audit trail", ha="left", va="center", fontsize=7.5, color="#555")

ax.text(
    8.0,
    -0.05,
    "Figure 4. Data Flow Diagram (Level 0)",
    ha="center",
    va="center",
    fontsize=11.5,
    fontstyle="italic",
    color="#111",
)

plt.tight_layout(pad=0.3)
fig.savefig(OUT, bbox_inches="tight", facecolor="white", edgecolor="none")
plt.close()
print("Saved:", OUT)
