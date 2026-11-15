# CHAPTER V
# SUMMARY, CONCLUSION, AND RECOMMENDATIONS

This chapter presents the summary of findings, conclusions, and recommendations derived from the planning, development, implementation, and evaluation of **IntelliFlow: CEIT Student Attrition Prediction and Enrollment Visualization System Using Random Forest**. The structure directly follows the institutional evaluation rubric: Section 5.1 reports the empirical data and metrics established during the user-acceptance evaluation; Section 5.2 judges what those results signify for the decision-support problems of the College of Engineering and Information Technology (CEIT); and Section 5.3 assigns specific, actionable directives to named institutional stakeholders and future researchers.

---

## 5.1 Summary of Findings

The findings of this study are drawn from the execution of the system architecture and the thirty-six Likert and open-ended evaluation items administered to authorized personnel across CEIT and student support offices.

### 5.1.1 Data Ingestion, Processing, and Enrollment Dynamics Visualization
1. Batch file processing for CSV and Excel enrollment datasets (FS1) and Student Personal Record (SPR) uploads achieved a unanimous weighted mean of 5.00 ($SD = 0.00$), validating that tabular student datasets were parsed, scrubbed of unconfirmed rows, and stored without data loss.
2. The enrollment dynamics dashboard (FS2), which visually translates retention, dropouts, and inbound or outbound student shifting across academic terms and programs, scored a unanimous weighted mean of 5.00 ($SD = 0.00$).
3. Performance efficiency during batch file uploads (PE1) and real-time trend chart rendering upon query filtering (PE2) both achieved unanimous ratings of 5.00 ($SD = 0.00$), confirming that data transformations completed without noticeable system latency.

### 5.1.2 Random Forest Attrition Risk Scoring and Decision Support
1. The Random Forest classification module (FS3), tasked with generating mid-semester student attrition probabilities and categorizing students into High, Medium, and Low risk bands, garnered three "Strongly Agree," one "Agree," and one "Neutral" rating, resulting in a weighted mean of 4.40 ($SD = 0.89$). This was the only non-unanimous functional suitability item and the only statement in the entire thirty-six-item instrument to receive a neutral mark.
2. The feature-importance explainer (FS5) and visual subject-difficulty heatmaps (US3) each obtained unanimous weighted means of 5.00 ($SD = 0.00$), substantiating that the specific academic drivers underlying a flagged risk status (GPA, attendance percentage, and failed subjects) were clearly communicated to evaluators.
3. Automated generation of administrative intervention instruments (FS4), specifically the CEIT Departmental Academic Referral Form and risk-summary reports in PDF and spreadsheet formats, scored a unanimous weighted mean of 5.00 ($SD = 0.00$), supported by prompt export execution times (PE4: $WM = 5.00, SD = 0.00$).

### 5.1.3 Software Product Quality (ISO/IEC 25010) and Technology Acceptance (TAM)
1. Across the eight product-quality characteristics of ISO/IEC 25010 encompassing thirty statements, IntelliFlow achieved an overall grand mean of 4.96 ($SD = 0.07$), interpreted as Highly Acceptable:
   - **Performance Efficiency:** 4 items, $WM = 5.00, SD = 0.00$ (Highly Acceptable)
   - **Usability:** 5 items, $WM = 5.00, SD = 0.00$ (Highly Acceptable)
   - **Reliability:** 3 items, $WM = 5.00, SD = 0.00$ (Highly Acceptable)
   - **Portability:** 3 items, $WM = 5.00, SD = 0.00$ (Highly Acceptable)
   - **Security:** 4 items, $WM = 4.95, SD = 0.11$ (Highly Acceptable; SE3 regarding surrogate student IDs: $WM = 4.80, SD = 0.45$)
   - **Compatibility:** 3 items, $WM = 4.93, SD = 0.15$ (Highly Acceptable; CO2 regarding screen scaling: $WM = 4.80, SD = 0.45$)
   - **Maintainability:** 3 items, $WM = 4.93, SD = 0.15$ (Highly Acceptable; MA2 regarding error log tracing: $WM = 4.80, SD = 0.45$)
   - **Functional Suitability:** 5 items, $WM = 4.88, SD = 0.18$ (Highly Acceptable; lowest characteristic mean due to FS3)
2. In the Technology Acceptance Model (TAM), evaluators unanimously marked all six items under Perceived Usefulness (PU1–PU3: $WM = 5.00, SD = 0.00$) and Perceived Ease of Use (EU1–EU3: $WM = 5.00, SD = 0.00$) with "Strongly Agree," establishing a TAM grand mean of 5.00 ($SD = 0.00$).
3. The composite acceptability grand mean across all ten dimensions (thirty-six items) reached 4.97 ($SD = 0.06$), confirming high institutional receptivity.

### 5.1.4 Evaluator Demographics and Qualitative Feedback
1. The acceptance test was completed on 12 November 2026 by five purposively selected institutional raters ($N = 5$): one CEIT Administrator (20.00%), three CEIT Faculty Advisers or Instructors (60.00%), and one OASFA Personnel (20.00%). No questionnaires were returned by the Registrar's Office (0.00%). All five evaluators executed the testing protocol exclusively on laptops (100.00%).
2. Qualitative remarks confirmed operational satisfaction with report exports, dashboard responsiveness, and the clarity of the subject-difficulty heatmap. However, evaluators explicitly documented three critical post-intervention gaps: the absence of an automated email or SMS notification dispatched directly to assigned faculty advisers, the lack of an ongoing status tracking mechanism for students following referral generation, and the absence of a remark log to record advisory dialogue.

---

## 5.2 Conclusions

Based on the empirical findings, the researchers formulate the following conclusions regarding the core institutional problems identified in the study:

1. **Resolution of Enrollment Data Fragmentation:** The system successfully eliminates the manual friction and data loss previously associated with reconciling heterogeneous office spreadsheets. By enforcing a pre-commit validation pipeline and browser-based data management, IntelliFlow establishes an operational workflow where departmental records can be maintained cleanly by college staff without requiring direct database engineering expertise.
2. **Operational Utility of Decision-Support Analytics:** The unanimous acceptance of the visualization dashboard proves that translating raw tabular registries into graphical retention, dropout, and shifting metrics fulfills a long-standing administrative need for CEIT leadership. Department chairs and administrators can interpret longitudinal student trajectories during academic planning sessions without manually compiling figures from multi-year records.
3. **Differentiation Between Risk Advisory and Statistical Calibration:** While evaluators found the mid-semester risk banding and feature-importance explanations highly practical for intervention, the presence of a neutral rating on prediction accuracy indicates that users appropriately perceive the Random Forest module as an early-warning guide rather than an infallible forecasting authority. The model successfully isolates which academic factors (such as attendance and GPA degradation) warrant intervention, but its scores serve as diagnostic advice rather than deterministic proof of impending student dropout.
4. **Adherence to Institutional Data Privacy and Usability Mandates:** The system satisfies the operational requirements of Republic Act No. 10173 (Data Privacy Act of 2012) through surrogate student identifiers, strict role segregation, and local file batching that isolates sensitive student records from live external networks. Furthermore, the perfect usability and learnability scores confirm that faculty advisers and administrators can navigate the complete advising cycle—from file upload to referral printing—in a single sitting with minimal orientation.
5. **Boundary Limitations of the Deployment Pilot:** The high acceptance scores establish proof-of-concept readiness for day-to-day office workflows within CEIT. However, because the trial was completed exclusively on laptops by five respondents without direct participation from the Registrar's Office, the system's cross-departmental integration and autonomous operation under unattended production conditions remain unverified.

---

## 5.3 Recommendations

To address the limitations, qualitative feedback, and structural gaps established during the pilot evaluation, the following recommendations are directed to specific institutional stakeholders and future researchers:

### 5.3.1 For the CEIT Dean and Department Chairpersons
1. **The CEIT Dean** should officially sanction the integration of IntelliFlow into the college's mid-semester retention review meetings, mandating that department chairs utilize the enrollment-dynamics charts and at-risk summary reports during resource allocation, section scheduling, and faculty loading deliberations.
2. **The CEIT Department Chairpersons (IT, CpE, and EE)** should require faculty advisers to generate the CEIT Departmental Academic Referral Form for every student categorized in the "High" and "Critical" risk tiers within one week following the release of preliminary midterm evaluations.

### 5.3.2 For CEIT Faculty Advisers and Instructors
1. **The CEIT Faculty Advisers** must treat the Random Forest risk bands and subject-difficulty heatmaps as consultative early-warning indicators, combining model outputs with direct student mentoring sessions rather than using the automated score as the sole basis for academic sanction or advising recommendations.
2. **The Designated Academic Advisers** should record and file post-referral counseling notes manually in the departmental repository until the in-system status-tracking module is fully deployed.

### 5.3.3 For the Office of Admission, Scholarship, and Financial Assistance (OASFA) Personnel
1. **The OASFA Personnel** should cross-reference IntelliFlow’s at-risk cohort lists with the student scholarship registry at the middle of each semester, proactively identifying financially vulnerable students whose academic decline risks the revocation of their educational grants or stipends.

### 5.3.4 For the University Registrar
1. **The University Registrar** should collaborate with CEIT by establishing a standardized, anonymized term-end batch export protocol for enrollment records, ensuring that future dataset updates can be ingested into IntelliFlow without requiring manual column formatting or data cleaning by faculty advisers.

### 5.3.5 For the CEIT Administrative and Technical Staff
1. **The CEIT Administrative Staff** must implement a scheduled weekly database backup protocol on an isolated offline storage drive to guarantee data durability and prevent data corruption across semester transitions.
2. **The Campus Network Administrator** should strengthen the application’s server environment by provisioning HTTPS encryption and configuring a reverse proxy before deploying IntelliFlow across the wider campus intranet.

### 5.3.6 For the Next Group of Student Researchers (Future Work)
1. **The Next Group of Student Researchers** must implement an automated **Email and SMS notification pipeline** that dispatches immediate, secure alerts to assigned faculty advisers the moment a student is classified into the High or Critical attrition tier.
2. **The Next Group of Student Researchers** should design and embed a **Post-Referral Status Tracker and Remark Ledger**, providing a persistent audit trail where faculty advisers can document counseling sessions, track remediation milestones, and update student retention outcomes directly within the database.
3. **The Next Group of Student Researchers** should expand user-acceptance testing to include personnel from the University Registrar's Office, testing the web application across desktop workstations and mobile browsers to validate responsive cross-device performance.
4. **The Next Group of Student Researchers** must conduct a multi-term **longitudinal predictive validity study**, comparing the Random Forest model's mid-semester probability outputs against actual end-of-term dropout and retention registries to compute empirical Confusion Matrix metrics (Precision, Recall, F1-Score, and ROC-AUC).
