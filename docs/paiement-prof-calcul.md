# Paiement prof — how the automatic teacher-pay calculation works

This document explains, end to end, how the GLS CRM computes what a teacher
should be paid for one group over one month. It is self-contained: someone
who has never seen the code (or another AI assistant) should be able to
understand the rules and redo any calculation by hand.

> **The calculation writes nothing.** It is a read-only proposal. No money
> moves, no caisse (till) is touched. When the operator agrees with the
> amount, they click « Créer la dépense », which opens the ordinary
> « Paiement prof » expense form pre-filled with the total. The real money
> movement then goes through the normal expense flow (approval, till balance
> check, audit journal).

---

## 1. Where it lives

| What | Where |
|---|---|
| Screen | `/backoffice/paiement-prof` (« Paiement prof »), permission `prof-payments.calculate` (the 5 management roles + super-admin) |
| Teacher's pay configuration | Employee record → tab « Paiement prof » |
| Reads the configuration | `app/Domain/Payroll/Support/ConfigurationPaieEnseignant.php` |
| Computes the month window | `app/Domain/Payroll/Support/MoisDeGroupe.php` |
| Decides which attendance pays | `app/Domain/Payroll/Support/StatutPresencePaie.php` |
| Formula — GLS & win-win | `app/Domain/Payroll/Actions/CalculerPaiementProfParSeance.php` |
| Formula — hourly | `app/Domain/Payroll/Actions/CalculerPaiementProfHoraire.php` |
| Gathers the data and runs the formula | `app/Domain/Payroll/Queries/GetPaiementProfCalcul.php` |
| Tests | `tests/Feature/Backoffice/Payroll/` |

---

## 2. The three pay systems

The pay mode is set **on the teacher (the employee)**, never on the group.
A teacher uses the same mode in every group they teach.

| Mode (stored value) | Label on screen | What is configured on the employee | Paid for |
|---|---|---|---|
| `horaire` | « Par heure » | `taux_horaire_prof` — one hourly rate (DH/hour) | the **hours** taught |
| `gls` | « Système GLS » | `montant_par_etudiant_prof` — one fixed amount per student per month | each student's **attendance** |
| `win_win` | « Système win-win » | table `enseignant_taux_mensuels` — one amount per student **for each month** (e.g. Sept 400, Oct 420, Nov 450 … up to 600) | each student's **attendance** |

**GLS and win-win use exactly the same formula.** The only difference is
where the "amount per student" (the *rate*) comes from:

- GLS → one fixed number, the same every month;
- win-win → the number entered for **that specific month**.

### Configuration errors are refused, never guessed

The calculation stops and names the problem (instead of paying 0 or guessing)
when:

- the teacher has no pay mode;
- mode `horaire` without an hourly rate, or mode `gls` without an amount per
  student;
- mode `win_win` with **no line for the requested month** — the system never
  borrows the previous/next month's amount, and never falls back on a GLS
  rate.

---

## 3. Inputs common to every mode

### 3.1 The group and the teacher

The operator picks a **group**, then a **month**, then a **teacher**. The
teacher list = every teacher ever assigned to that group. The one who taught
the most sessions that month is pre-selected.

The calculation only counts **that teacher's own sessions**
(`seances.enseignant_id`). A substitute teacher is paid separately with their
own calculation. A session done with **no teacher recorded** pays nobody; the
screen warns about it and links to the session so it can be re-assigned.

### 3.2 The "month" is the group's month, not the calendar month

A pay month starts on **the day the group started** (`groups.date_debut_formation`).

- Group started on 07/09/2026 → « Septembre 2026 » = **07/09 → 06/10**,
  « Octobre 2026 » = 07/10 → 06/11, etc.
- The anchor day is capped at **28** (a group started on the 31st uses the
  28th, so February always exists).
- If the group has no start date → plain calendar month (1st → last day), and
  the screen says so.

For win-win, the monthly rate used is the one of the calendar month in which
the window **starts** (window 07/09 → 06/10 uses the September rate).

### 3.3 Which sessions count

Only sessions with status **« Effectuée »** (actually held) inside the month
window, taught by the chosen teacher. Planned or cancelled sessions count for
nothing.

### 3.4 Which attendance pays (GLS / win-win)

**Only « Présent » pays.** « Absent » pays nothing. The old statuses
« Retard » and « Justifié » can no longer be entered (the roll call only has
Présent / Absent); the few historical rows that still carry them are treated
as **not paid**.

---

## 4. Formula — GLS and win-win (per session, proportional)

```
divisor              = min(number of sessions held in the month, 22)
amount for a student = rate × (student's « Présent » count) ÷ divisor
teacher total        = sum of every student's amount
```

- **rate** = `montant_par_etudiant_prof` (GLS) or the month's amount (win-win).
- **number of sessions held** = the teacher's « Effectuée » sessions in the
  window.
- **22 is a cap on the divisor**, not a minimum:
  - a short month (e.g. 18 sessions) divides by its real 18 → a student present
    every time is worth exactly the full rate; the teacher is not penalised
    for a short month (holidays etc.);
  - a heavy month (e.g. 25 sessions with make-up classes) still divides by 22
    → extra sessions earn extra money; a student present 25 times is worth
    more than the rate. This is intentional.
- Every attendance counts: there is **no threshold** and **no weekly
  bucket** (an older "4 weeks, minimum 3 days per week" model was removed on
  22/09/2026).
- No sessions in the month → everything is 0 (never a division by zero).

### Rounding

Each student's amount is computed from the **full rate** and rounded to 2
decimals **once**:

```
round(rate × presences ÷ divisor, 2)
```

It is *not* `round(rate ÷ divisor, 2) × presences`. Example: 500 ÷ 22 =
22.7272…; using the rounded 22.73 × 22 would give 500.06 instead of 500.00.
The screen still shows the "amount per session" (22.73) for information, but
the product uses the exact value. The teacher total is the sum of the
already-rounded student lines.

### Manual adjustment

On the result table, the operator can type a different amount for any
student. That value **replaces** the computed amount for that student, and
the total is recomputed. Adjustments are not stored anywhere; they only
change the total carried to the expense form.

### Worked examples (rate = 500 DH per student)

**A. Normal month — 22 sessions held** → divisor 22

| Student | Présent | Calculation | Amount |
|---|---|---|---|
| Amina | 22 | 500 × 22 ÷ 22 | 500.00 |
| Youssef | 18 | 500 × 18 ÷ 22 | 409.09 |
| Salma | 11 | 500 × 11 ÷ 22 | 250.00 |
| Omar | 0 | 500 × 0 ÷ 22 | 0.00 |
| **Total** | | | **1 159.09 DH** |

**B. Short month — 18 sessions held** → divisor 18 (below the cap)

| Student | Présent | Calculation | Amount |
|---|---|---|---|
| Amina | 18 | 500 × 18 ÷ 18 | 500.00 |
| Youssef | 15 | 500 × 15 ÷ 18 | 416.67 |
| **Total** | | | **916.67 DH** |

**C. Heavy month — 25 sessions held** → divisor capped at 22

| Student | Présent | Calculation | Amount |
|---|---|---|---|
| Amina | 25 | 500 × 25 ÷ 22 | 568.18 |
| Youssef | 20 | 500 × 20 ÷ 22 | 454.55 |
| **Total** | | | **1 022.73 DH** |

**D. Win-win** — teacher configured with September = 400, October = 420.
Same formula; only the rate changes with the month.

| Month | Sessions | Student present | Calculation | Amount |
|---|---|---|---|---|
| Septembre | 20 | 20 | 400 × 20 ÷ 20 | 400.00 |
| Octobre | 22 | 19 | 420 × 19 ÷ 22 | 362.73 |
| Novembre | — | — | no November line on the teacher | **refused**: "No win-win amount is set for novembre 2026" |

---

## 5. Formula — hourly (« Par heure »)

```
teacher total = hourly rate × hours
```

There are **no per-student lines**: the teacher is paid for time taught,
whatever the attendance.

How the hours are obtained:

1. The server measures the **usual length of a session** for the group that
   month (the most frequent `heure_debut → heure_fin` among held sessions,
   e.g. 10:00 → 12:00 = 2 h).
2. The form pre-fills « Durée par séance » with it, and computes
   `hours = session length × number of sessions this teacher held`.
3. The operator can overwrite either field (e.g. one session was shortened).
   Once the total hours are typed by hand, the automatic multiplication stops
   overwriting them.

The server also computes the real sum of each held session's duration
(`heuresEffectuees`) and lists sessions whose time is missing or inverted
(end ≤ start) — those count as 0 h and are flagged so someone fixes them.

### Worked example

Rate 60 DH/h, sessions of 2 h, 12 sessions held:

```
hours = 2 × 12 = 24 h
total = 60 × 24 = 1 440.00 DH
```

If one session lasted only 1.5 h, the operator types 23.5 h →
60 × 23.5 = 1 410.00 DH.

---

## 6. From the result to the actual payment

« Créer la dépense » opens the Dépenses screen, « Paiement prof » tab, with
the form pre-filled:

- type « Paiement prof », the group, the teacher;
- **montant** = the displayed total (adjustments included);
- **période** = the month window (e.g. 07/09/2026 → 06/10/2026);
- description = « Teacher – Group – Month ».

From there it is an ordinary expense: it is created « En attente » if expense
approval is on, it is debited from the till only when approved, and the till
balance check applies. The calculation screen itself never records anything.

---

## 7. Summary in one table

| | Horaire | GLS | Win-win |
|---|---|---|---|
| Rate source | employee's hourly rate | employee's fixed amount per student | employee's amount **for this month** |
| Unit paid | hour | student attendance | student attendance |
| Formula | rate × hours | rate × présences ÷ min(sessions, 22) per student, summed | same as GLS |
| Uses attendance? | no | yes, « Présent » only | yes, « Présent » only |
| Month | group-anchored window | group-anchored window | group-anchored window; rate of the window's starting month |
| Missing config | refused, named | refused, named | refused if the month has no line |
| Manual override | hours field | per-student amount | per-student amount |
| Writes money? | no | no | no |
