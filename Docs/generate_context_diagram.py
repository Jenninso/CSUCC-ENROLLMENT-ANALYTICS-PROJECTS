"""Clean Context Diagram (Figure 3) for the capstone paper."""

import matplotlib.pyplot as plt
from matplotlib.patches import Circle, FancyBboxPatch, Rectangle

OUT = r"C:\xampp new\htdocs\CAPSTONE\Docs\Figure3_Context_Diagram.png"

fig, ax = plt.subplots(figsize=(14, 10), dpi=240)
ax.set_xlim(0, 14)
ax.set_ylim(0, 10)
ax.axis("off")
fig.patch.set_facecolor("white")
ax.set_facecolor("white")

ax.text(
    7.0,
    9.55,
    "Context Diagram",
    ha="center",
    va="center",
    fontsize=14,
    fontweight="bold",
    color="#111",
)
ax.text(
    7.0,
    9.15,
    "Visualization of Enrollment Dynamics Utilizing Data Analytics\nwith AI-Driven Decision Support",
    ha="center",
    va="center",
    fontsize=9,
    color="#333",
    linespacing=1.25,
)


def entity(x, y, w, h, text):
    ax.add_patch(
        FancyBboxPatch(
            (x - w / 2, y - h / 2),
            w,
            h,
            boxstyle="square,pad=0.01",
            lw=1.6,
            edgecolor="#111",
            facecolor="#ececec",
            zorder=3,
        )
    )
    ax.text(x, y, text, ha="center", va="center", fontsize=8.8, color="#111", zorder=4, linespacing=1.15)
    return x, y, w, h


def arrow(x1, y1, x2, y2, label, loff=(0, 0.22), rad=0.0):
    ax.annotate(
        "",
        xy=(x2, y2),
        xytext=(x1, y1),
        arrowprops=dict(
            arrowstyle="-|>",
            color="#222",
            lw=1.25,
            connectionstyle=f"arc3,rad={rad}",
            shrinkA=1,
            shrinkB=1,
        ),
        zorder=2,
    )
    ax.text(
        (x1 + x2) / 2 + loff[0],
        (y1 + y2) / 2 + loff[1],
        label,
        ha="center",
        va="center",
        fontsize=7.2,
        color="#222",
        zorder=5,
        bbox=dict(boxstyle="round,pad=0.12", fc="white", ec="none", alpha=0.96),
    )


# ===================== Center process (the whole system) =====================
cx, cy, cr = 7.0, 5.0, 1.75
ax.add_patch(Circle((cx, cy), cr, facecolor="white", edgecolor="#111", lw=2.0, zorder=3))
ax.text(cx, cy + 0.45, "0.0", ha="center", va="center", fontsize=11, fontweight="bold", zorder=4)
ax.text(
    cx,
    cy - 0.2,
    "Enrollment Analytics\n& Decision Support\nSystem",
    ha="center",
    va="center",
    fontsize=9,
    zorder=4,
    linespacing=1.2,
)

# ===================== External entities =====================
# Left: Authorized Users
entity(1.7, 5.0, 2.4, 1.2, "Authorized\nUsers")

# Top: Email / OTP Service (optional)
entity(7.0, 7.85, 2.7, 0.95, "Email / OTP\nService")

# Top-right-ish note that it's optional - use lighter style for optional services
entity(12.3, 7.4, 2.5, 0.95, "LLM Provider\n(Assistant / AI)")

# Right: Enrollment Database
entity(12.3, 5.0, 2.5, 1.15, "Enrollment\nDatabase")

# Bottom: could show institutional admin separately, but paper focuses on users + DB + optional services
# Add a small "optional" tags
ax.text(7.0, 8.5, "(optional)", ha="center", va="center", fontsize=7, color="#666", style="italic")
ax.text(12.3, 8.05, "(optional)", ha="center", va="center", fontsize=7, color="#666", style="italic")

# ===================== Data flows =====================
# Users -> System
arrow(2.9, 5.35, 5.3, 5.55, "credentials, records,\nanalysis requests", loff=(0.05, 0.42), rad=-0.05)
# System -> Users
arrow(5.3, 4.45, 2.9, 4.65, "dashboards, forecasts,\ninsights, reports", loff=(0.05, -0.42), rad=-0.05)

# System <-> Database
arrow(8.7, 5.35, 11.05, 5.35, "read / write\nenrollment data", loff=(0, 0.32))
arrow(11.05, 4.65, 8.7, 4.65, "student & enrollment\nrecords", loff=(0, -0.32))

# System <-> Email/OTP
arrow(7.0, 6.75, 7.0, 7.35, "OTP / notification\nrequest", loff=(1.15, 0))
arrow(6.35, 7.35, 6.35, 6.75, "delivery status\n/ OTP code", loff=(-1.1, 0))

# System <-> LLM
arrow(8.45, 6.15, 11.1, 7.0, "prompt +\nanalytics context", loff=(0.55, 0.35), rad=-0.1)
arrow(11.05, 6.85, 8.5, 5.85, "AI reply /\ninsight text", loff=(0.75, -0.45), rad=-0.08)

# ===================== Legend =====================
ax.add_patch(
    FancyBboxPatch(
        (1.0, 0.55),
        12.0,
        1.35,
        boxstyle="square,pad=0.02",
        lw=1.0,
        edgecolor="#888",
        facecolor="#f8f8f8",
        zorder=1,
    )
)
ax.text(7.0, 1.65, "Legend", ha="center", fontsize=9, fontweight="bold", color="#222")

ax.add_patch(Rectangle((1.4, 0.8), 0.85, 0.45, facecolor="#ececec", edgecolor="#111", lw=1.2))
ax.text(3.3, 1.02, "External Entity", ha="left", va="center", fontsize=8)

ax.add_patch(Circle((6.0, 1.02), 0.28, facecolor="white", edgecolor="#111", lw=1.3))
ax.text(6.5, 1.02, "System (single process)", ha="left", va="center", fontsize=8)

ax.annotate("", xy=(11.4, 1.02), xytext=(10.2, 1.02), arrowprops=dict(arrowstyle="-|>", color="#222", lw=1.2))
ax.text(11.6, 1.02, "Data Flow", ha="left", va="center", fontsize=8)

ax.text(
    7.0,
    0.2,
    "Figure 3. Context Diagram",
    ha="center",
    va="center",
    fontsize=12,
    fontstyle="italic",
    color="#111",
)

plt.tight_layout(pad=0.25)
fig.savefig(OUT, bbox_inches="tight", facecolor="white", edgecolor="none")
plt.close()
print("Saved:", OUT)
