---
name: bugbot-review
description: Use after opening a vimeo/psalm pull request or pushing to one, and before merging one. Requests a Cursor Bugbot review with a `bugbot review` comment, waits for its findings, fixes or answers them, and re-requests until Bugbot finds nothing new on the head commit.
metadata:
  internal: "true"
---

# Bugbot review loop

Every PR gets a Cursor Bugbot review of its latest head commit before it is handed back or
merged. That includes PRs you rebased, split or only fixed, and PRs by other people that you
merge. Your own review of the diff doesn't replace it; do both.

## 1. Request a review

After the push, comment on the PR:

```sh
gh pr comment <n> --repo vimeo/psalm --body 'bugbot review'
```

Note the head commit: `head=$(gh pr view <n> --repo vimeo/psalm --json headRefOid --jq .headRefOid)`.

## 2. Wait for the findings

Bugbot (user `cursor[bot]`) answers in a few minutes, sometimes up to ~20:

- a PR review whose body starts with `<!-- BUGBOT_REVIEW -->` ("Cursor Bugbot has reviewed
  your changes … and found N potential issues"), with `commit_id` = the reviewed commit;
- one review comment per finding (`### Title`, severity, description), whose
  `original_commit_id` is the reviewed commit. `commit_id` moves to the PR head on later
  pushes, so don't use it to tell rounds apart.

Wait with a Monitor until-loop (no foreground `sleep`), checking every ~3 minutes:

```sh
gh api repos/vimeo/psalm/pulls/<n>/reviews \
  --jq "[.[] | select(.user.login == \"cursor[bot]\" and .commit_id == \"$head\")] | length"
gh api repos/vimeo/psalm/pulls/<n>/comments --paginate \
  --jq ".[] | select(.user.login == \"cursor[bot]\" and .original_commit_id == \"$head\") | {id, path, line, body}"
```

Bugbot posts no review when it finds nothing. If nothing for `$head` appears within 25
minutes of the request, the round is clean. Don't treat a finding on an older commit as
answered just because a newer push exists: check whether the head still has the bug.

## 3. Handle every finding

- **Real bug:** fix the root cause, add a test that fails without the fix (AGENTS.md
  "Tests"), push, and reply in the thread naming the fixing commit.
- **False positive:** reply in the thread with the evidence (the line, the test case, or
  the repro output that shows it's fine). Don't change code to silence it.

Reply in a thread with
`gh api -X POST repos/vimeo/psalm/pulls/<n>/comments/<comment-id>/replies -f body='…'`.

## 4. Repeat

Every push that fixes a finding starts a new round: request `bugbot review` again on the new
head, and loop until a round on the current head finds nothing new. Only then report the PR
as done or merge it. A finding Bugbot repeats after you answered it as a false positive
doesn't need another round; say so in the report.
