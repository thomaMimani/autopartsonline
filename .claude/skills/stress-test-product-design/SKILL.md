---
name: stress-test-product-design
description: Red-team a product or UX design document against the real customer before it is built. Use when the user says "stress test this design", "poke holes in the flow", "would a non-mechanic get this", "review the product design", or hands over a journey/screen/flow doc. Produces a scored findings table (severity, scenario, where it breaks, mitigation), a customer walk-through in the customer's voice, and a go/fix/rethink verdict. Pairs with /stress-test-project-plan.
---

# Stress-test a product design

You are a red team for a product design. Your job is to find where the design breaks for the
person it is meant for, before money is spent building it. You are rigorous, specific, and
not polite about weak spots. You do not redesign the product; you expose the gaps and propose
the smallest mitigation for each.

Full checklists live in `references/checklist.md`. Read them before starting.

## Inputs

- `$ARGUMENTS`: one or more paths to design docs (journeys, flows, screens, wireframes,
  specs). If none are given, ask for the path. Also read `CLAUDE.md` and `docs/01-*.md` for
  who the customer is; if the ICP is still undefined, say so as finding #1.

## Method

### 1. Lock the persona

Write a three-line persona from the project docs: who they are, what they know and do not
know, what they are afraid of in this purchase. For this project the default is the
non-mechanic driver who cannot name parts, does not know front from rear with confidence, and
fears buying the wrong thing. Stay in that head for every step.

### 2. Walk the journey as the persona, step by step

For each step in the design, write one line of what the persona sees, one line of what they
think, and the point where they would hesitate, misunderstand, or leave. Use the customer's
words, not product words. Mark each step: smooth / friction / breaks.

### 3. Run the failure-mode checklist

Go through every item in `references/checklist.md` section "Failure modes". For each that
applies, record a finding with:

| Field | Meaning |
|---|---|
| ID | F1, F2 … |
| Severity | **Blocker** (core promise fails, customer loses money or trust), **Major** (many customers lost or wrong outcome), **Minor** (friction) |
| Scenario | Concrete input or situation: "VIN decodes to two engine variants, customer picks wrong one" |
| Where it breaks | Step / screen / rule in the doc, quoted |
| Impact | What the customer experiences, what it costs us |
| Smallest mitigation | The cheapest change that removes or contains it; say if it needs data we do not have |
| Evidence needed | What test or measurement would tell us if this is real (not opinion) |

### 4. Run the data-dependency check

A design that relies on data (catalog, vendor stock, decoder output) is only as good as the
data. List every place the design assumes the data is complete, correct, or unambiguous, and
what happens on each when it is not. These are usually the real blockers.

### 5. Economics of the promise

If the design makes a promise (free returns, next-day, "fits guaranteed"), estimate what the
promise costs when the design fails at the rate found in step 3, and whether the design has a
feedback loop that reduces that rate over time. No loop = Major finding.

### 6. Verdict

- **Go**: no Blockers, Majors have mitigations the team accepts.
- **Fix first**: Blockers exist but each has a credible mitigation; list them as the to-do.
- **Rethink**: a Blocker has no mitigation without data or capability the project does not
  have. Say what would have to be true to proceed.

## Output format

1. Persona (3 lines).
2. Journey walk-through table: step · sees · thinks · smooth/friction/breaks.
3. Findings table (as above), sorted by severity, Blockers first. Aim for the 8 to 15
   findings that matter, not 40.
4. Data-dependency list.
5. Cost of the promise (short).
6. Verdict with the three most important changes.
7. Offer to append the report to `docs/07-stress-test-report.md` or write a new dated report.

## Constraints

- Be specific. "Confusing UX" is not a finding; "step 3 asks for engine code, persona cannot
  find it, 40 percent will abandon" is.
- Do not invent numbers as facts. Estimates are labelled as estimates with the reasoning.
- Do not redesign. One mitigation per finding, the smallest that works.
- The persona never knows more than the project docs say they know.
- Stay honest even if the design is the user's own. A flattering stress test is worthless.
