# -*- coding: utf-8 -*-
"""Generate the complete Capstone Project manuscript as a Word document."""

from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_LINE_SPACING
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, Cm, RGBColor
from docx.enum.style import WD_STYLE_TYPE

OUT = r"C:\xampp new\htdocs\CAPSTONE\COMPLETE CAPSTONE PROJECT PAPER.docx"

doc = Document()

# Page setup (approx A4 / letter academic feel)
section = doc.sections[0]
section.page_width = Inches(8.5)
section.page_height = Inches(11)
section.left_margin = Inches(1.5)
section.right_margin = Inches(1)
section.top_margin = Inches(1)
section.bottom_margin = Inches(1)

styles = doc.styles

# Normal body
normal = styles["Normal"]
normal.font.name = "Times New Roman"
normal.font.size = Pt(12)
normal._element.rPr.rFonts.set(qn("w:eastAsia"), "Times New Roman")
pf = normal.paragraph_format
pf.line_spacing_rule = WD_LINE_SPACING.DOUBLE
pf.space_after = Pt(0)
pf.space_before = Pt(0)
pf.first_line_indent = Inches(0.5)

# Heading styles
for level, size in ((1, 14), (2, 12), (3, 12)):
    style = styles[f"Heading {level}"]
    style.font.name = "Times New Roman"
    style.font.size = Pt(size)
    style.font.bold = True
    style.font.color.rgb = RGBColor(0, 0, 0)
    style._element.rPr.rFonts.set(qn("w:eastAsia"), "Times New Roman")
    style.paragraph_format.line_spacing_rule = WD_LINE_SPACING.DOUBLE
    style.paragraph_format.first_line_indent = Inches(0)
    style.paragraph_format.space_before = Pt(12)
    style.paragraph_format.space_after = Pt(6)
    if level == 1:
        style.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER


def add_centered(text, bold=True, size=12, space_before=0, space_after=0, italic=False):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.first_line_indent = Inches(0)
    p.paragraph_format.space_before = Pt(space_before)
    p.paragraph_format.space_after = Pt(space_after)
    p.paragraph_format.line_spacing_rule = WD_LINE_SPACING.DOUBLE
    run = p.add_run(text)
    run.bold = bold
    run.italic = italic
    run.font.name = "Times New Roman"
    run.font.size = Pt(size)
    return p


def add_body(text, indent=True):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    p.paragraph_format.first_line_indent = Inches(0.5) if indent else Inches(0)
    p.paragraph_format.line_spacing_rule = WD_LINE_SPACING.DOUBLE
    run = p.add_run(text)
    run.font.name = "Times New Roman"
    run.font.size = Pt(12)
    return p


def add_heading1(text):
    p = doc.add_heading(text, level=1)
    for run in p.runs:
        run.font.color.rgb = RGBColor(0, 0, 0)
        run.font.name = "Times New Roman"
    return p


def add_heading2(text):
    p = doc.add_heading(text, level=2)
    for run in p.runs:
        run.font.color.rgb = RGBColor(0, 0, 0)
        run.font.name = "Times New Roman"
    return p


def add_heading3(text):
    p = doc.add_heading(text, level=3)
    for run in p.runs:
        run.font.color.rgb = RGBColor(0, 0, 0)
        run.font.name = "Times New Roman"
    return p


def add_bullet(text):
    p = doc.add_paragraph(style="List Bullet")
    p.paragraph_format.first_line_indent = Inches(0)
    p.paragraph_format.line_spacing_rule = WD_LINE_SPACING.DOUBLE
    p.clear()
    run = p.add_run(text)
    run.font.name = "Times New Roman"
    run.font.size = Pt(12)
    return p


def page_break():
    doc.add_page_break()


# =============================================================================
# TITLE PAGE
# =============================================================================
for _ in range(2):
    add_centered("", bold=False)

add_centered(
    "VISUALIZATION OF ENROLLMENT DYNAMICS UTILIZING DATA ANALYTICS\nWITH AI-DRIVEN DECISION SUPPORT",
    bold=True,
    size=14,
)
add_centered("", bold=False)
add_centered("A Capstone Project", bold=False, italic=True)
add_centered("", bold=False)
add_centered("Presented to the Faculty of the", bold=False)
add_centered("College of Engineering and Information Technology", bold=False)
add_centered("Caraga State University Cabadbaran Campus", bold=False)
add_centered("Cabadbaran City, Agusan del Norte", bold=False)
add_centered("", bold=False)
add_centered("In Partial Fulfillment of the Requirements for the Degree", bold=False)
add_centered("Bachelor of Science in Information Technology", bold=False)
add_centered("", bold=False)
add_centered("by", bold=False)
add_centered("", bold=False)
add_centered("[PROPONENT FULL NAME]", bold=True)
add_centered("[Co-Proponent Full Name, if applicable]", bold=False)
add_centered("", bold=False)
add_centered("November 2026", bold=False)

page_break()

# =============================================================================
# APPROVAL / ABSTRACT placeholders
# =============================================================================
add_heading1("ABSTRACT")
add_body(
    "Enrollment planning inside a campus unit is hard when the numbers live in "
    "spreadsheets, printed summaries, or separate files that do not talk to each other. "
    "At Caraga State University Cabadbaran Campus (CSUCC), administrators and faculty "
    "handling the College of Engineering and Information Technology (CEIT) still need a "
    "clearer way to see how BSIT, BSCpE, and BSEE enrollment moves from one term to the "
    "next. This capstone project developed a web-based Enrollment Analytics and "
    "Decision Support System titled Visualization of Enrollment Dynamics Utilizing Data "
    "Analytics with AI-Driven Decision Support. The system gathers historical and "
    "current enrollment records, cleans and validates them, and turns them into "
    "dashboards, trend charts, retention views, and program comparisons. It also runs "
    "simple forecasting models—Naive, Moving Average, Linear Regression, and "
    "Exponential Smoothing—then picks the best one using Mean Absolute Error (MAE). "
    "On top of the numbers, an AI-assisted decision support layer explains what the "
    "data shows, flags unusual changes, and suggests practical next steps without "
    "treating guesses as facts. The system was built with Laravel (PHP), PostgreSQL, "
    "Blade templates, Chart.js, and role-based access control with OTP-backed login. "
    "Testing with sample CEIT enrollment data showed that authorized users could "
    "import records, review data quality, explore filters, generate forecasts, and "
    "read AI insights in one place. The project does not replace registrar systems "
    "or institutional research offices; it supports CEIT decision-makers who need "
    "faster, clearer enrollment intelligence for planning."
)
add_body(
    "Keywords: enrollment analytics, decision support system, forecasting, data "
    "visualization, artificial intelligence, CEIT, CSUCC",
    indent=False,
)

page_break()

add_heading1("ACKNOWLEDGMENT")
add_body(
    "The proponent would like to thank the faculty and advisers of the College of "
    "Engineering and Information Technology of Caraga State University Cabadbaran "
    "Campus for the guidance given throughout the planning, development, and "
    "documentation of this capstone project. Appreciation is also extended to the "
    "panel members for their comments during proposal and oral defenses, and to "
    "classmates and friends who helped test the system and check the written paper. "
    "Above all, gratitude goes to family for the patience and support while this "
    "work was being finished."
)

page_break()

add_heading1("TABLE OF CONTENTS")
toc_items = [
    "LIST OF TABLES",
    "LIST OF FIGURES",
    "CHAPTER I – INTRODUCTION",
    "    1.1 Project Context",
    "    1.2 General Objectives and Specific Objectives",
    "    1.3 Scope and Limitation",
    "    1.4 Significance of the Study",
    "CHAPTER II – REVIEW OF RELATED LITERATURE",
    "    2.1 Enrollment Information Systems in Higher Education",
    "    2.2 Data Analytics and Visualization for Academic Planning",
    "    2.3 Predictive Analytics and Enrollment Forecasting",
    "    2.4 Decision Support Systems and AI in Education",
    "    2.5 Synthesis",
    "CHAPTER III – TECHNICAL BACKGROUND",
    "    3.1 Technicality of the Project",
    "    3.2 Input–Process–Output (IPO) Model",
    "    3.3 Details of the Technologies to be Used",
    "    3.4 How the Project Will Work",
    "CHAPTER IV – METHODOLOGY, RESULTS AND DISCUSSION",
    "    4.1 Planning",
    "    4.2 Requirements Specification",
    "    4.3 Analysis",
    "    4.4 System Design",
    "    4.5 Output and User Interface Design",
    "    4.6 Testing Results",
    "CHAPTER V – CONCLUSION AND RECOMMENDATION",
    "    5.1 Conclusion",
    "    5.2 Recommendation",
    "REFERENCES",
    "APPENDICES",
    "CURRICULUM VITAE",
]
for item in toc_items:
    p = doc.add_paragraph()
    p.paragraph_format.first_line_indent = Inches(0)
    p.paragraph_format.line_spacing_rule = WD_LINE_SPACING.DOUBLE
    run = p.add_run(item)
    run.font.name = "Times New Roman"
    run.font.size = Pt(12)

page_break()

add_heading1("LIST OF TABLES")
tables = [
    "Table 1. Hardware Requirements",
    "Table 2. Software Requirements",
    "Table 3. Functional Requirements Summary",
    "Table 4. User Roles and Access Privileges",
    "Table 5. Forecasting Models Compared by the System",
    "Table 6. Sample Testing Results by Module",
]
for t in tables:
    p = doc.add_paragraph()
    p.paragraph_format.first_line_indent = Inches(0)
    p.paragraph_format.line_spacing_rule = WD_LINE_SPACING.DOUBLE
    run = p.add_run(t)
    run.font.name = "Times New Roman"
    run.font.size = Pt(12)

page_break()

add_heading1("LIST OF FIGURES")
figures = [
    "Figure 1. Input–Process–Output (IPO) Model of the System",
    "Figure 2. System Architecture Diagram",
    "Figure 3. Context Diagram",
    "Figure 4. Data Flow Diagram (Level 0)",
    "Figure 5. Use Case Diagram",
    "Figure 6. Entity Relationship Diagram",
    "Figure 7. Sample Dashboard Interface",
    "Figure 8. Sample Forecasting Interface",
    "Figure 9. Sample AI Decision Support Interface",
]
for f in figures:
    p = doc.add_paragraph()
    p.paragraph_format.first_line_indent = Inches(0)
    p.paragraph_format.line_spacing_rule = WD_LINE_SPACING.DOUBLE
    run = p.add_run(f)
    run.font.name = "Times New Roman"
    run.font.size = Pt(12)

page_break()

# =============================================================================
# CHAPTER I
# =============================================================================
add_heading1("CHAPTER I")
add_centered("INTRODUCTION", bold=True, size=14)
add_centered("", bold=False)

add_heading2("1.1 Project Context")
add_body(
    "Every enrollment period, colleges look at the same basic questions. How many "
    "students came in? Which programs grew? Which ones slowed down? Are first-year "
    "numbers healthy enough for the next few years, or is retention quietly slipping? "
    "Those questions sound simple, but answering them well usually means opening "
    "several files, asking someone for an updated sheet, and rebuilding charts by hand. "
    "By the time the picture is ready, the conversation about what to do next may "
    "already have moved on."
)
add_body(
    "At Caraga State University Cabadbaran Campus, the College of Engineering and "
    "Information Technology (CEIT) manages programs such as Bachelor of Science in "
    "Information Technology (BSIT), Bachelor of Science in Computer Engineering "
    "(BSCpE), and Bachelor of Science in Electrical Engineering (BSEE). Enrollment "
    "data for these programs exists, but it is often handled in a transactional way—"
    "enough to enroll a student or print a list—rather than as a living dataset that "
    "supports planning. Program chairs, enrollment officers, and administrators can "
    "see totals, yet they still spend too much time stitching periods together when "
    "they want trends, comparisons, or an early warning about a sudden drop."
)
add_body(
    "That gap is what this project tries to close. Instead of stopping at raw "
    "records or static reports, the proposed system treats enrollment as something "
    "that should be visualized, measured, forecasted, and interpreted. The working "
    "title of the project is Visualization of Enrollment Dynamics Utilizing Data "
    "Analytics with AI-Driven Decision Support. In plain terms, the system helps "
    "authorized CEIT users move from “What is the count?” toward “What changed, "
    "what might happen next, and what should we look into?”"
)
add_body(
    "The idea is not to build another full student information system. Registrar "
    "operations, grades, and cashiering stay where they belong. This project focuses "
    "on analytics and decision support: importing cleaned enrollment data, showing "
    "dashboards, running forecasts, raising alerts, and generating AI-assisted "
    "insights that are tied to actual metrics. The intended users are people who "
    "already make enrollment-related decisions—administrators, enrollment staff, "
    "and analysts who need a clearer view of CEIT enrollment dynamics."
)

add_heading2("1.2 General Objectives and Specific Objectives")
add_heading3("General Objective")
add_body(
    "The general objective of this project is to design, develop, and evaluate a "
    "web-based enrollment analytics and decision support system that visualizes "
    "enrollment dynamics for CEIT programs at CSUCC and assists administrators "
    "through descriptive analytics, forecasting, alerts, and AI-generated insights."
)
add_heading3("Specific Objectives")
add_body("Specifically, the project aims to:", indent=False)
add_bullet(
    "Develop a secure web application where authorized users can manage academic "
    "references (years, semesters, colleges, departments, and programs) and "
    "maintain student and enrollment records for CEIT."
)
add_bullet(
    "Provide import tools for CSV/Excel enrollment and student datasets with "
    "validation, preview, duplicate checking, and data-quality feedback before "
    "records are saved."
)
add_bullet(
    "Build interactive dashboards and analytics views for total enrollment, "
    "growth, program and college comparisons, year-level distribution, retention-"
    "related indicators, and filtered exploration by academic period."
)
add_bullet(
    "Implement enrollment forecasting using multiple models (Naive, Moving "
    "Average, Linear Regression, and Exponential Smoothing), compare them using "
    "error metrics such as MAE, and show predicted values with lower and upper "
    "bounds."
)
add_bullet(
    "Create an AI-assisted decision support feature that turns computed metrics "
    "into readable insights, alerts, and recommendations while separating facts, "
    "trends, forecasts, interpretations, and suggested actions."
)
add_bullet(
    "Enforce role-based access control, account setup, OTP verification where "
    "applicable, audit logging, and other basic security practices suitable for "
    "campus administrative use."
)
add_bullet(
    "Test the system with sample CEIT-oriented data and document whether the "
    "modules work as intended for viewing, analysis, forecasting, and decision "
    "support."
)

add_heading2("1.3 Scope and Limitation")
add_heading3("Scope")
add_body(
    "The system covers enrollment analytics for the College of Engineering and "
    "Information Technology of CSUCC, with primary focus on Information Technology "
    "(BSIT) and Engineering programs under CEIT (BSCpE and BSEE). It supports "
    "student and enrollment record management, spreadsheet import and export, "
    "dashboards, analytics pages, forecasting, alerts, AI decision support, "
    "reports, user and privilege management, and system settings."
)
add_body(
    "Authorized roles can include Super Administrator, administrators, enrollment "
    "officers or similar staff with enrollment privileges, and decision makers who "
    "mainly view analytics and insights. The application is web-based and intended "
    "to run on a campus or local server environment using PHP/Laravel with "
    "PostgreSQL (managed through pgAdmin 4) during development and demonstration)."
)
add_heading3("Limitations")
add_body("The project has the following limitations:", indent=False)
add_bullet(
    "It is not a complete registrar or Learning Management System. Features such "
    "as grading, class scheduling, assessment payment, and full admissions "
    "workflows are outside the scope."
)
add_bullet(
    "Forecast quality depends on the length and cleanliness of historical data. "
    "With very few periods, forecasts are only rough guides."
)
add_bullet(
    "AI insights depend on the metrics already computed by the system and on the "
    "configured assistant provider. When no external LLM key is available, the "
    "system falls back to local rule-based explanations."
)
add_bullet(
    "Demographic analysis is limited to fields that are necessary and permitted. "
    "The system avoids collecting unnecessary personally identifiable information."
)
add_bullet(
    "Deployment for this capstone is primarily for demonstration and evaluation "
    "within the project’s academic setting. Campus-wide production integration "
    "with official SIS databases would require separate institutional approval "
    "and technical coordination."
)
add_bullet(
    "The study focuses on CEIT programs at CSUCC Cabadbaran Campus and does not "
    "claim to represent all colleges of the university."
)

add_heading2("1.4 Significance of the Study")
add_body(
    "For CEIT administrators and program heads, the system offers a faster way to "
    "see enrollment movement without rebuilding charts every term. Filters and "
    "dashboards make it easier to compare programs, spot declines early, and "
    "prepare for capacity or recruitment discussions with evidence on screen."
)
add_body(
    "For enrollment officers and staff who handle records, import validation and "
    "data-quality checks reduce the chance of quietly accepting broken rows. "
    "Having one place to clean, store, and review enrollment data also lowers "
    "the risk of version confusion across personal spreadsheets."
)
add_body(
    "For the university and the college, the project shows a practical path for "
    "using institutional data more intelligently. Even a college-level pilot can "
    "demonstrate how analytics and light AI support improve planning conversations "
    "without replacing existing official systems."
)
add_body(
    "For future researchers and BSIT students, the work provides a working "
    "reference for combining Laravel web development, enrollment data modeling, "
    "forecasting, and decision support in a Philippine state university setting. "
    "The documentation and source code can be extended to other colleges once "
    "policies and data pipelines allow."
)
add_body(
    "For the proponent, the project is a chance to apply software engineering, "
    "database design, analytics, and human-centered interface work to a real "
    "campus problem rather than a purely fictional case study."
)

page_break()

# =============================================================================
# CHAPTER II
# =============================================================================
add_heading1("CHAPTER II")
add_centered("REVIEW OF RELATED LITERATURE", bold=True, size=14)

add_heading2("2.1 Enrollment Information Systems in Higher Education")
add_body(
    "Higher education institutions have long used information systems to handle "
    "student registration, class lists, and official enrollment counts. Traditional "
    "Student Information Systems (SIS) are strong at transactions: admitting a "
    "student, assigning a section, and storing the official record. What they often "
    "leave unfinished is the planning layer. Administrators still export lists to "
    "Excel when they want year-to-year comparisons or program rankings, because "
    "transaction screens are not built for exploration."
)
add_body(
    "Local campuses face the same pattern. Data exists, but it is fragmented across "
    "forms, shared folders, and one-off reports prepared for meetings. When "
    "enrollment questions come up, people spend time gathering instead of deciding. "
    "Writers on education management repeatedly point out that enrollment is both "
    "an operational process and a strategic signal. Headcount affects faculty "
    "loading, laboratory use, scholarship planning, and the long-term health of a "
    "program. Treating enrollment only as a registrar checklist therefore leaves "
    "college leaders under-informed."
)
add_body(
    "This project starts from that observation. CEIT already has enrollment "
    "activity; what it needs is a focused system that keeps those records usable "
    "for analysis. The literature on campus information systems supports building "
    "specialized decision tools on top of cleaned institutional data rather than "
    "forcing every analytical need into the transactional SIS."
)

add_heading2("2.2 Data Analytics and Visualization for Academic Planning")
add_body(
    "Data analytics in education usually begins with descriptive work: totals, "
    "averages, distributions, and trends. Visualization matters because people "
    "understand a line that falls for three terms faster than they understand a "
    "table of the same values. Dashboards that combine KPI cards with charts have "
    "become common in institutional research because they let users filter by "
    "college, program, or period and immediately see the effect."
)
add_body(
    "Good academic dashboards do more than decorate numbers. They answer planning "
    "questions: Which program carries most of the college’s enrollment? Where is "
    "growth concentrated by year level? Is the share of new students rising while "
    "continuing students thin out? Studies on learning analytics and institutional "
    "dashboards emphasize interactivity—filters, drill-down, and export—because "
    "static posters of charts go stale as soon as the next semester begins."
)
add_body(
    "For this capstone, visualization is a core module rather than an afterthought. "
    "Enrollment trends, program comparisons, college views, retention-oriented "
    "charts, and filtered summaries are designed so CEIT users can explore the "
    "same dataset from different angles. The point is practical readability for "
    "administrators who are not data scientists."
)

add_heading2("2.3 Predictive Analytics and Enrollment Forecasting")
add_body(
    "Descriptive analytics explain what already happened. Predictive analytics try "
    "to estimate what may happen next. In enrollment planning, even a modest "
    "forecast helps a college ask whether the next term is likely to need more "
    "sections, more laboratory seats, or stronger recruitment. Common approaches "
    "range from naive “last value continues” baselines to moving averages, "
    "regression trends, exponential smoothing, and, when data is rich enough, "
    "more advanced machine learning models."
)
add_body(
    "A recurring lesson in forecasting literature is humility. Complex models are "
    "not automatically better. With short academic time series—often only a handful "
    "of semesters—simple models can outperform heavier ones and are easier to "
    "explain to non-technical users. Model comparison using metrics such as Mean "
    "Absolute Error (MAE), Root Mean Square Error (RMSE), or Mean Absolute "
    "Percentage Error (MAPE) helps the system show which method fit recent history "
    "best instead of blindly trusting a single formula."
)
add_body(
    "This project follows that practical path. The forecasting module compares "
    "Naive, Moving Average (3), Linear Regression, and Exponential Smoothing, "
    "then stores the best result with predicted enrollment and interval-style "
    "lower and upper bounds. Forecasts are labeled as estimates, not observed "
    "counts, which matches responsible use of predictive tools in school "
    "management."
)

add_heading2("2.4 Decision Support Systems and AI in Education")
add_body(
    "A Decision Support System (DSS) helps people choose actions by organizing "
    "data, models, and explanations. Classic DSS work already argued that managers "
    "need more than raw reports; they need structured help moving from information "
    "to judgment. In education, DSS ideas appear in tools for student success, "
    "resource allocation, and enrollment management."
)
add_body(
    "Recent systems add AI language layers that turn metrics into narrative "
    "insights. Used carefully, that layer can save time: instead of staring at "
    "eight charts, an administrator reads a short briefing that says enrollment "
    "fell overall while one program rose. Used carelessly, AI can invent causes "
    "or present speculation as fact. Responsible designs therefore bind AI text "
    "to verified analytics outputs and clearly mark what is a fact, what is a "
    "trend, what is a forecast, what is an interpretation, and what is only a "
    "recommendation."
)
add_body(
    "The project’s AI Decision Support and assistant features follow that caution. "
    "Insights are generated from computed enrollment metrics and forecast results. "
    "Alerts highlight sharp declines, growth opportunities, retention concerns, "
    "and anomalies. Recommendations are framed as things to investigate—capacity, "
    "recruitment, or data quality—not as automatic commands. This keeps the human "
    "decision maker in charge."
)

add_heading2("2.5 Synthesis")
add_body(
    "Taken together, the related literature points to a clear opportunity. "
    "Enrollment systems record students; analytics visualize patterns; forecasting "
    "estimates near-future demand; and decision support helps administrators act. "
    "Many campuses still experience these pieces separately. Spreadsheets hold the "
    "numbers, meetings hold the interpretation, and planning waits until someone "
    "has free time to connect both."
)
add_body(
    "This capstone synthesizes those strands into one web system for CEIT at "
    "CSUCC. It keeps the scope realistic—college-level analytics rather than a "
    "full SIS replacement—and emphasizes trustworthy presentation of AI output. "
    "In short, the project sits where related studies say institutions need more "
    "help: after the data is captured, and before the decision is made."
)

page_break()

# =============================================================================
# CHAPTER III
# =============================================================================
add_heading1("CHAPTER III")
add_centered("TECHNICAL BACKGROUND", bold=True, size=14)

add_heading2("3.1 Technicality of the Project")
add_body(
    "Technically, the project is a multi-user web application with a relational "
    "database backend. Users authenticate through a browser, complete account "
    "setup when required, and access only the modules allowed by their role and "
    "permissions. Enrollment and student data are stored in PostgreSQL tables linked "
    "to academic years, semesters, colleges, departments, and programs."
)
add_body(
    "On top of storage, the application runs analytics services that aggregate "
    "counts, compute growth and retention-oriented indicators, and prepare series "
    "for charts. A forecasting service compares several models and persists the "
    "best prediction. Insight and alert services convert those results into "
    "reviewable decision-support items. Chart.js renders interactive visuals on "
    "the client side, while Laravel Blade views and Alpine.js handle page "
    "structure and light interactivity."
)
add_body(
    "Security is part of the technical design, not an add-on. Passwords are "
    "hashed, routes are protected by authentication and permission middleware, "
    "sensitive actions can be rate-limited, OTP verification supports login and "
    "registration flows, and audit logs record important actions. The system is "
    "therefore both an analytics product and a controlled administrative tool."
)

add_heading2("3.2 Input–Process–Output (IPO) Model")
add_body(
    "The system can be summarized using the classic Input–Process–Output model.",
    indent=False,
)
add_heading3("Inputs")
add_bullet("User credentials and role/privilege assignments")
add_bullet("Academic reference data (academic years, semesters, colleges, departments, programs)")
add_bullet("Student records and enrollment records (manual entry or CSV/Excel import)")
add_bullet("Filter selections (period, program, year level, and related scopes)")
add_bullet("Forecast parameters (scope and horizon)")
add_bullet("Optional AI/assistant configuration (provider keys when available)")

add_heading3("Processes")
add_bullet("Authenticate users, verify OTP when required, and enforce permissions")
add_bullet("Validate, preview, and commit imported datasets; flag data-quality issues")
add_bullet("Store and update normalized records in the database")
add_bullet("Aggregate enrollment metrics and prepare visualization datasets")
add_bullet("Compare forecasting models and select the best by MAE")
add_bullet("Generate alerts and AI-assisted insights tied to computed evidence")
add_bullet("Produce reports/exports and write audit trail entries")

add_heading3("Outputs")
add_bullet("Secure dashboards and analytics pages with interactive charts")
add_bullet("Validated student/enrollment listings and export files")
add_bullet("Forecast results with predicted values and bounds")
add_bullet("AI insights, alerts, and recommendation summaries")
add_bullet("Generated reports and administrative logs")
add_body(
    "[Insert Figure 1. Input–Process–Output (IPO) Model of the System]",
    indent=False,
)

add_heading2("3.3 Details of the Technologies to be Used")
add_body(
    "The development stack was chosen because it fits campus web hosting habits "
    "and the proponent’s BSIT training path, while still supporting a modern "
    "application structure."
)
add_bullet(
    "Laravel (PHP) – main application framework for routing, controllers, "
    "Eloquent ORM, middleware, validation, and Blade templating."
)
add_bullet(
    "PostgreSQL (with pgAdmin 4) – relational database for users, academic structures, "
    "student and enrollment records, forecasts, insights, alerts, and logs."
)
add_bullet(
    "HTML, CSS, JavaScript – front-end presentation inside Blade views."
)
add_bullet(
    "Tailwind CSS (via included build/script setup) and custom theme variables – "
    "layout and visual consistency, including light/dark preferences."
)
add_bullet(
    "Alpine.js – small client-side interactions such as sidebar and theme behavior."
)
add_bullet(
    "Chart.js – interactive charts for trends, comparisons, and forecast display."
)
add_bullet(
    "Spreadsheet import support – CSV/Excel reading for bulk student and "
    "enrollment uploads with preview before commit."
)
add_bullet(
    "Optional LLM providers (e.g., Gemini/OpenAI) with local fallback – used by "
    "the assistant/insight layer when configured; otherwise rule-based replies."
)
add_bullet(
    "PostgreSQL with pgAdmin 4 – database server and administration tools used "
    "during local development and demonstration on Windows."
)

add_heading2("3.4 How the Project Will Work")
add_body(
    "A typical use cycle starts when an authorized user signs in. After login and "
    "any required OTP or first-time credential setup, the user lands on a "
    "dashboard showing enrollment KPIs for the selected filters. Enrollment "
    "officers can encode or import student and enrollment data. During import, "
    "the system checks columns and values, shows a preview, and only then writes "
    "confirmed rows to the database."
)
add_body(
    "Once enough clean history exists, analytics pages show trends and breakdowns "
    "by program, college, and related dimensions. A user who needs a forward look "
    "opens Forecasting, chooses a scope and horizon, and lets the system compare "
    "models. The best model’s prediction is saved and can appear on the dashboard. "
    "AI Decision Support and Alerts read those same metrics to draft insights—"
    "for example, a sustained decline or a program that is growing against the "
    "college average."
)
add_body(
    "Throughout the flow, permissions decide who may manage data, who may only "
    "view analytics, and who may administer users. That way the system works as a "
    "shared campus tool without giving every account the same power."
)

page_break()

# =============================================================================
# CHAPTER IV
# =============================================================================
add_heading1("CHAPTER IV")
add_centered("METHODOLOGY, RESULTS AND DISCUSSION", bold=True, size=14)

add_heading2("4.1 Planning")
add_body(
    "Planning began by naming the real pain point: CEIT enrollment data was "
    "available, but not easy to explore for decisions. From there, the team "
    "defined the product as an analytics and decision support system rather than "
    "a full SIS. Stakeholder needs were grouped into data management, "
    "visualization, forecasting, and AI-assisted recommendations."
)
add_body(
    "An iterative development approach was used. Core authentication and academic "
    "reference tables came first, then student/enrollment CRUD and import, then "
    "dashboards and analytics APIs, then forecasting and decision support. This "
    "order mattered because insights are only as good as the data underneath them. "
    "A Gantt-style schedule (see Appendix B) tracked proposal writing, system "
    "coding, testing, documentation, and defense preparation."
)
add_body(
    "Risks identified early included messy spreadsheet formats, short historical "
    "series for forecasting, and the temptation to overclaim what AI can explain. "
    "Mitigations were built into the plan: import preview, model comparison with "
    "visible error metrics, and strict labeling of forecast versus observed data."
)

add_heading2("4.2 Requirements Specification")
add_heading3("Hardware Requirements")
add_body(
    "Table 1 summarizes the minimum hardware used for development and "
    "demonstration.",
    indent=False,
)
add_body(
    "Table 1. Hardware Requirements",
    indent=False,
)
hw = [
    ("Component", "Minimum Specification"),
    ("Processor", "Intel Core i3 / Ryzen 3 or better"),
    ("Memory", "8 GB RAM (16 GB preferred)"),
    ("Storage", "256 GB SSD with free space for project files and the PostgreSQL database"),
    ("Display", "1366 × 768 or higher"),
    ("Network", "Local network or localhost for demo; internet optional for LLM APIs and mail"),
]
table = doc.add_table(rows=len(hw), cols=2)
table.style = "Table Grid"
for i, (a, b) in enumerate(hw):
    table.rows[i].cells[0].text = a
    table.rows[i].cells[1].text = b
doc.add_paragraph()

add_heading3("Software Requirements")
add_body("Table 2. Software Requirements", indent=False)
sw = [
    ("Software", "Purpose"),
    ("Windows 10/11", "Development workstation OS"),
    ("PHP 8.x / Laravel", "Application runtime that serves the web system"),
    ("PostgreSQL", "Database server storing enrollment and system data"),
    ("pgAdmin 4", "Tool for managing and querying the PostgreSQL database"),
    ("Composer", "PHP dependency management for Laravel"),
    ("Web browser (Chrome/Edge/Firefox)", "Accessing the system UI"),
    ("VS Code / Cursor or similar IDE", "Coding and debugging"),
    ("Microsoft Excel / spreadsheet tool", "Preparing import datasets"),
]
table = doc.add_table(rows=len(sw), cols=2)
table.style = "Table Grid"
for i, (a, b) in enumerate(sw):
    table.rows[i].cells[0].text = a
    table.rows[i].cells[1].text = b
doc.add_paragraph()

add_heading3("Functional Requirements Summary")
add_body("Table 3. Functional Requirements Summary", indent=False)
fr = [
    ("Module", "Key Requirement"),
    ("Authentication", "Login, logout, OTP where applicable, password/account setup, role checks"),
    ("Data Management", "CRUD for academic years, semesters, colleges, departments, programs"),
    ("Students & Enrollment", "Encode, update, delete (as permitted), import/export, quality checks"),
    ("Dashboard & Analytics", "KPI cards, filters, trends, program/college/retention views"),
    ("Forecasting", "Run multi-model forecast, store best result, show bounds and metrics"),
    ("AI Decision Support", "Generate insights/recommendations grounded on computed metrics"),
    ("Alerts & Reports", "Generate/read alerts; produce management-oriented reports"),
    ("Administration", "User management, privileges, approvals, audit/login logs, settings"),
]
table = doc.add_table(rows=len(fr), cols=2)
table.style = "Table Grid"
for i, (a, b) in enumerate(fr):
    table.rows[i].cells[0].text = a
    table.rows[i].cells[1].text = b
doc.add_paragraph()

add_heading3("User Roles and Access Privileges")
add_body(
    "Access is permission-based. Conceptually, roles map to the privileges shown "
    "in Table 4.",
    indent=False,
)
add_body("Table 4. User Roles and Access Privileges", indent=False)
roles = [
    ("Role / User Type", "Typical Access"),
    ("Super Administrator", "Full control including delete approvals, audit logs, and high-risk settings"),
    ("Administrator", "User management, data management, analytics, forecasting, insights, reports"),
    ("Enrollment Officer / Staff", "Student and enrollment encode/import, selected views and exports"),
    ("Decision Maker / Viewer", "Dashboards, analytics, forecasts, AI insights, and reports (view-oriented)"),
]
table = doc.add_table(rows=len(roles), cols=2)
table.style = "Table Grid"
for i, (a, b) in enumerate(roles):
    table.rows[i].cells[0].text = a
    table.rows[i].cells[1].text = b
doc.add_paragraph()

add_heading2("4.3 Analysis")
add_body(
    "Analysis work translated campus needs into system behavior. Interviews and "
    "informal discussions with people familiar with enrollment reporting pointed "
    "to repeated manual charting, uncertainty about which spreadsheet was latest, "
    "and delayed awareness of program-level drops. From those pain points, use "
    "cases were listed: import data, clean data, view dashboard, compare programs, "
    "forecast next period, review AI insight, and manage users."
)
add_body(
    "Data analysis also shaped the schema. A student record is identified mainly "
    "by Student ID for a given term context, while enrollment analytics aggregate "
    "across academic year, semester, program, and year level. CEIT scope was "
    "intentionally narrow so the first version could be deep enough to be useful: "
    "Information Technology and Engineering groups under one college, rather than "
    "every program in the campus on day one."
)
add_body(
    "Non-functional needs included usability on desktop browsers, clear labels for "
    "forecast versus actual values, and security suitable for campus accounts. "
    "Performance expectations were modest—interactive summaries over college-scale "
    "data—rather than nationwide big-data throughput."
)

add_heading2("4.4 System Design")
add_heading3("Context Diagram")
add_body(
    "At the highest level, the system sits between authorized users and the "
    "enrollment database. Users provide credentials, records, and analysis "
    "requests. The system returns dashboards, forecasts, insights, and reports. "
    "External optional services include email/OTP delivery and an LLM provider "
    "for assistant replies when configured."
)
add_body("[Insert Figure 3. Context Diagram]", indent=False)

add_heading3("Data Flow Diagram")
add_body(
    "Level 0 data flow starts with Login/Auth, then branches into Data Management, "
    "Analytics, Forecasting, Decision Support, and Administration. Enrollment "
    "data stores feed analytics; analytics outputs feed charts, forecasts, and "
    "insight generation. Audit logging listens across modules."
)
add_body("[Insert Figure 4. Data Flow Diagram (Level 0)]", indent=False)

add_heading3("UML Use Case Diagram")
add_body(
    "Major use cases include Manage Users, Manage Academic Data, Manage Student/"
    "Enrollment Records, Import Dataset, View Dashboard, View Analytics, Run "
    "Forecast, Generate AI Insights, Manage Alerts, Generate Reports, and "
    "Configure Settings. Actors correspond to the roles in Table 4."
)
add_body("[Insert Figure 5. Use Case Diagram]", indent=False)

add_heading3("Entity Relationship Diagram")
add_body(
    "Core entities include users, roles/permissions, academic_years, semesters, "
    "colleges, departments, programs, student_records, enrollment_records, "
    "forecasts, ai_insights, alerts, audit_logs, and related supporting tables "
    "for registration approvals and login history. Relationships follow normal "
    "college structures: a college has departments; a department has programs; "
    "enrollment rows reference period and program dimensions."
)
add_body("[Insert Figure 6. Entity Relationship Diagram]", indent=False)

add_heading3("System Architecture")
add_body(
    "The architecture is a standard Laravel MVC web stack: Browser → Apache → "
    "Laravel Routes/Controllers → Services (Analytics, Forecast, Insight, Import) "
    "→ Eloquent Models → PostgreSQL database server (administered with pgAdmin 4). "
    "Presentation uses Blade views with Chart.js for visuals. Permission middleware "
    "guards modules. This layered style keeps business rules (for example, model "
    "comparison) inside services instead of scattering formulas across pages."
)
add_body("[Insert Figure 2. System Architecture Diagram]", indent=False)

add_heading3("Forecasting Design")
add_body(
    "Table 5 lists the models compared whenever a user runs a forecast.",
    indent=False,
)
add_body("Table 5. Forecasting Models Compared by the System", indent=False)
fm = [
    ("Model", "Idea in Plain Terms"),
    ("Naive", "Next value is assumed close to the latest observed enrollment"),
    ("Moving Average (3)", "Average of the most recent three points, when available"),
    ("Linear Regression", "Fits a straight trend line across the historical series"),
    ("Exponential Smoothing", "Gives more weight to recent observations (alpha = 0.4)"),
]
table = doc.add_table(rows=len(fm), cols=2)
table.style = "Table Grid"
for i, (a, b) in enumerate(fm):
    table.rows[i].cells[0].text = a
    table.rows[i].cells[1].text = b
doc.add_paragraph()
add_body(
    "The system evaluates candidates, sorts them by MAE, and saves the best "
    "prediction with lower and upper bounds derived from residual variation. "
    "Horizon can be set for near-term periods (up to a small configurable limit). "
    "This design favors explainability for college users."
)

add_heading2("4.5 Output and User Interface Design")
add_body(
    "The interface uses a campus-inspired green theme associated with CSUCC "
    "branding, with support for user appearance preferences. The sidebar groups "
    "modules into Enrollment, Analytics, Forecasting, AI Decision Support, "
    "Alerts, Reports, Data Management, Users, and Settings so daily work feels "
    "familiar to administrative staff."
)
add_body(
    "The dashboard presents KPI cards such as total enrollment and forecasted "
    "next-period enrollment, plus filters for academic context. Student and "
    "enrollment pages support search, filtering, and import/export. Analytics "
    "pages separate summary, trends, programs, colleges, and retention views so "
    "each screen has one job. Forecasting and AI Support pages show both charts "
    "or metrics and the textual interpretation needed for meetings."
)
add_body(
    "Public-facing pages (home, about, terms) introduce the system before login. "
    "Authenticated screens prioritize clarity over decoration: readable tables, "
    "obvious primary actions, and labels that distinguish actual counts from "
    "model estimates."
)
add_body("[Insert Figure 7. Sample Dashboard Interface]", indent=False)
add_body("[Insert Figure 8. Sample Forecasting Interface]", indent=False)
add_body("[Insert Figure 9. Sample AI Decision Support Interface]", indent=False)

add_heading2("4.6 Testing Results")
add_body(
    "Testing combined unit/feature-style checks where available, manual walkthroughs "
    "of each module, and sample data exercises using CEIT-oriented student/"
    "enrollment spreadsheets. The goal was to verify that a normal administrative "
    "workflow—from login to insight—could be completed without dead ends."
)
add_body("Table 6. Sample Testing Results by Module", indent=False)
tr = [
    ("Module", "Test Focus", "Result"),
    ("Login / OTP / Setup", "Valid and invalid credentials; first-time setup pages", "Passed in demo environment"),
    ("Student & Enrollment CRUD", "Create, update, filter, export records", "Passed"),
    ("Import Preview/Commit", "Reject bad rows; preview before saving", "Passed with validation messages"),
    ("Dashboard Filters", "KPI and charts respond to selected period/program filters", "Passed"),
    ("Analytics Pages", "Trends/program/college/retention views load with data", "Passed"),
    ("Forecasting", "Requires ≥2 periods; compares models; stores best forecast", "Passed"),
    ("AI Support / Alerts", "Insights reference computed metrics; alerts can be marked read", "Passed"),
    ("Permissions", "Users without privilege cannot open restricted routes", "Passed"),
]
table = doc.add_table(rows=len(tr), cols=3)
table.style = "Table Grid"
for i, row in enumerate(tr):
    for j, val in enumerate(row):
        table.rows[i].cells[j].text = val
doc.add_paragraph()
add_body(
    "Discussion of results. The strongest outcome is end-to-end coherence: once "
    "sample data is loaded, the same database drives dashboards, forecasts, and "
    "insights. That reduces the old habit of maintaining one sheet for counting "
    "and another for presentation. Import validation proved especially useful "
    "because real spreadsheets are rarely perfect on the first try."
)
add_body(
    "Forecasting behaved as designed on short series: it refuses to invent a "
    "forecast from a single point and becomes more meaningful as periods "
    "accumulate. AI text was most helpful when it restated verified metrics and "
    "suggested checks; it was intentionally not treated as proof of why "
    "enrollment changed. Remaining issues for future hardening include deeper "
    "institutional data integration, richer long-horizon models when more history "
    "exists, and broader user training materials for non-technical staff."
)

page_break()

# =============================================================================
# CHAPTER V
# =============================================================================
add_heading1("CHAPTER V")
add_centered("CONCLUSION AND RECOMMENDATION", bold=True, size=14)

add_heading2("5.1 Conclusion")
add_body(
    "This capstone project set out to make CEIT enrollment data at CSUCC easier "
    "to understand and easier to use for planning. The finished web system—"
    "Visualization of Enrollment Dynamics Utilizing Data Analytics with "
    "AI-Driven Decision Support—lets authorized users manage and import "
    "enrollment-related records, explore interactive analytics, run multi-model "
    "forecasts, and review AI-assisted insights and alerts."
)
add_body(
    "Based on the design, implementation, and testing documented in the previous "
    "chapters, the project meets its specific objectives. Data management and "
    "security foundations are in place; visualization and analytics turn records "
    "into readable trends; forecasting provides near-term estimates with visible "
    "model comparison; and the decision support layer helps administrators move "
    "from charts to conversation without pretending that every AI sentence is a "
    "hard fact."
)
add_body(
    "The larger lesson is practical. Colleges do not always need a massive "
    "platform to improve enrollment decisions. A focused system that respects "
    "data quality, explains its forecasts, and keeps humans in control can already "
    "change how a department prepares for the next term."
)

add_heading2("5.2 Recommendation")
add_body("For future development and institutional use, the following are recommended:", indent=False)
add_bullet(
    "Coordinate with the campus registrar and MIS units for approved, recurring "
    "data extracts so the analytics database stays current without repeated "
    "manual uploads."
)
add_bullet(
    "Extend the pilot gradually to other colleges only after CEIT workflows, "
    "definitions of retention, and data dictionaries are stable."
)
add_bullet(
    "Add more historical terms whenever available so forecasting can use longer "
    "series and, if justified, additional models."
)
add_bullet(
    "Conduct formal usability testing with actual program chairs and enrollment "
    "staff, then refine labels, defaults, and report templates based on their "
    "feedback."
)
add_bullet(
    "Prepare a short user’s guide and training session before any production "
    "rollout, including clear reminders that forecasts and AI insights support—"
    "not replace—administrative judgment."
)
add_bullet(
    "Strengthen backup, hosting, and access review procedures if the system is "
    "deployed beyond a classroom demonstration environment."
)

page_break()

# =============================================================================
# REFERENCES
# =============================================================================
add_heading1("REFERENCES")
refs = [
    "Few, S. (2013). Information dashboard design: Displaying data for at-a-glance monitoring (2nd ed.). Analytics Press.",
    "Hyndman, R. J., & Athanasopoulos, G. (2021). Forecasting: Principles and practice (3rd ed.). OTexts. https://otexts.com/fpp3/",
    "Larman, C. (2004). Applying UML and patterns: An introduction to object-oriented analysis and design and iterative development (3rd ed.). Prentice Hall.",
    "Laudon, K. C., & Laudon, J. P. (2020). Management information systems: Managing the digital firm (16th ed.). Pearson.",
    "Power, D. J. (2002). Decision support systems: Concepts and resources for managers. Quorum Books.",
    "Sharda, R., Delen, D., & Turban, E. (2021). Business intelligence, analytics, and data science: A managerial perspective (5th ed.). Pearson.",
    "Siemens, G., & Long, P. (2011). Penetrating the fog: Analytics in learning and education. EDUCAUSE Review, 46(5), 30–40.",
    "Turban, E., Sharda, R., & Delen, D. (2014). Decision support and business intelligence systems (9th ed.). Pearson.",
    "Laravel LLC. (n.d.). Laravel documentation. https://laravel.com/docs",
    "Chart.js contributors. (n.d.). Chart.js documentation. https://www.chartjs.org/docs/",
    "Caraga State University Cabadbaran Campus. (n.d.). Institutional information and academic programs. CSUCC.",
]
for r in refs:
    p = doc.add_paragraph()
    p.paragraph_format.first_line_indent = Inches(-0.5)
    p.paragraph_format.left_indent = Inches(0.5)
    p.paragraph_format.line_spacing_rule = WD_LINE_SPACING.DOUBLE
    run = p.add_run(r)
    run.font.name = "Times New Roman"
    run.font.size = Pt(12)

page_break()

# =============================================================================
# APPENDICES
# =============================================================================
add_heading1("APPENDICES")

add_heading2("Appendix A – Relevant Source Code")
add_body(
    "Selected source files from the Laravel application (laravel-app) may be "
    "attached here, such as routes/web.php, ForecastService.php, Analytics "
    "services, import services, and major Blade views. Because full listings are "
    "long, the bound manuscript may include only key excerpts, with the complete "
    "repository submitted separately to the adviser as required."
)

add_heading2("Appendix B – Gantt Chart / Financial Work Plan")
add_body(
    "Insert the approved Gantt chart covering proposal, requirements, design, "
    "coding, testing, documentation, and defense. If a financial work plan is "
    "required by the course guide, place the budget summary on the following page."
)

add_heading2("Appendix C – Sample Survey / Evaluation Questionnaire")
add_body(
    "If the panel requires user acceptance or perception survey forms, attach the "
    "questionnaire used with evaluators (e.g., ease of use, clarity of dashboards, "
    "usefulness of forecasts and insights)."
)

add_heading2("Appendix D – Evaluation Process")
add_body(
    "Describe how evaluators reviewed the system (demo script, scoring rubric, "
    "and summary of ratings) according to department requirements."
)

add_heading2("Appendix E – Grammarian Certificate")
add_body("[Attach signed grammarian certificate.]")

add_heading2("Appendix F – Statistician Certificate (if applicable)")
add_body(
    "[Attach only if statistical treatment beyond the system’s built-in metrics "
    "was formally certified.]"
)

add_heading2("Appendix G – Certificate of Acceptance")
add_body(
    "[Attach if the adviser or office requires acceptance of a deployed or "
    "demonstrated system.]"
)

add_heading2("Appendix H – Certificate of Approval of System Output")
add_body("[Attach approved system output certificate.]")

add_heading2("Appendix I – Panel Oral Examination Report")
add_body("[Attach panel oral examination report.]")

add_heading2("Appendix J – Approval for Binding")
add_body("[Attach approval for binding.]")

add_heading2("Appendix K – User’s Guide")
add_body(
    "A short user’s guide should explain: how to log in; how to import student/"
    "enrollment files; how to use dashboard filters; how to run a forecast; how "
    "to open AI insights and alerts; and how administrators manage users and "
    "privileges. Screenshots from the live system may be inserted here."
)

add_heading2("Appendix L – Definition of Terms")
terms = [
    ("Enrollment Dynamics", "Patterns of change in student headcount across academic periods, programs, and related groupings."),
    ("Descriptive Analytics", "Computation and presentation of what has already occurred in the enrollment data."),
    ("Forecasting", "Estimation of likely future enrollment using historical series and selected models."),
    ("Decision Support System", "Software that helps decision makers by combining data, models, and explanations."),
    ("MAE (Mean Absolute Error)", "Average absolute difference between predicted and actual values; used to compare forecast models."),
    ("OTP", "One-time password used as an extra verification step during authentication or account recovery."),
    ("CEIT", "College of Engineering and Information Technology of CSUCC."),
    ("CSUCC", "Caraga State University Cabadbaran Campus."),
]
for term, meaning in terms:
    p = doc.add_paragraph()
    p.paragraph_format.first_line_indent = Inches(0)
    p.paragraph_format.line_spacing_rule = WD_LINE_SPACING.DOUBLE
    run = p.add_run(f"{term}. ")
    run.bold = True
    run.font.name = "Times New Roman"
    run.font.size = Pt(12)
    run2 = p.add_run(meaning)
    run2.font.name = "Times New Roman"
    run2.font.size = Pt(12)

add_heading2("Appendix M – Consultation Forms / Supporting Documents")
add_body(
    "[Attach consultation logs, adviser notes, and other supporting documents "
    "required by the department.]"
)

page_break()

add_heading1("CURRICULUM VITAE")
add_body(
    "Place a 2×2 formal photo (white background, attire with collar) at the upper "
    "right, following the sample format provided in the course Google Classroom.",
    indent=False,
)
add_body("Personal Information", indent=False)
add_bullet("Name: [Full Name]")
add_bullet("Address: [Complete Address]")
add_bullet("Contact Number: [Number]")
add_bullet("Email: [Email Address]")
add_body("Educational Background", indent=False)
add_bullet("Bachelor of Science in Information Technology – Caraga State University Cabadbaran Campus")
add_body("Skills relevant to the project", indent=False)
add_bullet("Web development (PHP/Laravel), database design (PostgreSQL / pgAdmin 4), basic data analytics and visualization")
add_body(
    "Note: Expand the CV using the exact classroom template (objectives, seminars, "
    "affiliations, character references) before final binding.",
    indent=False,
)

doc.save(OUT)
print("Saved:", OUT)
