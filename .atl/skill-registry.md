# Skill Registry — ConsultappV2

Generated: 2026-09-19 | Project: ConsultappV2

## Project-Level Skills

| Skill | Trigger | Scope | Path |
|-------|---------|-------|------|
| infer-conventions | detect, infer, document, standardize project conventions or coding style; `.ai/rules` setup | Laravel conventions | `.agents/skills/infer-conventions/SKILL.md` |
| laravel-best-practices | writing, reviewing, or refactoring Laravel PHP code; controllers, models, migrations, policies, jobs | Laravel backend | `.agents/skills/laravel-best-practices/SKILL.md` |
| pest-testing | Pest PHP testing; test writing, editing, fixing, TDD, datasets, mocking, browser testing | Pest 5 testing | `.agents/skills/pest-testing/SKILL.md` |
| tailwindcss-development | tailwind in any form; responsive grids, flex/grid layouts, UI components, dark mode | Tailwind CSS v4 | `.agents/skills/tailwindcss-development/SKILL.md` |

## User-Level Skills (relevant, non-SDD)

| Skill | Trigger | Scope | Path |
|-------|---------|-------|------|
| branch-pr | creating, opening, or preparing PRs for review | Git/PR workflow | `~/.config/opencode/skills/branch-pr/SKILL.md` |
| chained-pr | PRs over 400 lines, stacked PRs, review slices | Git/PR workflow | `~/.config/opencode/skills/chained-pr/SKILL.md` |
| cognitive-doc-design | writing guides, READMEs, RFCs, onboarding, architecture docs | Documentation | `~/.config/opencode/skills/cognitive-doc-design/SKILL.md` |
| comment-writer | PR feedback, issue replies, reviews, Slack/GitHub comments | Collaboration | `~/.config/opencode/skills/comment-writer/SKILL.md` |
| issue-creation | issue creation, bug reports, feature requests, issue approval | Issue tracking | `~/.config/opencode/skills/issue-creation/SKILL.md` |
| judgment-day | dual review, adversarial review | Code review | `~/.config/opencode/skills/judgment-day/SKILL.md` |
| skill-creator | new skills, agent instructions, documenting AI usage patterns | Skills | `~/.config/opencode/skills/skill-creator/SKILL.md` |
| skill-improver | improve skills, audit skills, refactor skills | Skills | `~/.config/opencode/skills/skill-improver/SKILL.md` |
| systemic-issue-triage | new issue, bug report, triage, backlog, root cause | Issue triage | `~/.config/opencode/skills/systemic-issue-triage/SKILL.md` |
| work-unit-commits | implementation, commit splitting, chained PRs | Git workflow | `~/.config/opencode/skills/work-unit-commits/SKILL.md` |
| rdd-defect-workflow | RDD, receipt-driven development, review authority | Quality | `~/.config/opencode/skills/rdd-defect-workflow/SKILL.md` |

## Agent Convention Files

| File | Description |
|------|-------------|
| `AGENTS.md` | Laravel Boost guidelines, PHP rules, deployment, Pint, Pest conventions |

## Registry Notes

- SDD skills (sdd-init, sdd-explore, sdd-propose, sdd-spec, sdd-design, sdd-tasks, sdd-apply, sdd-verify, sdd-archive, sdd-onboard) and `_shared` are excluded from this index — they are orchestrated by the SDD pipeline, not loaded ad-hoc.
- Project-level skills take priority over user-level skills with the same name.
- `go-testing` and `gentle-ai-bench` excluded (not relevant to this Laravel project).
