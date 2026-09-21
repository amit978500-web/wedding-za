# Wedding Za Development Workflow

## Branches

- `main` — stable, tested releases only.
- `develop` — daily development integration branch.
- `feature/<name>` — optional branch for larger isolated changes.

## Daily routine

1. Pull `develop` before starting.
2. Make focused changes.
3. Test locally.
4. Review `git status` and `git diff`.
5. Commit with a clear message.
6. Push `develop` to GitHub.
7. Merge `develop` into `main` only after a release-level QA pass.

## Commit style

Use concise Conventional Commit-style messages:

- `feat: add event type filtering`
- `fix: remove destination scroll lock`
- `style: refine hero typography`
- `perf: optimize image loading`
- `docs: update local setup guide`
- `chore: update project tooling`

## Never commit

- passwords
- API keys
- `.env` files
- production customer data
- `storage/leads.csv`
- hosting or payment credentials
