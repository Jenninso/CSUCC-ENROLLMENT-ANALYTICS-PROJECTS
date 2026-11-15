# -*- coding: utf-8 -*-
"""Chapter IV manuscript from the IntelliFlow UAT tally (v4) and the implemented system."""

from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_LINE_SPACING
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, Cm, Twips

OUT = r"C:\xampp new\htdocs\CAPSTONE\CHAPTER IV - METHODOLOGY, RESULTS AND DISCUSSION.docx"

doc = Document()
section = doc.sections[0]
section.page_width = Inches(8.5)
section.page_height = Inches(11)
section.left_margin = Inches(1.5)
section.right_margin = Inches(1)
section.top_margin = Inches(1)
section.bottom_margin = Inches(1)


def set_run(run, size=12, bold=False, italic=False):
    run.font.name = "Times New Roman"
    run._element.rPr.rFonts.set(qn("w:eastAsia"), "Times New Roman")
    run.font.size = Pt(size)
    run.bold = bold
    run.italic = italic


def add_p(text, *, size=12, bold=False, italic=False, align="justify", space_before=0, space_after=0, first_indent=0.5, line=2.0):
    p = doc.add_paragraph()
    p.alignment = {
        "justify": WD_ALIGN_PARAGRAPH.JUSTIFY,
        "center": WD_ALIGN_PARAGRAPH.CENTER,
        "left": WD_ALIGN_PARAGRAPH.LEFT,
    }[align]
    pf = p.paragraph_format
    pf.space_before = Pt(space_before)
    pf.space_after = Pt(space_after)
    pf.line_spacing = line
    pf.first_line_indent = Inches(first_indent) if first_indent else Inches(0)
    run = p.add_run(text)
    set_run(run, size, bold, italic)
    return p


def heading1(text):
    add_p(text, size=14, bold=True, align="center", first_indent=0, space_before=0, space_after=6)


def heading2(text):
    add_p(text, size=12, bold=True, align="left", first_indent=0, space_before=12, space_after=6)


def heading3(text):
    add_p(text, size=12, bold=True, italic=True, align="left", first_indent=0, space_before=10, space_after=4)


def body(text):
    add_p(text, align="justify", first_indent=0.5)


def caption(text):
    add_p(text, size=12, bold=True, align="center", first_indent=0, space_before=10, space_after=4, line=1.15)


def note(text):
    add_p(text, size=10, italic=True, align="justify", first_indent=0, space_before=2, space_after=8, line=1.15)


def shade_cell(cell, hex_color):
    tc = cell._tc
    tcPr = tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:fill"), hex_color)
    shd.set(qn("w:val"), "clear")
    tcPr.append(shd)


def set_cell(cell, text, *, bold=False, size=10, align="center"):
    cell.text = ""
    p = cell.paragraphs[0]
    p.alignment = {
        "center": WD_ALIGN_PARAGRAPH.CENTER,
        "left": WD_ALIGN_PARAGRAPH.LEFT,
        "right": WD_ALIGN_PARAGRAPH.RIGHT,
    }[align]
    pf = p.paragraph_format
    pf.space_before = Pt(1)
    pf.space_after = Pt(1)
    pf.line_spacing = 1.0
    run = p.add_run(text)
    set_run(run, size, bold)


def prevent_row_split(row):
    tr = row._tr
    trPr = tr.get_or_add_trPr()
    cant = OxmlElement("w:cantSplit")
    trPr.append(cant)


def add_table(headers, rows, col_widths, left_cols=None):
    left_cols = left_cols or set()
    table = doc.add_table(rows=1 + len(rows), cols=len(headers))
    table.style = "Table Grid"
    table.autofit = False
    tbl = table._tbl
    tblPr = tbl.tblPr if tbl.tblPr is not None else OxmlElement("w:tblPr")
    tblW = OxmlElement("w:tblW")
    total = sum(col_widths)
    tblW.set(qn("w:w"), str(int(total * 1440)))
    tblW.set(qn("w:type"), "dxa")
    tblPr.append(tblW)

    for i, h in enumerate(headers):
        cell = table.rows[0].cells[i]
        set_cell(cell, h, bold=True, size=10)
        shade_cell(cell, "1F4E79")
        for p in cell.paragraphs:
            for r in p.runs:
                r.font.color.rgb = None
                r.font.color.theme_color = None
                from docx.shared import RGBColor
                r.font.color.rgb = RGBColor(255, 255, 255)
    prevent_row_split(table.rows[0])

    for r_i, row in enumerate(rows):
        is_total = str(row[0]).lower().startswith("total") or str(row[0]).lower().startswith("iso") or str(row[0]).lower().startswith("tam") or str(row[0]).lower().startswith("composite") or "grand" in str(row[0]).lower() or str(row[0]).endswith("Mean")
        for c_i, val in enumerate(row):
            cell = table.rows[r_i + 1].cells[c_i]
            align = "left" if c_i in left_cols else "center"
            set_cell(cell, str(val), bold=is_total, size=10, align=align)
            if is_total:
                shade_cell(cell, "D6E3F0")
            elif r_i % 2 == 1:
                shade_cell(cell, "F4F7FB")
        prevent_row_split(table.rows[r_i + 1])

    for row in table.rows:
        for i, w in enumerate(col_widths):
            row.cells[i].width = Inches(w)
    doc.add_paragraph().paragraph_format.space_after = Pt(0)
    return table


# =============================================================================
heading1("CHAPTER IV")
add_p("METHODOLOGY, RESULTS AND DISCUSSION", size=14, bold=True, align="center", first_indent=0, space_after=6)
add_p(
    "CEIT Student Attrition Prediction and Enrollment Visualization System Using Random Forest (IntelliFlow)",
    size=12, italic=True, align="center", first_indent=0, space_after=12,
)

body(
    "This chapter presents how IntelliFlow was planned, specified, analyzed, and designed, "
    "and how the finished system was evaluated by its intended users. Sections 4.1 to 4.5 "
    "describe the development methodology and the design of the web application. Section 4.6 "
    "reports the user-acceptance results gathered on 12 November 2026 from a purposive sample "
    "of College of Engineering and Information Technology (CEIT) personnel at Caraga State "
    "University – Cabadbaran Campus. Product quality was measured with the eight characteristics "
    "of ISO/IEC 25010, and acceptance was measured with the Technology Acceptance Model (TAM) "
    "constructs of perceived usefulness and perceived ease of use."
)

# ----- 4.1 -----
heading2("4.1 Planning")
body(
    "Planning started from a campus problem rather than from a technology. CEIT keeps "
    "enrollment and academic records, but those records are difficult to read when a department "
    "needs to see who is likely to drop, shift, or fall behind before the term ends. The product "
    "was therefore planned as a decision-support system for the college, not as a replacement "
    "for the university student-information system. The working name of the product is IntelliFlow. "
    "Its full title is the CEIT Student Attrition Prediction and Enrollment Visualization System "
    "Using Random Forest."
)
body(
    "The plan grouped the work into four outcomes that match the objectives of the study. First, "
    "authorized staff must be able to bring in batch enrollment files in CSV or Excel form, check "
    "them, and store them without losing rows. Second, dashboards must turn retention, dropout, "
    "and inbound or outbound shifting into charts that a chair or adviser can read in a meeting. "
    "Third, a Random Forest model must score mid-semester attrition risk and place each student "
    "in a High, Medium, or Low band, with feature-importance notes that explain why the student "
    "was flagged. Fourth, the same record must support an intervention: a downloadable CEIT "
    "departmental academic referral form and a risk-summary report in PDF or Excel."
)
body(
    "Development followed an iterative order. Authentication, roles, and the academic reference "
    "tables (academic year, semester, college, department, and program) were built first, because "
    "every later screen depends on them. Student records, the Student Profile Record (SPR) form, "
    "and the import preview were built next. Analytics, the enrollment-dynamics dashboard, and "
    "the Random Forest scoring service followed only after the data path was stable. Referral "
    "documents, alerts, and the in-system assistant were added last. A Gantt chart for proposal "
    "writing, coding, testing, and documentation is placed in Appendix B."
)
body(
    "Three risks were written into the plan. Spreadsheet files from offices are rarely clean, so "
    "import must preview and reject bad rows instead of failing silently. A short historical "
    "series cannot support a confident enrollment forecast, so the forecast module requires at "
    "least two periods and reports a band around the Random Forest point estimate. Student "
    "records are personal data under Republic Act No. 10173, so the plan required login, "
    "role restrictions, and representation of students by surrogate identifiers rather than open "
    "display of personally identifiable information on shared screens."
)

# ----- 4.2 -----
heading2("4.2 Requirements Specification")
body(
    "Requirements were taken from the objectives, from the modules that CEIT staff actually "
    "walk through, and from the indicators later used in the user-acceptance instrument. "
    "Hardware and software requirements below are the minimum setup used to develop and "
    "demonstrate the system. All five evaluators in the acceptance test used a laptop, which "
    "matches the intended client device."
)

heading3("Hardware Requirements")
caption("Table 1. Hardware Requirements")
add_table(
    ["Component", "Minimum Specification", "Purpose in IntelliFlow"],
    [
        ["Processor", "Intel Core i3 / AMD Ryzen 3 or better", "Web server, import parsing, and Random Forest scoring"],
        ["Memory", "8 GB RAM (16 GB preferred)", "Laravel runtime, database, and browser charts together"],
        ["Storage", "256 GB SSD with free space for the database and uploads", "Enrollment files, prediction logs, and generated reports"],
        ["Display", "1366 × 768 or higher", "Dashboard, heatmaps, and multi-column tables"],
        ["Network", "Campus LAN or localhost for the pilot", "Browser access; internet only if mail or an external assistant is enabled"],
        ["Client device", "Laptop or desktop with a current browser", "No client installation; tablets are supported by the same web UI"],
    ],
    [1.3, 2.5, 2.2],
    left_cols={0, 1, 2},
)
note("Note. The acceptance-test respondents all used laptops. Phone use was not observed in this evaluation.")

heading3("Software Requirements")
caption("Table 2. Software Requirements")
add_table(
    ["Software", "Role in the System"],
    [
        ["Windows 10/11", "Development and pilot workstation"],
        ["Apache (XAMPP) with PHP 8.3", "Serves the Laravel application"],
        ["Laravel 13", "Routing, authentication, permissions, and Blade views"],
        ["PostgreSQL", "Stores users, academic structure, enrollment, forecasts, alerts, and audit logs"],
        ["Composer", "Installs PHP dependencies"],
        ["Google Chrome, Microsoft Edge, or Mozilla Firefox", "Client access; no extra plug-in"],
        ["Microsoft Excel or a compatible spreadsheet tool", "Preparing CSV and Excel enrollment batches"],
        ["PHPUnit", "Automated checks of attrition scoring, forecasting, and access rules"],
    ],
    [2.7, 3.3],
    left_cols={0, 1},
)

body(
    "The functional requirements that the acceptance instrument later tested are summarized "
    "here so the design and the results use the same language. The system must parse and "
    "validate batch CSV and Excel enrollment files; draw retention, dropout, and shifting on "
    "the dashboard; score attrition with Random Forest into High, Medium, and Low; fill "
    "referral forms and risk-summary reports; and show feature importance. It must stay "
    "responsive while scoring and while exporting PDF or Excel. It must run in the major "
    "browsers, scale on laptop screens, and keep report layout stable. Navigation among the "
    "dashboard, data management, alerts, and reports must be learnable on a first sitting. "
    "Login must be required, administrative actions must follow roles, uploaded personal data "
    "must be scrubbed or held behind surrogate student identifiers, and staff must not need "
    "a direct connection to a live university database. Dataset errors must be correctable "
    "in the data-management module, prediction logs must be traceable, and program codes or "
    "a retrained forest must be replaceable without rebuilding the application."
)

# ----- 4.3 -----
heading2("4.3 Analysis")
body(
    "Analysis turned the planning outcomes into behavior and data. The primary users are "
    "the CEIT administrator, CEIT faculty advisers and instructors, and personnel of the "
    "Office of the Admission, Scholarship, and Financial Assistance (OASFA). The registrar "
    "was identified as a possible data source and was included in the evaluation form, but "
    "the pilot treats registrar extracts as files that an authorized CEIT user uploads. "
    "Students use a narrower portal: the SPR form, including a public token link, and do "
    "not see cohort risk lists."
)
body(
    "The use cases that follow from those roles are: sign in (with one-time verification "
    "when required); manage academic years, semesters, colleges, departments, and programs; "
    "encode or import student and enrollment records after a preview; view the enrollment "
    "dashboard and program, college, trend, and retention analytics; run a Random Forest "
    "enrollment forecast when at least two periods exist; open the attrition list and a "
    "single student’s risk explanation; generate a referral or risk-summary report; record "
    "an intervention; and administer accounts, privileges, and logs. Each use case was "
    "checked against a permission so that a faculty adviser does not receive the same "
    "actions as a super administrator."
)
body(
    "Data analysis fixed the grain of the records. A student is known to the model by a "
    "student identifier, a program, a year level, and a term. Academic signals used for "
    "risk include grade-point standing, attendance, and failed subjects. Enrollment "
    "analytics aggregate the same records by academic year, semester, program, and college "
    "so the dashboard can show retention and shifting without listing every name. The "
    "SPR adds advising context. Risk output is stored as a level and a short explanation, "
    "and an intervention row can later point back to that student. This split keeps the "
    "prediction reproducible and keeps the advising note separate from the model score."
)
body(
    "Non-functional analysis set the acceptance targets that Section 4.6 measures. The "
    "interface should be readable on a laptop without training beyond a short demonstration. "
    "Scoring and export should finish inside an ordinary page request. Bad files should "
    "produce a message rather than a crash. Access should fail closed when a role lacks "
    "the permission. These targets are perception targets for a pilot: the user-acceptance "
    "test asks whether staff believe the system meets them. It does not, by itself, "
    "certify the statistical accuracy of the forest."
)

# ----- 4.4 -----
heading2("4.4 System Design")

heading3("Context Diagram")
body(
    "The context diagram places IntelliFlow between the people who supply records and the "
    "people who act on risk. The CEIT administrator and faculty adviser send credentials, "
    "enrollment files, filter choices, and intervention notes. OASFA personnel consult "
    "scholarship and financial-referral context. The system returns dashboards, risk bands, "
    "feature-importance explanations, alerts, and downloadable referral and summary reports. "
    "The database is inside the system boundary. Optional external services are mail for "
    "one-time passwords and, when configured, an assistant model for drafted replies. The "
    "university’s live registration database stays outside the boundary: the pilot consumes "
    "exported files so that a capstone application never opens a direct connection to "
    "production student records."
)
add_p("[Insert Figure 1. Context Diagram of IntelliFlow]", italic=True, align="center", first_indent=0, space_before=6, space_after=6)

heading3("Data Flow Diagram")
body(
    "At Level 0, Login and Account Control admit the user and write an audit event. Data "
    "Management receives CSV or Excel batches, validates columns and values, and writes "
    "confirmed rows to the student and enrollment stores. Analytics reads those stores and "
    "produces retention, dropout, shifting, and subject-difficulty series for the charts. "
    "Attrition Scoring trains or applies the Random Forest on the current term’s academic "
    "features, writes a High, Medium, or Low level, and attaches the features that "
    "contributed most to the score. Forecasting reads the historical enrollment series and "
    "stores the next-period Random Forest estimate with a lower and upper band. Decision "
    "Support reads the score, the charts, and the intervention log, then fills the referral "
    "form and the risk-summary export. Administration reads and writes users, privileges, "
    "and settings. Every process that changes data also writes the audit log."
)
add_p("[Insert Figure 2. Data Flow Diagram (Level 0)]", italic=True, align="center", first_indent=0, space_before=6, space_after=6)
body(
    "Level 1 of the import process is the flow staff notice most. The file is parsed, "
    "rows with missing identifiers or impossible values are listed, a preview is shown, "
    "and only a confirmed preview is committed. Level 1 of attrition scoring separates "
    "feature preparation, forest prediction, banding, and explanation so that a wrong "
    "band can be traced to a feature rather than treated as a black-box label."
)

heading3("UML Use Case Diagram")
body(
    "The use-case diagram uses four actors: Super Administrator, Administrator, Staff "
    "(the CEIT faculty adviser, instructor, or enrollment user whose privileges are "
    "assigned), and Student. The student actor reaches only the SPR form. Staff actors "
    "include View Dashboard, View Analytics, View At-Risk List, Open Student Risk Detail, "
    "Generate Referral or Risk Summary, and Record Intervention. Actors with data "
    "privileges add Import Enrollment File, Manage Student Records, and Manage SPR "
    "Applications. Administrator actors add Manage Users, Approve Registration, and "
    "Manage Academic Structure. Run Forecast and View Alerts are included for actors "
    "who hold those privileges. Login is an included use case of every authenticated "
    "action. The diagram should be drawn so that privilege boundaries are visible: "
    "viewing a risk list is not the same use case as editing the underlying record."
)
add_p("[Insert Figure 3. UML Use Case Diagram]", italic=True, align="center", first_indent=0, space_before=6, space_after=6)

heading3("Entity Relationship Diagram")
body(
    "The persistent model follows the college structure and the prediction workflow. "
    "A role has many users, and roles are linked to permissions. A college has "
    "departments; a department has programs. Academic years and semesters label every "
    "term-bound row. A student record belongs to a program, an academic year, and a "
    "semester, and carries the academic features used for scoring (including grade "
    "standing, attendance, year level, and section). Enrollment records hold the "
    "period counts used by the dashboard and the forecast. An SPR application stores "
    "the profile form and may be reached by a public token. A forecast row stores the "
    "Random Forest point estimate, the band, the period, and the error metric. Alerts "
    "and AI insight rows store generated notices. An intervention row stores the "
    "advising action taken after a student is flagged, including a financial or "
    "scholarship referral where that is the chosen action. Audit logs and login history "
    "reference the acting user. Surrogate student identifiers are what shared screens "
    "and exports are designed to show in place of a full personal name."
)
add_p("[Insert Figure 4. Entity Relationship Diagram]", italic=True, align="center", first_indent=0, space_before=6, space_after=6)

heading3("System Architecture")
body(
    "IntelliFlow is a Laravel model-view-controller application. The browser talks only "
    "to Apache. Routes pass through authentication, account-setup, and permission "
    "middleware before they reach a controller. Controllers stay thin. Scoring, "
    "forecasting, analytics, import, and report assembly live in services: the Random "
    "Forest implementation, the student-risk analysis, the forecast service, and the "
    "analytics service. Eloquent models persist to PostgreSQL. Blade templates render "
    "the pages, and charts are drawn in the browser. This split is what makes the "
    "model replaceable: a retrained forest or a revised program list is a service and "
    "data change, not a redesign of the screens. Reports are generated from the same "
    "stored scores the user already saw, so the PDF and the screen cannot drift apart."
)
add_p("[Insert Figure 5. System Architecture]", italic=True, align="center", first_indent=0, space_before=6, space_after=6)
body(
    "The Random Forest used for enrollment forecasting is an ensemble of regression "
    "trees trained on the observed series for the selected program or college. The "
    "service refuses to forecast from a single period. The attrition path uses the "
    "same family of method on student-level features and then cuts the score into "
    "risk bands. Feature importance is surfaced in the student view and in the "
    "subject-difficulty heatmap so an adviser can see which academic signals "
    "dominated the flag. Both paths are deterministic given the stored seed and the "
    "current table, which is what allows a prediction log to be re-checked."
)

# ----- 4.5 -----
heading2("4.5 Output and User Interface Design")
body(
    "The interface is a logged-in web application with a persistent sidebar. Modules "
    "are grouped the way the acceptance items name them: Dashboard, Data Management, "
    "AI Alerts, and Reports, together with Students, At-Risk, Forecasting, Analytics, "
    "and Settings. The visual language is a light campus layout with a clear primary "
    "action on each page. Charts use one job per view: summary, trends, programs, "
    "colleges, or retention. Risk is shown as a band first and as a number second, "
    "so a chair can scan a cohort before opening one student."
)
body(
    "The main outputs are the ones the instrument asked users to judge. The dashboard "
    "translates retention, dropouts, and shifting into charts and supports filters "
    "for term and program. The at-risk list categorizes students and opens a detail "
    "page with the reasons for the flag and the subject-difficulty heatmap. From that "
    "page the user prints or downloads the departmental academic referral and the "
    "risk-summary report. Exports are offered as PDF and as a spreadsheet. Validation "
    "messages appear when an upload is incomplete or a file is not in the expected "
    "shape, which is the behavior the reliability items describe. The SPR screen is "
    "a separate, narrower interface for the student profile and can be shared by a "
    "QR link without giving the student the staff menu."
)
body(
    "Security is visible in the interface as well as in the server. Unauthenticated "
    "visitors see only the public pages and the SPR link they were given. After login, "
    "menus and routes that the role cannot use are not offered. Shared analytical "
    "screens are designed around surrogate student identifiers. Profile and appearance "
    "settings stay on the signed-in account and do not change another user’s data."
)
add_p("[Insert Figure 6. Dashboard and Enrollment Dynamics]", italic=True, align="center", first_indent=0, space_before=6, space_after=2)
add_p("[Insert Figure 7. At-Risk List and Feature Explanation]", italic=True, align="center", first_indent=0, space_before=2, space_after=2)
add_p("[Insert Figure 8. Referral Form and Risk-Summary Export]", italic=True, align="center", first_indent=0, space_before=2, space_after=6)

# ----- 4.6 -----
heading2("4.6 Testing Results")
body(
    "After the modules above could be demonstrated end to end, the system was subjected "
    "to user-acceptance testing. The instrument is the IntelliFlow User Acceptance and "
    "ISO/IEC 25010 Evaluation form. It has three scored parts: respondent profile, "
    "thirty ISO/IEC 25010 product-quality items covering eight characteristics, and six "
    "TAM items covering perceived usefulness and perceived ease of use. A final "
    "open-ended part asks what works well, what should be improved before administrators "
    "and faculty rely on the system, and what the office still needs that the system "
    "does not do. Responses were encoded in the tally workbook IntelliFlow UAT Survey "
    "Tally v4. Names were optional under the Data Privacy Act; this chapter reports "
    "roles only."
)

heading3("Respondents and Administration")
body(
    "Respondents were selected purposively. They are the people who would rely on the "
    "system in CEIT: one college administrator, three faculty advisers or instructors, "
    "and one OASFA staff member. Five questionnaires were completed on 12 November 2026. "
    "Each respondent answered all 36 Likert items. All five used a laptop. No registrar "
    "personnel and no phone or desktop session is included. The sample is appropriate "
    "for a pilot acceptance test with the offices that own the workflow. It is too small "
    "for a generalization to the whole campus, and the results are reported as the "
    "judgment of these five users."
)
caption("Table 3. Distribution of Respondents by Role and Device")
add_table(
    ["Profile", "Category", "Frequency (f)", "Percentage (%)"],
    [
        ["Role", "CEIT Administrator", "1", "20.00"],
        ["Role", "CEIT Faculty Adviser / Instructor", "3", "60.00"],
        ["Role", "OASFA Personnel", "1", "20.00"],
        ["Role", "Registrar Personnel", "0", "0.00"],
        ["Role", "Total", "5", "100.00"],
        ["Device", "Laptop", "5", "100.00"],
        ["Device", "Desktop", "0", "0.00"],
        ["Device", "Phone", "0", "0.00"],
        ["Device", "Total", "5", "100.00"],
    ],
    [1.3, 3.0, 1.4, 1.3],
    left_cols={0, 1},
)
note("Note. Percentages are computed from N = 5 completed questionnaires. The tally sheet stored role shares as proportions; they are shown here as percentages.")

heading3("Scoring and Interpretation")
body(
    "Each item used a five-point scale: 5 Strongly Agree, 4 Agree, 3 Neutral, "
    "2 Disagree, and 1 Strongly Disagree. The weighted mean of an item is the sum of "
    "(rating × frequency) divided by the number of respondents. The characteristic "
    "mean is the unweighted mean of its item means, which is the figure reported by "
    "the tally. Verbal interpretation follows the scale in Table 4. A result from "
    "4.21 to 5.00 is Highly Acceptable."
)
caption("Table 4. Weighted-Mean Interpretation Scale")
add_table(
    ["Weighted Mean", "Scale", "Verbal Response", "Interpretation"],
    [
        ["4.21 – 5.00", "5", "Strongly Agree", "Highly Acceptable"],
        ["3.41 – 4.20", "4", "Agree", "Acceptable"],
        ["2.61 – 3.40", "3", "Neutral", "Moderately Acceptable"],
        ["1.81 – 2.60", "2", "Disagree", "Slightly Acceptable"],
        ["1.00 – 1.80", "1", "Strongly Disagree", "Not Acceptable"],
    ],
    [1.5, 1.0, 1.8, 1.7],
)

heading3("ISO/IEC 25010 Product Quality")
body(
    "Table 5 summarizes the eight product-quality characteristics. Every characteristic "
    "fell in the Strongly Agree band and is interpreted as Highly Acceptable. The "
    "ISO/IEC 25010 grand mean is 4.96 (SD = 0.07)."
)
caption("Table 5. Summary of ISO/IEC 25010 Product Quality Evaluation")
add_table(
    ["Characteristic", "Items", "Weighted Mean", "SD", "Verbal Response", "Interpretation"],
    [
        ["Functional Suitability", "5", "4.88", "0.18", "Strongly Agree", "Highly Acceptable"],
        ["Performance Efficiency", "4", "5.00", "0.00", "Strongly Agree", "Highly Acceptable"],
        ["Compatibility", "3", "4.93", "0.15", "Strongly Agree", "Highly Acceptable"],
        ["Usability", "5", "5.00", "0.00", "Strongly Agree", "Highly Acceptable"],
        ["Reliability", "3", "5.00", "0.00", "Strongly Agree", "Highly Acceptable"],
        ["Security", "4", "4.95", "0.11", "Strongly Agree", "Highly Acceptable"],
        ["Maintainability", "3", "4.93", "0.15", "Strongly Agree", "Highly Acceptable"],
        ["Portability", "3", "5.00", "0.00", "Strongly Agree", "Highly Acceptable"],
        ["ISO/IEC 25010 Grand Mean", "30", "4.96", "0.07", "Strongly Agree", "Highly Acceptable"],
    ],
    [1.85, 0.6, 0.85, 0.55, 1.15, 1.15],
    left_cols={0},
)
note("Note. Means are rounded to two decimals from the tally. The unrounded grand mean is 4.962.")

body(
    "Functional suitability obtained 4.88, the lowest characteristic mean, and the only "
    "one pulled down by a mixed item. Four of its five items were unanimous Strongly "
    "Agree: batch CSV and Excel parsing without data loss (FS1), dashboard charts of "
    "retention, dropout, and shifting (FS2), auto-filled referral forms and risk-summary "
    "reports (FS4), and feature-importance explanations (FS5). The Random Forest risk "
    "item (FS3) — that the model accurately generates mid-semester probabilities and "
    "High, Medium, and Low bands — received three Strongly Agree, one Agree, and one "
    "Neutral, for a weighted mean of 4.40 (SD = 0.89). The band is still Highly "
    "Acceptable, but it is the weakest single indicator in the instrument. Users "
    "accepted the flagging workflow more consistently than they accepted a claim of "
    "accuracy. That distinction matters. This survey measures perceived accuracy. "
    "It is not a hold-out test of the forest, and the Neutral response is a reason to "
    "keep the explanation beside the band rather than to present the band as a certainty."
)
body(
    "Performance efficiency, usability, reliability, and portability were unanimous. "
    "All five respondents strongly agreed that large files load in an acceptable time, "
    "that charts render quickly when filtered, that risk scoring does not introduce "
    "noticeable lag, and that PDF or Excel export finishes promptly (PE1–PE4, each "
    "mean = 5.00). The same unanimous result holds for a clean layout, logical "
    "navigation, readable heatmaps, clear validation messages, and first-time operation "
    "of upload and report generation (US1–US5). Reliability items on stability, "
    "graceful handling of bad files, and retention of records and prediction logs were "
    "also 5.00. Portability items — browser access on a computer or tablet, no extra "
    "installation, and opening saved reports from another authorized computer — were "
    "5.00. These four characteristics are the clearest result in the pilot: once a "
    "user is on a laptop browser, the system feels fast, stable, and learnable."
)
body(
    "Compatibility, security, and maintainability were Highly Acceptable with one "
    "Agree each. Browser behavior and cross-system report layout were unanimous "
    "(CO1 and CO3 = 5.00). Scaling across laptop resolutions (CO2) was 4.80. Role-based "
    "login, anonymization on upload, and the absence of a direct live-database "
    "connection were unanimous (SE1, SE2, and SE4 = 5.00). The use of surrogate "
    "identifiers such as STUDENT-2026-XXXX (SE3) was 4.80. Correcting a bad upload "
    "and updating curriculum codes or a retrained model were unanimous (MA1 and MA3 "
    "= 5.00). Tracing errors from prediction logs (MA2) was 4.80. No item received "
    "a Disagree or a Strongly Disagree."
)

caption("Table 6. Item Results for Indicators That Were Not Unanimous")
add_table(
    ["Code", "Indicator", "SA", "A", "N", "D", "SD", "WM", "Interpretation"],
    [
        ["FS3", "Random Forest mid-semester risk bands are accurate", "3", "1", "1", "0", "0", "4.40", "Highly Acceptable"],
        ["CO2", "The interface scales across laptop and desktop resolutions", "4", "1", "0", "0", "0", "4.80", "Highly Acceptable"],
        ["SE3", "Records are shown with anonymized surrogate IDs", "4", "1", "0", "0", "0", "4.80", "Highly Acceptable"],
        ["MA2", "Prediction logs make technical errors easy to trace", "4", "1", "0", "0", "0", "4.80", "Highly Acceptable"],
    ],
    [0.55, 3.15, 0.4, 0.4, 0.4, 0.4, 0.45, 0.5, 1.15],
    left_cols={1},
)
note("Note. The other 32 Likert items received a frequency of 5 for Strongly Agree and a weighted mean of 5.00. SA = Strongly Agree, A = Agree, N = Neutral, D = Disagree, SD = Strongly Disagree.")

heading3("Technology Acceptance")
body(
    "TAM results are reported separately because they answer a different question: "
    "not whether a quality characteristic is present, but whether these users would "
    "treat the system as useful and easy in their own work. Both constructs were "
    "unanimous."
)
caption("Table 7. Summary of Technology Acceptance Model Constructs")
add_table(
    ["Construct", "Items", "Weighted Mean", "SD", "Verbal Response", "Interpretation"],
    [
        ["Perceived Usefulness", "3", "5.00", "0.00", "Strongly Agree", "Highly Acceptable"],
        ["Perceived Ease of Use", "3", "5.00", "0.00", "Strongly Agree", "Highly Acceptable"],
        ["TAM Grand Mean", "6", "5.00", "0.00", "Strongly Agree", "Highly Acceptable"],
    ],
    [1.85, 0.6, 0.85, 0.55, 1.15, 1.15],
    left_cols={0},
)
body(
    "On usefulness, all five respondents strongly agreed that the system improves "
    "their productivity in reading enrollment and attrition trends (PU1), makes "
    "retention and dropout or shifting risk easier to monitor (PU2), and is useful "
    "for administrative and decision-support work (PU3). On ease of use, all five "
    "strongly agreed that interaction is clear (EU1), that the system does what "
    "they ask of it (EU2), and that they would use it again for a future enrollment "
    "and risk cycle (EU3). The TAM grand mean is 5.00. Within this pilot, acceptance "
    "is not the weak point. The open comments below are about what to add before "
    "daily reliance, not about rejecting the present system."
)

heading3("Overall Result")
caption("Table 8. Composite Acceptability of IntelliFlow")
add_table(
    ["Measure", "Items", "Weighted Mean", "SD", "Verbal Response", "Interpretation"],
    [
        ["ISO/IEC 25010 grand mean", "30", "4.96", "0.07", "Strongly Agree", "Highly Acceptable"],
        ["TAM grand mean", "6", "5.00", "0.00", "Strongly Agree", "Highly Acceptable"],
        ["Composite (10 dimensions)", "36", "4.97", "0.06", "Strongly Agree", "Highly Acceptable"],
    ],
    [2.2, 0.7, 0.9, 0.6, 1.2, 1.2],
    left_cols={0},
)
note("Note. The composite mean in the tally is 4.970 before rounding. All three figures fall in the 4.21–5.00 band.")
body(
    "The composite of the ten dimensions is 4.97 (SD = 0.06), verbally Strongly Agree "
    "and interpreted as Highly Acceptable. Dispersion is small because most items were "
    "unanimous. The standard deviation should not be read as proof of precision in a "
    "large population. With five respondents, a single Neutral answer moves an item "
    "mean by 0.40, which is exactly what happened on FS3."
)

heading3("Qualitative Feedback")
body(
    "Four of the five respondents wrote at least one open comment. The comments sort "
    "into what already works, what should be tightened before the college relies on "
    "the system, and what the office still has to do outside the present screens."
)
caption("Table 9. Open-Ended Feedback by Role")
add_table(
    ["Role", "What works well", "Improve before daily reliance", "Still needed"],
    [
        [
            "CEIT Administrator",
            "Risk-summary reports export promptly to PDF and Excel.",
            "—",
            "—",
        ],
        [
            "Faculty adviser",
            "The dashboard.",
            "The security of the login feature.",
            "A status tracker for student progress after an academic referral.",
        ],
        [
            "Faculty adviser",
            "Random Forest mid-semester predictions, feature importance, and subject-difficulty heatmaps explain why a student is flagged.",
            "—",
            "An email or SMS alert to the assigned adviser, and a remark box for advising notes.",
        ],
        [
            "OASFA personnel",
            "Batch CSV and Excel upload runs smoothly.",
            "—",
            "—",
        ],
        [
            "Faculty adviser",
            "No written comment.",
            "—",
            "—",
        ],
    ],
    [1.25, 1.9, 1.7, 1.85],
    left_cols={0, 1, 2, 3},
)
note("Note. Wording is preserved in substance from the tally. One faculty questionnaire contained Likert ratings only.")
body(
    "The written strengths line up with the perfect scores. The administrator confirmed "
    "the export path that PE4 and FS4 describe. OASFA confirmed the batch upload that "
    "FS1 and PE1 describe. One faculty adviser confirmed that feature importance and "
    "the heatmap make a high-risk flag explainable, which is FS5 and US3. The dashboard "
    "itself was named as the feature that works."
)
body(
    "The improvement requests are specific and should be treated as the backlog, not "
    "as a failed test. One faculty adviser asked that login security be strengthened "
    "before the college relies on the system, even though the four security items "
    "were rated Highly Acceptable (the lowest, SE3, at 4.80). The rating says the "
    "present controls are acceptable; the comment says they are not yet enough for "
    "unattended daily use. The same respondent asked for a status tracker after a "
    "referral is issued. Another adviser asked for an automatic email or SMS to the "
    "assigned adviser and for a remark box where advising notes can be logged. The "
    "intervention record exists in the design, but the pilot does not yet notify the "
    "adviser outside the application or show a running status after the referral "
    "leaves the screen. Those two comments describe the same gap: the system can "
    "flag and document, and it does not yet follow the student after the form is downloaded."
)

heading3("Discussion")
body(
    "Taken together, the pilot says that IntelliFlow is acceptable to the CEIT "
    "administrator, the faculty advisers who tried it, and the OASFA respondent. "
    "The grand means sit at the top of the interpretation scale. Usefulness and "
    "ease of use are not in doubt for these users: every TAM item is 5.00, including "
    "the intention to use the system again next term. Speed, stability, browser "
    "access, and learnability are likewise not in doubt. The design choices in "
    "Sections 4.4 and 4.5 — one job per analytics page, preview-before-commit import, "
    "and reports generated from the stored score — are the behaviors the respondents "
    "singled out in their own words."
)
body(
    "The result that should discipline the conclusion is FS3. A Highly Acceptable "
    "mean of 4.40 still contains a Neutral judgment on whether the Random Forest "
    "bands are accurate. Perceived acceptance of a decision-support screen is not "
    "evidence that the model’s probabilities are calibrated. For the defense and "
    "for any later deployment, the honest claim is that users can operate the "
    "scoring workflow and that they find the explanation useful, while predictive "
    "validity remains a separate evaluation that needs historical outcomes "
    "(who actually dropped or shifted) rather than a Likert item."
)
body(
    "Two limits bind the chapter. First, N = 5 from one college on one day, all on "
    "laptops, with no registrar respondent. A unanimous 5.00 is encouraging and "
    "fragile; the next office may not score the same way. Second, several quality "
    "items ask the user to judge an internal behavior (anonymization on upload, "
    "log traceability, model replacement). High agreement means the demonstration "
    "made those behaviors believable to the respondent. It does not replace a "
    "technical audit. Within those limits, the acceptance test supports the "
    "statement that the implemented system meets the functional and quality "
    "expectations of the users who will operate it, and it identifies the next "
    "work clearly: treat the risk band as advice, tighten login to the point "
    "faculty will trust it unattended, and add adviser notification plus a "
    "post-referral status and remark so the intervention does not end when the PDF is saved."
)

doc.save(OUT)
print(OUT)
