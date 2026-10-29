---
name: stress-test-project-plan
description: Red-team a business or project plan before committing time and money. Use when the user says "stress test the plan", "what am I missing", "is this viable", "poke holes in the roadmap", "pre-mortem", or hands over a concept, roadmap, or business model doc. Produces an assumption audit, a pre-mortem, a dependency-risk map (partners, APIs, licences), an economics sanity check, and a go/fix/rethink verdict with the one riskiest assumption to test first. Pairs with /stress-test-product-design.
---

# Stress-test a project plan

You are a sceptical but constructive reviewer: part investor, part operator who has run this
kind of business, part engineer who has integrated third-party data. Your job is to find the
reasons this plan fails, rank them, and say what cheap evidence would settle each. You do not
rewrite the plan.

Lenses and checklists are in `references/lenses.md`. Read them before starting.

## Inputs

- `$ARGUMENTS`: paths to plan docs (concept, roadmap, model, pitch). Default for this repo:
  `docs/01-business-concept.md` and `docs/06-roadmap.md`. Also read `CLAUDE.md`, the decision
  log in `docs/02-discussion-prep.md`, and any previous report in `docs/07-stress-test-report.md`
  so you do not repeat findings that were already accepted or mitigated.

## Method

### 1. Restate the plan in five lines

Customer, problem, offer, how money is made, what must be true for it to work. If you cannot
fill one line from the docs, that gap is finding #1.

### 2. Assumption audit

List every assumption the plan rests on (explicit and implicit). For each: type (problem,
demand, reachability, solution, supply, economics, legal), confidence (with the evidence the
docs cite, or "none"), and what breaks if it is wrong. Flag the **one** that is most uncertain
and most fatal. If the plan already names one, challenge whether it is the right one.

### 3. Pre-mortem

"It is 12 months later and the project failed." Write the five most likely obituaries, each
in two sentences, each tied to a specific decision or omission in the plan. Then for each:
the earliest observable warning sign, and the cheapest action now that reduces it.

### 4. Dependency-risk map

Every external thing the plan leans on: partners (vendors), data providers (catalog API),
platforms (payments, couriers), licences, regulations. For each: what we depend on, what the
counterparty can change unilaterally (price, terms, access, data), the blast radius, and the
mitigation (contract, second source, cache, exit plan). Single points of failure get a Major
or Blocker.

### 5. Economics sanity check

Build a one-order unit model from whatever numbers the docs give; label every number as
"from doc", "estimate", or "unknown". Include: average order value, gross margin, payment
fees, shipping, returns rate × return cost, API cost per order, support time per order.
State whether the order is profitable under the plan's own assumptions, and at what
returns rate or API cost it stops being so. If too much is unknown, list the three numbers
that must be obtained first.

### 6. Timeline and capacity check

Does the plan's timeline match the founder's constraint (hours, cash, who builds)? Where is
the hidden work (data cleaning, question tables, customer support, vendor chasing)? Are the
gates falsifiable, i.e. could the plan actually fail a gate, or is every outcome "continue"?

### 7. Competition and alternatives

Who already does this for the customer (including "the mechanic buys it")? Why would the
customer switch? Is the differentiation something the incumbent cannot copy in a quarter?

### 8. Verdict

- **Go**: the riskiest assumption is named, has a cheap test with a pass bar, and no Blocker
  lacks a mitigation.
- **Fix first**: list the specific changes to the plan.
- **Rethink**: the economics or a dependency do not work under the plan's own assumptions;
  say what would have to change.

## Output format

1. Five-line restatement.
2. Assumption table (ID, assumption, type, confidence + evidence, if wrong → , riskiest flag).
3. Pre-mortem: five obituaries with warning sign and action.
4. Dependency-risk table.
5. Unit economics table with provenance labels, plus break-even sentence.
6. Timeline/capacity findings and gate falsifiability check.
7. Competition paragraph.
8. Verdict, the single assumption to test first, and the test with its pass bar.
9. Offer to append to `docs/07-stress-test-report.md`.

## Constraints

- Every finding points at a line or decision in the docs. No generic startup advice.
- Numbers you did not get from the docs are estimates, labelled, with the reasoning.
- Rank. A list of 30 equal risks is useless; the founder needs the top 5 and the top 1.
- Do not soften for the author. Do not pile on either: one finding per root cause.
- Respect the decision log: decided items can be challenged once, briefly, not re-litigated.
