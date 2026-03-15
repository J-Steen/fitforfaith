#!/bin/bash
# ─────────────────────────────────────────────────────────────
# FitForFaith — Deployment Script
# Run this on the server after each git pull:  bash deploy.sh
# ─────────────────────────────────────────────────────────────
set -e

echo "▶ Pulling latest code..."
git pull origin main

echo "▶ Setting directory permissions..."
chmod -R 775 storage/
chown -R www-data:www-data storage/ 2>/dev/null || true

echo "▶ Clearing cache..."
rm -f storage/cache/*.cache

echo "✓ Deploy complete."
