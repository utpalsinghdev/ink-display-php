# Setting Up Private Contributions Display

If your contribution graph is not showing private contributions, follow these steps:

## 1. Check GitHub Profile Settings

**This is the most important step!** GitHub has a profile setting that controls whether private contributions are displayed.

1. Go to your GitHub profile: https://github.com/settings/profile
2. Scroll down to the **"Contributions"** section
3. Make sure **"Include private contributions on my profile"** is **CHECKED/ENABLED**
4. Save the changes

**Note:** This setting must be enabled on GitHub's website for private contributions to appear in the API.

## 2. Verify Token Permissions

Your GitHub Personal Access Token must have the `repo` scope:

1. Go to: https://github.com/settings/tokens
2. Find your token (or create a new one)
3. Make sure the **`repo`** scope is selected
4. If using an organization with SSO, authorize the token for SSO

## 3. Check Commit Email

Make sure the email address in your Git commits matches your GitHub account:

1. Go to: https://github.com/settings/emails
2. Verify all email addresses you use for commits are added and verified
3. Check your Git config: `git config user.email`

## 4. Verify Private Repositories Have Activity

Private contributions only count if:
- Commits are made to the default branch or `gh-pages` branch
- The commits are within the last year
- The email in commits matches your GitHub account

## 5. Test Your Setup

Run this command to verify everything is working:

```bash
php check_token.php
```

This will show:
- ✓ Token validity
- ✓ Token scopes
- ✓ GraphQL API status
- ✓ Contribution counts

## Troubleshooting

If private contributions still don't show:

1. **Wait a few minutes** - GitHub may need time to update
2. **Clear cache** - Delete files in the `cache/` directory
3. **Check SSO** - If your repos are in an organization with SSO, authorize your token
4. **Verify commits** - Make sure you have actual commits in private repos in the last year

## Important Notes

- GitHub's contribution graph only shows contributions from the **last year**
- Only commits to the **default branch** or **gh-pages** count
- Commits in **forked repositories** don't count unless you open a PR
- The API reflects what you see on your GitHub profile page

If your GitHub profile page shows private contributions, the API should also return them when using a token with `repo` scope.

