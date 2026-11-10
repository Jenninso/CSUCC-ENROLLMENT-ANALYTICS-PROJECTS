"""Clean package-style UML Use Case Diagram (Figure 5)."""

import matplotlib.pyplot as plt
from matplotlib.patches import Ellipse, FancyBboxPatch, Circle, Polygon, Rectangle

OUT = r"C:\xampp new\htdocs\CAPSTONE\Docs\Figure5_Use_Case_Diagram.png"

fig, ax = plt.subplots(figsize=(15, 10.5), dpi=230)
ax.set_xlim(0, 15)
ax.set_ylim(0, 10.5)
ax.axis("off")
fig.patch.set_facecolor("white")
ax.set_facecolor("white")

# Outer system boundary
system = FancyBboxPatch(
    (3.3, 1.35),
    8.5,
    8.35,
    boxstyle="round,pad=0.02,rounding_size=0.1",
    linewidth=1.8,
    edgecolor="#222",
    facecolor="#fff",
)
ax.add_patch(system)
ax.text(
    7.55,
    9.45,
    "Enrollment Analytics & Decision Support System",
    ha="center",
    va="center",
    fontsize=12,
    fontweight="bold",
    color="#111",
)


def stick_actor(x, y, label):
    ax.add_patch(Circle((x, y + 0.42), 0.145, fill=False, lw=1.55, edgecolor="#111", zorder=5))
    ax.plot([x, x], [y + 0.275, y - 0.04], color="#111", lw=1.55, zorder=5)
    ax.plot([x - 0.25, x + 0.25], [y + 0.15, y + 0.15], color="#111", lw=1.55, zorder=5)
    ax.plot([x, x - 0.2], [y - 0.04, y - 0.38], color="#111", lw=1.55, zorder=5)
    ax.plot([x, x + 0.2], [y - 0.04, y - 0.38], color="#111", lw=1.55, zorder=5)
    ax.text(x, y - 0.68, label, ha="center", va="top", fontsize=8.5, color="#111", linespacing=1.15)


def package(x, y, w, h, title):
    box = FancyBboxPatch(
        (x, y),
        w,
        h,
        boxstyle="square,pad=0.01",
        linewidth=1.2,
        edgecolor="#444",
        facecolor="#fafafa",
        zorder=1,
    )
    ax.add_patch(box)
    # UML package tab
    tab = Rectangle((x, y + h), w * 0.38, 0.28, linewidth=1.2, edgecolor="#444", facecolor="#f0f0f0", zorder=2)
    ax.add_patch(tab)
    ax.text(x + w * 0.19, y + h + 0.14, title, ha="center", va="center", fontsize=7.5, color="#333", zorder=3)
    return x, y, w, h


def usecase(x, y, text, w=3.1, h=0.52):
    ax.add_patch(Ellipse((x, y), w, h, facecolor="white", edgecolor="#222", lw=1.15, zorder=3))
    ax.text(x, y, text, ha="center", va="center", fontsize=7.8, color="#111", zorder=4)
    return x, y, w, h


def assoc(x1, y1, x2, y2):
    ax.plot([x1, x2], [y1, y2], color="#333", lw=1.15, zorder=0, solid_capstyle="round")


# Actors
stick_actor(1.45, 7.9, "Super\nAdministrator")
stick_actor(1.45, 5.5, "Administrator")
stick_actor(1.45, 2.9, "Enrollment\nOfficer / Staff")
stick_actor(13.55, 5.5, "Decision Maker\n/ Viewer")

# Generalization Super Admin ──▷ Administrator  (inherits)
ax.plot([1.45, 1.45], [7.05, 6.15], color="#111", lw=1.3, zorder=2)
ax.add_patch(
    Polygon(
        [[1.45, 6.0], [1.30, 6.28], [1.60, 6.28]],
        closed=True,
        facecolor="white",
        edgecolor="#111",
        lw=1.3,
        zorder=3,
    )
)
ax.text(0.72, 6.55, "inherits", fontsize=7, color="#666", rotation=90, va="center")

# Packages
p1 = package(3.7, 7.15, 3.5, 1.85, "Administration")
p2 = package(7.6, 7.15, 3.8, 1.85, "Data Management")
p3 = package(3.7, 4.15, 7.7, 2.35, "Analytics & Decision Support")
p4 = package(3.7, 2.0, 7.7, 1.55, "Reporting & Access")

# Use cases inside packages
usecase(5.45, 8.45, "Manage Users", 2.7, 0.48)
usecase(5.45, 7.7, "Configure Settings", 2.7, 0.48)

usecase(9.5, 8.45, "Manage Academic Data", 3.0, 0.48)
usecase(9.5, 7.7, "Manage Student / Enrollment\nRecords  ·  Import Dataset", 3.35, 0.62)

usecase(5.45, 5.85, "View Dashboard", 2.7, 0.48)
usecase(8.4, 5.85, "View Analytics", 2.5, 0.48)
usecase(11.1, 5.85, "Run Forecast", 2.4, 0.48)
usecase(6.6, 4.85, "Generate AI Insights", 2.9, 0.48)
usecase(10.2, 4.85, "Manage Alerts", 2.5, 0.48)

usecase(6.4, 2.85, "Generate Reports", 2.7, 0.48)
usecase(10.0, 2.85, "Authenticate / Login", 2.9, 0.48)

# Associations to packages (few clean lines)
# Administrator -> all four packages
assoc(1.85, 5.65, 3.7, 8.05)   # Administration
assoc(1.85, 5.65, 3.7, 5.35)   # Analytics
assoc(1.85, 5.65, 3.7, 2.75)   # Reporting
assoc(1.85, 5.65, 7.6, 8.05)   # Data Management

# Super Admin -> Administration (extra emphasis) + inherits rest
assoc(1.85, 8.05, 3.7, 8.35)

# Enrollment Officer -> Data + Analytics (view) + Reporting
assoc(1.85, 3.05, 7.6, 7.5)
assoc(1.85, 3.05, 3.7, 4.8)
assoc(1.85, 3.05, 3.7, 2.55)

# Decision Maker -> Analytics + Reporting
assoc(13.15, 5.65, 11.4, 5.35)
assoc(13.15, 5.65, 11.4, 2.75)

# Small legend / reading guide
legend = FancyBboxPatch(
    (3.5, 1.45),
    8.1,
    0.42,
    boxstyle="square,pad=0.01",
    linewidth=0.9,
    edgecolor="#999",
    facecolor="#f7f7f7",
    zorder=2,
)
ax.add_patch(legend)
ax.text(
    7.55,
    1.66,
    "Each line links an actor to a package of related use cases.  "
    "Super Administrator inherits all Administrator access.",
    ha="center",
    va="center",
    fontsize=7.6,
    color="#333",
)

ax.text(
    7.55,
    0.7,
    "Figure 5. UML Use Case Diagram",
    ha="center",
    va="center",
    fontsize=11.5,
    fontstyle="italic",
    color="#111",
)
ax.text(
    7.55,
    0.28,
    "Packages group related features so the diagram stays readable.",
    ha="center",
    va="center",
    fontsize=7.5,
    color="#666",
)

plt.tight_layout(pad=0.2)
fig.savefig(OUT, bbox_inches="tight", facecolor="white", edgecolor="none")
plt.close()
print("Saved:", OUT)
