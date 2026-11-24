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

Bugbot (user `cursor[bot]`) reacts with 👀 to the comment (👍 once it's done) and runs a `Cursor Bugbot`
check on the head commit, which takes about 5 minutes. When the check completes, Bugbot
has posted:

- a PR review whose body starts with `<!-- BUGBOT_REVIEW -->`, with `commit_id` = the
  reviewed commit. It says "found no new issues" for a clean round, or "found N potential
  issues";
- one review comment per finding (`### Title`, severity, description). Its
  `original_commit_id` is the reviewed commit. `commit_id` moves to the PR head on later
  pushes, so don't use it to tell rounds apart.

Wait for the check with a background until-loop (no foreground `sleep`), then read the result:

```sh
until gh api "repos/vimeo/psalm/commits/$head/check-runs" \
  --jq '.check_runs[] | select(.app.slug == "cursor") | .status' | grep -q completed; do sleep 60; done
gh api repos/vimeo/psalm/pulls/<n>/reviews \
  --jq ".[] | select(.user.login == \"cursor[bot]\" and .commit_id == \"$head\") | .body"
gh api repos/vimeo/psalm/pulls/<n>/comments --paginate \
  --jq ".[] | select(.user.login == \"cursor[bot]\" and .original_commit_id == \"$head\") | {id, path, line, body}"
```

If no 👀 or check appears within 15 minutes, comment `bugbot run` instead. Don't treat a
finding on an older commit as answered just because a newer push exists: check whether the
head still has the bug.

## 3. Handle every finding

- **Real bug:** fix the root cause, add a test that fails without the fix (AGENTS.md
  "Tests"), push, and reply in the thread naming the fixing commit.
- **False positive:** reply in the thread with the evidence (the line, the test case, or
  the repro output that shows it's fine). Don't change code to silence it.

Reply in a thread with
`gh api -X POST repos/vimeo/psalm/pulls/<n>/comments/<comment-id>/replies -f body='…'`.

## 4. Repeat

Every push that fixes a finding starts a new round: request `bugbot review` again on the new
head, and loop until the review of the current head says "found no new issues". Only then report the PR
as done or merge it. A finding Bugbot repeats after you answered it as a false positive
doesn't need another round; say so in the report.
