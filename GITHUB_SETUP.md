# GitHub Setup Guide

Your WordPress project has been initialized with Git and committed. Follow these steps to push to GitHub:

## Step 1: Create a GitHub Repository

1. Go to [GitHub.com](https://github.com) and sign in
2. Click the "+" icon in the top right corner
3. Select "New repository"
4. Name your repository (e.g., `real_world_wordpress`)
5. Choose Public or Private
6. **DO NOT** initialize with README, .gitignore, or license (we already have these)
7. Click "Create repository"

## Step 2: Add GitHub Remote and Push

After creating the repository, GitHub will show you commands. Use these commands:

```bash
# Add your GitHub repository as remote (replace YOUR_USERNAME and REPO_NAME)
git remote add origin https://github.com/YOUR_USERNAME/REPO_NAME.git

# Or if using SSH (recommended for AWS):
git remote add origin git@github.com:YOUR_USERNAME/REPO_NAME.git

# Push your code to GitHub
git branch -M main
git push -u origin main
```

## Step 3: Fetch on AWS Server

Once pushed to GitHub, on your AWS server:

```bash
# Clone the repository
git clone https://github.com/YOUR_USERNAME/REPO_NAME.git

# Or if you already have the directory:
cd /path/to/your/project
git remote add origin https://github.com/YOUR_USERNAME/REPO_NAME.git
git pull origin main
```

## Important Notes

- **wp-config.php** is excluded from Git (contains sensitive database credentials)
- **wp-content/uploads/** is excluded (user-uploaded files)
- Make sure to create `wp-config.php` on your AWS server with the correct database settings
- Uploads folder will need to be created and given proper permissions on AWS

## Quick Commands Reference

```bash
# Check current remotes
git remote -v

# Change remote URL (if needed)
git remote set-url origin https://github.com/YOUR_USERNAME/REPO_NAME.git

# Push changes
git push origin main

# Pull changes on AWS
git pull origin main
```
