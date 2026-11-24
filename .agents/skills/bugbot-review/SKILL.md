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

After the push, note the head commit and the time, then comment on the PR:

```sh
head=$(gh pr view <n> --repo vimeo/psalm --json headRefOid --jq .headRefOid)
requested=$(date -u +%Y-%m-%dT%H:%M:%SZ)
gh pr comment <n> --repo vimeo/psalm --body 'bugbot review'
```

## 2. Wait for the findings

Bugbot (user `cursor[bot]`) reacts with 👀 to the comment (👍 once it's done) and runs a
`Cursor Bugbot` check on the head commit, which takes about 5 minutes. Then it posts:

- a PR review whose body starts with `<!-- BUGBOT_REVIEW -->`, with `commit_id` = the
  reviewed commit. It says "found no new issues" for a clean round, or "found N potential
  issues";
- one review comment per finding (`### Title`, severity, description). Its
  `original_commit_id` is the reviewed commit. `commit_id` moves to the PR head on later
  pushes, so don't use it to tell rounds apart.

Wait for that review with a background until-loop (no foreground `sleep`), then read it
and its findings:

```sh
deadline=$(( $(date +%s) + 1200 ))
review=
until review=$(gh api repos/vimeo/psalm/pulls/<n>/reviews --paginate --jq ".[] | select(.user.login ==
    \"cursor[bot]\" and .commit_id == \"$head\" and .submitted_at > \"$requested\") | .id" | tail -n 1) \
    && [ -n "$review" ] || [ "$(date +%s)" -ge "$deadline" ]; do
  sleep 60
done
if [ -z "$review" ]; then
  echo 'no Bugbot review in 20 minutes'
else
  gh api repos/vimeo/psalm/pulls/<n>/reviews/$review --jq .body
  gh api repos/vimeo/psalm/pulls/<n>/reviews/$review/comments --paginate \
    --jq '.[] | {id, path, line: .original_line, body}'
fi
```

If the loop times out without a 👀 or a check, comment `bugbot run` and wait again. Bugbot
marks the reviews of earlier rounds "Stale Bugbot comment from a previous run". Don't treat a
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
