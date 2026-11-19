# Panelist Concerns — Response & Evidence

## 1. Improve UI / make it more user-friendly

**Done in the live system**

- Clearer page headers, colored KPI tiles, Filter (teal) / Clear (amber) actions  
- At-risk meters and SPR chips for quick scanning  
- Import page redesigned as **3 steps** (Prepare → Validate → Confirm) with progress labels and mapped-column preview  

**How to demo:** Dashboard → Student Records → Import → At-risk student report  

---

## 2. Scalable / dynamic Excel upload

**Done**

| Capability | Behavior |
|---|---|
| File size | Up to **50 MB** (CSV / .xlsx) |
| Column mapping | Header aliases (e.g. `studid` → `student_id`, `sy` → `academic_year`) |
| Dynamic terms | Missing **academic year / semester** in the file are **auto-created** |
| Large preview | Valid rows stored in a **temp file** (not session) so big extracts do not blow PHP session size |
| Commit | **Chunked insert** (400 rows); duplicates skipped; enrollment projection rebuilt once |
| Feedback | Validation score, valid/invalid counts, first 200 row errors |

**How to demo:** Import `CEIT_system_master.csv` (~13k rows) via Student Records → Import  

---

## 3. Use Gane and Sarson for DFD

**Correct notation (Gane & Sarson)**

- External entity → **square**  
- Process → **rounded rectangle** (not a circle)  
- Data store → **open rectangle**  
- Data flow → **labeled arrow**  

**Manuscript figures (use these)**

- `Docs/Figure_DFD_Context_GaneSarson.png` — Context diagram  
- `Docs/Figure_DFD_Level0_GaneSarson.png` — Level 0 DFD  
- Guide: `Docs/DFD_GANE_AND_SARSON.md`  

**Regenerate**

```bash
python Docs/generate_dfd_gane_sarson.py
```

> Replace any older DFD that uses **circles** for processes (Yourdon/DeMarco). The panel asked for **Gane & Sarson**.
