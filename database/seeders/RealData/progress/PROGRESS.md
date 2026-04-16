# Real Data — Manual Transcription Progress

Every CSV row is inspected by hand. Columns map to the `ratings` schema with
per-facet scores inferred from the Arabic review text. Nothing is ever
script-generated — this file + COMPANIES.md are the authoritative checklist.

## Files

| # | File | Lines | Last row read | Status |
|---|------|-------|---------------|--------|
| 1 | `استبيان جهات التدريب لدفعه ٤٤  (Responses) - Form responses 1.csv` | 55 | — | not started |
| 2 | `تجارب التدريب - كليه الحاسب - Original Form responses.csv` | 494 | — | not started |
| 3 | `تجارب التدريب - كليه الحاسب - Original Form responses (1).csv` | 494 | — | skipped (byte-identical to #2) |
| 4 | `تجارب التدريب 2023 - ردود النموذج 1.csv` | 46 | — | not started |
| 5 | `تدريب تعاوني - COOP - جهات تدريب تعاوني ـ COOP.csv` | 121 | — | not started |
| 6 | `تدريب تعاوني - COOP .csv` | 319 | — | skipped (same data as #5 in TSV layout) |
| 7 | `جمع تجارب التدريب الصيفي لكلية الحاسب ونظم المعلومات 1443هـ (الردود) - ردود النموذج 1.csv` | 79 | — | not started |
| 8 | `🖥️ استبيان تجارب التدريب التعاوني – كلية الحاسبات(1445).csv` | 56 | — | not started |

## Reading order

Chronological, so when the same company shows up twice the earlier entry
gets created first and later CSVs merge onto it:

1. File #5 (COOP directory — seed companies with type/website/description)
2. File #2 (ComputerCollege, 2019–2022)
3. File #7 (Summer1443, Aug 2022)
4. File #4 (TrainingExperiences 2023, Aug 2023)
5. File #1 (Form44, 2025 cohort)
6. File #8 (Coop1445, 2026 cohort)

## Skipped row policy

- File #4 rows where `نوع التدريب` = `عن بعد (دورات)` are self-study on
  Udemy/Coursera — not company internships. Skipped.
- Any row where the company name is blank or generic like `-` / `لا يوجد`
  and no review text to recover from. Noted below per file.

## Per-file notes

(filled in as each file is processed)
