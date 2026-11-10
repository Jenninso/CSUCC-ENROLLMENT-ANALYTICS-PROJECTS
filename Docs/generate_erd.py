"""Figure 6 — Entity Relationship Diagram (clean, paper-ready)."""

import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch, FancyArrowPatch, Circle
from matplotlib.lines import Line2D

OUT = r"C:\xampp new\htdocs\CAPSTONE\Docs\Figure6_ERD.png"

fig, ax = plt.subplots(figsize=(18, 12.5), dpi=220)
ax.set_xlim(0, 18)
ax.set_ylim(0, 12.5)
ax.axis("off")
fig.patch.set_facecolor("white")
ax.set_facecolor("white")

ax.text(
    9,
    12.15,
    "Entity Relationship Diagram",
    ha="center",
    va="center",
    fontsize=14,
    fontweight="bold",
    color="#111",
)
ax.text(
    9,
    11.75,
    "Enrollment Analytics & Decision Support System (PostgreSQL)",
    ha="center",
    va="center",
    fontsize=9.5,
    color="#333",
)


def entity(x, y, w, h, title, fields, face="#fffef8"):
    """Draw a classic ER entity box with header + attributes."""
    ax.add_patch(
        FancyBboxPatch(
            (x, y),
            w,
            h,
            boxstyle="square,pad=0.01",
            lw=1.35,
            edgecolor="#111",
            facecolor=face,
            zorder=3,
        )
    )
    # header bar
    ax.add_patch(
        FancyBboxPatch(
            (x, y + h - 0.38),
            w,
            0.38,
            boxstyle="square,pad=0.01",
            lw=1.35,
            edgecolor="#111",
            facecolor="#e9eef5",
            zorder=4,
        )
    )
    ax.text(
        x + w / 2,
        y + h - 0.19,
        title,
        ha="center",
        va="center",
        fontsize=8,
        fontweight="bold",
        color="#111",
        zorder=5,
    )
    # fields
    line_h = (h - 0.42) / max(len(fields), 1)
    for i, f in enumerate(fields):
        yy = y + h - 0.42 - (i + 0.55) * line_h
        weight = "bold" if f.startswith("PK") or f.startswith("FK") else "normal"
        ax.text(
            x + 0.1,
            yy,
            f,
            ha="left",
            va="center",
            fontsize=6.6,
            fontweight=weight,
            color="#111",
            zorder=5,
            family="DejaVu Sans Mono",
        )
    return {
        "x": x,
        "y": y,
        "w": w,
        "h": h,
        "cx": x + w / 2,
        "cy": y + h / 2,
        "top": y + h,
        "bottom": y,
        "left": x,
        "right": x + w,
    }


def rel(a, b, label, card_a="1", card_b="M", rad=0.0, side=("right", "left")):
    """Draw relationship line with cardinality."""
    if side[0] == "right":
        x1, y1 = a["right"], a["cy"]
    elif side[0] == "left":
        x1, y1 = a["left"], a["cy"]
    elif side[0] == "bottom":
        x1, y1 = a["cx"], a["bottom"]
    else:
        x1, y1 = a["cx"], a["top"]

    if side[1] == "left":
        x2, y2 = b["left"], b["cy"]
    elif side[1] == "right":
        x2, y2 = b["right"], b["cy"]
    elif side[1] == "top":
        x2, y2 = b["cx"], b["top"]
    else:
        x2, y2 = b["cx"], b["bottom"]

    ax.annotate(
        "",
        xy=(x2, y2),
        xytext=(x1, y1),
        arrowprops=dict(
            arrowstyle="-",
            color="#333",
            lw=1.1,
            connectionstyle=f"arc3,rad={rad}",
        ),
        zorder=1,
    )
    # cardinality near ends
    ax.text(
        x1 + (0.18 if side[0] == "right" else -0.18 if side[0] == "left" else 0),
        y1 + (0.18 if side[0] in ("top", "bottom") else 0.16),
        card_a,
        fontsize=7,
        color="#111",
        ha="center",
        va="center",
        fontweight="bold",
        zorder=6,
        bbox=dict(boxstyle="round,pad=0.05", fc="white", ec="none", alpha=0.9),
    )
    ax.text(
        x2 + (-0.18 if side[1] == "left" else 0.18 if side[1] == "right" else 0),
        y2 + (0.18 if side[1] in ("top", "bottom") else 0.16),
        card_b,
        fontsize=7,
        color="#111",
        ha="center",
        va="center",
        fontweight="bold",
        zorder=6,
        bbox=dict(boxstyle="round,pad=0.05", fc="white", ec="none", alpha=0.9),
    )
    if label:
        ax.text(
            (x1 + x2) / 2,
            (y1 + y2) / 2 + 0.22,
            label,
            fontsize=6.4,
            color="#444",
            ha="center",
            va="center",
            style="italic",
            zorder=6,
            bbox=dict(boxstyle="round,pad=0.08", fc="white", ec="none", alpha=0.95),
        )


# ===================== USER / SECURITY (left) =====================
roles = entity(
    0.3,
    9.3,
    2.5,
    1.7,
    "ROLES",
    ["PK  id", "    role_name", "    description", "    timestamps"],
    face="#f7fbff",
)
users = entity(
    0.3,
    6.7,
    2.5,
    2.3,
    "USERS",
    [
        "PK  id",
        "FK  role_id",
        "    employee_id",
        "    name / email",
        "    username",
        "    password",
        "    status",
        "    timestamps",
    ],
    face="#f7fbff",
)
perms = entity(
    0.3,
    4.55,
    2.5,
    1.8,
    "PERMISSIONS",
    ["PK  id", "    key", "    name", "    module", "    timestamps"],
    face="#f7fbff",
)
role_perm = entity(
    3.1,
    5.35,
    2.4,
    1.4,
    "ROLE_PERMISSION",
    ["PK  id", "FK  role_id", "FK  permission_id"],
    face="#f0f4f8",
)
user_perm = entity(
    3.1,
    3.7,
    2.4,
    1.4,
    "USER_PERMISSION",
    ["PK  id", "FK  user_id", "FK  permission_id"],
    face="#f0f4f8",
)
audit = entity(
    0.3,
    2.35,
    2.5,
    1.85,
    "AUDIT_LOGS",
    ["PK  id", "FK  user_id", "    action", "    module", "    ip_address", "    details"],
    face="#f7fbff",
)
login = entity(
    3.1,
    1.85,
    2.4,
    1.7,
    "LOGIN_LOGS",
    ["PK  id", "FK  user_id", "    full_name", "    role_name", "    time_in / out", "    ip_address"],
    face="#f7fbff",
)
reg = entity(
    3.1,
    8.55,
    2.4,
    2.0,
    "REGISTRATION_REQUESTS",
    ["PK  id", "    employee_id", "    email", "    status", "FK  reviewed_by", "    reviewed_at"],
    face="#f7fbff",
)

# ===================== ACADEMIC STRUCTURE (center) =====================
ay = entity(
    6.1,
    9.55,
    2.7,
    1.7,
    "ACADEMIC_YEARS",
    ["PK  id", "    academic_year", "    start_date", "    end_date", "    status"],
    face="#fff8ef",
)
sem = entity(
    9.2,
    9.55,
    2.6,
    1.55,
    "SEMESTERS",
    ["PK  id", "FK  academic_year_id", "    semester_name", "    timestamps"],
    face="#fff8ef",
)
col = entity(
    6.1,
    7.2,
    2.7,
    1.55,
    "COLLEGES",
    ["PK  id", "    college_name", "    college_code", "    timestamps"],
    face="#fff8ef",
)
dept = entity(
    9.2,
    7.2,
    2.6,
    1.55,
    "DEPARTMENTS",
    ["PK  id", "FK  college_id", "    department_name", "    department_code"],
    face="#fff8ef",
)
prog = entity(
    12.2,
    7.15,
    2.7,
    1.8,
    "PROGRAMS",
    ["PK  id", "FK  department_id", "    program_name", "    program_code", "    degree_level", "    status"],
    face="#fff8ef",
)

# ===================== RECORDS / ANALYTICS (right-bottom) =====================
stu = entity(
    6.0,
    3.55,
    3.3,
    2.8,
    "STUDENT_RECORDS",
    [
        "PK  id",
        "    student_id",
        "FK  program_id",
        "FK  academic_year_id",
        "FK  semester_id",
        "    year_level / section",
        "    gender / age",
        "    gpa / grades",
        "    enrolled_status",
        "    risk_level",
    ],
    face="#f4fff4",
)
enr = entity(
    9.7,
    3.55,
    3.4,
    2.8,
    "ENROLLMENT_RECORDS",
    [
        "PK  id",
        "FK  academic_year_id",
        "FK  semester_id",
        "FK  college_id",
        "FK  department_id",
        "FK  program_id",
        "    year_level",
        "    enrollment_type",
        "    enrollment_count",
    ],
    face="#f4fff4",
)
fc = entity(
    13.5,
    4.85,
    2.7,
    2.1,
    "FORECASTS",
    [
        "PK  id",
        "FK  program_id",
        "FK  college_id",
        "    forecast_period",
        "    predicted_enrollment",
        "    lower / upper bound",
        "    model_used / MAE",
    ],
    face="#f4fff4",
)
ai = entity(
    13.5,
    2.45,
    2.7,
    2.05,
    "AI_INSIGHTS",
    [
        "PK  id",
        "    insight_type",
        "    title / description",
        "    severity",
        "    supporting_metric",
        "    recommendation",
    ],
    face="#f4fff4",
)
alerts = entity(
    10.0,
    1.0,
    3.0,
    2.1,
    "ALERTS",
    [
        "PK  id",
        "FK  program_id",
        "    title / metric",
        "    current / expected",
        "    severity",
        "    recommended_action",
        "    is_read",
    ],
    face="#f4fff4",
)

# ===================== Relationships =====================
rel(roles, users, "has", "1", "M", side=("bottom", "top"))
rel(roles, role_perm, "grants", "1", "M", side=("bottom", "left"), rad=0.1)
rel(perms, role_perm, "assigned", "1", "M", side=("right", "left"), rad=-0.05)
rel(users, user_perm, "has", "1", "M", side=("right", "left"), rad=0.05)
rel(perms, user_perm, "assigned", "1", "M", side=("right", "left"), rad=0.12)
rel(users, audit, "performs", "1", "M", side=("bottom", "top"))
rel(users, login, "logs", "1", "M", side=("bottom", "left"), rad=0.15)
rel(users, reg, "reviews", "1", "M", side=("top", "left"), rad=-0.1)

rel(ay, sem, "contains", "1", "M", side=("right", "left"))
rel(col, dept, "has", "1", "M", side=("right", "left"))
rel(dept, prog, "offers", "1", "M", side=("right", "left"))

rel(ay, stu, "for period", "1", "M", side=("bottom", "top"), rad=0.05)
rel(sem, stu, "", "1", "M", side=("bottom", "top"), rad=-0.08)
rel(prog, stu, "enrolled in", "1", "M", side=("bottom", "right"), rad=0.12)

rel(ay, enr, "", "1", "M", side=("bottom", "top"), rad=-0.05)
rel(sem, enr, "aggregates", "1", "M", side=("bottom", "top"), rad=0.05)
rel(col, enr, "", "1", "M", side=("bottom", "left"), rad=0.2)
rel(dept, enr, "", "1", "M", side=("bottom", "top"), rad=-0.15)
rel(prog, enr, "summarized in", "1", "M", side=("bottom", "top"), rad=0.08)

rel(prog, fc, "forecasted", "1", "M", side=("right", "left"), rad=-0.05)
rel(col, fc, "", "1", "M", side=("right", "top"), rad=-0.2)
rel(prog, alerts, "triggers", "1", "M", side=("bottom", "top"), rad=0.1)

# Legend / note
ax.add_patch(
    FancyBboxPatch(
        (0.3, 0.25),
        9.3,
        1.4,
        boxstyle="square,pad=0.02",
        lw=1.0,
        edgecolor="#888",
        facecolor="#f8f8f8",
        zorder=2,
    )
)
ax.text(0.5, 1.4, "Legend", fontsize=8.5, fontweight="bold", color="#222")
ax.text(
    0.5,
    0.95,
    "PK = Primary Key     FK = Foreign Key     1 = One     M = Many",
    fontsize=7.5,
    color="#222",
)
ax.text(
    0.5,
    0.55,
    "College → Department → Program. Enrollment & student records reference academic year, semester, and program.",
    fontsize=7.2,
    color="#333",
)
ax.text(
    0.5,
    0.25,
    "AI_INSIGHTS is generated from analytics outputs (logical link; may not require a hard FK).",
    fontsize=7.0,
    color="#555",
    style="italic",
)

ax.text(
    14.8,
    0.55,
    "Figure 6. Entity Relationship Diagram",
    ha="center",
    va="center",
    fontsize=11,
    fontstyle="italic",
    color="#111",
)

plt.tight_layout(pad=0.2)
fig.savefig(OUT, bbox_inches="tight", facecolor="white", edgecolor="none")
# also save v2 alias
fig.savefig(
    r"C:\xampp new\htdocs\CAPSTONE\Docs\Figure6_Entity_Relationship_Diagram.png",
    bbox_inches="tight",
    facecolor="white",
    edgecolor="none",
)
plt.close()
print("Saved:", OUT)
