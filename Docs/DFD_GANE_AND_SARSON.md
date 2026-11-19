# Data Flow Diagrams — Gane & Sarson Notation

Panel requirement: **Use Gane and Sarson for your DFD.**

## Notation (what to draw)

| Symbol | Meaning | Shape |
|---|---|---|
| External entity | Person / office outside the system | **Square / sharp rectangle** |
| Process | Work done by the system | **Rounded rectangle** with process number (1.0, 2.0…) |
| Data store | Stored data (database / file) | **Open rectangle** (top, bottom, right; left open with a second vertical line) |
| Data flow | Movement of data | **Labeled arrow** |

> Do **not** use circles for processes. Circles are **Yourdon / DeMarco**. Your panel asked for **Gane & Sarson**.

## Figures generated for the manuscript

| File | Diagram |
|---|---|
| `Docs/Figure_DFD_Context_GaneSarson.png` | Context diagram (system as process **0**) |
| `Docs/Figure_DFD_Level0_GaneSarson.png` | Level 0 DFD (processes 1.0–6.0) |

Regenerate anytime:

```bash
python Docs/generate_dfd_gane_sarson.py
```

## Level 0 processes (this system)

1. **1.0 Login & Authorization** — credentials in; session / denial out; audit to D4  
2. **2.0 Data Management** — CSV/Excel import, student records, SPR forms → D1 / D2  
3. **3.0 Analytics & Reporting** — KPIs, charts, retention views from D1  
4. **4.0 Forecasting** — historical series → predicted enrollment → D3  
5. **5.0 Decision Support** — insights, what-if, alerts → Decision Maker; store in D3  
6. **6.0 Administration** — users, roles, theme, academic refs → D2 / D4  

## Data stores

- **D1** Student & Enrollment Data  
- **D2** Academic Refs & SPR  
- **D3** Forecasts & Insights / Alerts  
- **D4** Audit Logs  

## How to cite in Chapter IV

> Figure X. Context Diagram of the Enrollment Analytics and Decision Support System using Gane and Sarson notation.  
> Figure Y. Level 0 Data Flow Diagram using Gane and Sarson notation.

Replace any older DFD that shows **circular** processes with these figures.
