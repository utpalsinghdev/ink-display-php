# Vercel Deployment Guide for PHP

## Important Note

**Vercel has limited PHP support.** While Vercel can deploy PHP applications, it's not as straightforward as Node.js. Consider these alternatives:

### Recommended Alternatives for PHP:
1. **Railway** - Excellent PHP support, easy deployment
2. **Render** - Free tier, great PHP support
3. **Fly.io** - Good for PHP applications
4. **Traditional Hosting** - cPanel, shared hosting, VPS

## If You Still Want to Use Vercel

Vercel's PHP support requires specific configuration. Here are the options:

### Option 1: Use Vercel's PHP Runtime (Recommended)

The `vercel.json` has been updated to use Vercel's PHP runtime. However, you may need to:

1. **Remove the `builds` configuration** - Vercel now uses `functions` instead
2. **Ensure all PHP files are in the root** or adjust routes accordingly
3. **Set environment variables** in Vercel dashboard:
   - `GITHUB_USERNAME`
   - `GITHUB_TOKEN` (optional)
   - `OPENWEATHER_API_KEY`
   - `WEATHER_CITY`

### Option 2: Use Serverless Functions

Convert your PHP to Node.js serverless functions, or use a PHP-to-Node bridge.

### Option 3: Static Site + API Proxy

Deploy the frontend as static and proxy API calls to a PHP backend elsewhere.

## Deployment Steps for Vercel

1. **Install Vercel CLI:**
   ```bash
   npm i -g vercel
   ```

2. **Login to Vercel:**
   ```bash
   vercel login
   ```

3. **Deploy:**
   ```bash
   vercel
   ```

4. **Set Environment Variables** in Vercel Dashboard:
   - Go to your project settings
   - Add environment variables
   - Redeploy

## Troubleshooting

**Error: "@vercel/php" not found**
- This package doesn't exist
- Use the updated `vercel.json` with `functions` instead of `builds`

**PHP not executing**
- Vercel's PHP support is limited
- Consider using Railway, Render, or traditional hosting instead

**Cache directory issues**
- Vercel's filesystem is read-only in serverless functions
- You may need to use external storage (Redis, database) for cache

## Better Alternatives

### Railway (Recommended)
```bash
# Install Railway CLI
npm i -g @railway/cli

# Login and deploy
railway login
railway init
railway up
```

### Render
1. Connect your GitHub repo
2. Select "Web Service"
3. Choose PHP environment
4. Set build command: (leave empty)
5. Set start command: `php -S 0.0.0.0:$PORT`

### Traditional Hosting
- Upload files via FTP/SFTP
- Set permissions on `cache/` directory
- Configure `config.php`
- Done!

## Recommendation

For a PHP project like this, **Railway** or **Render** would be much easier and more reliable than Vercel. Vercel is optimized for Node.js/static sites, not PHP.

