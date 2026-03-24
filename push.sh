#!/bin/bash

MESSAGE=${1:-"Auto update: $(date '+%Y-%m-%d %H:%M:%S')"}

echo "🚀 Pushing to GitHub..."
git add .
git commit -m "$MESSAGE"
git push origin main

echo " Hey Mayur!!! ✅ Done! Code pushed successfully."