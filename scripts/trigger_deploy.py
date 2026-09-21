#!/usr/bin/env python3
import os
import sys
import time
import urllib.request
import urllib.error

def trigger_deploy():
    webhook_url = os.environ.get("DOKPLOY_WEBHOOK_URL")
    if not webhook_url:
        print("[ERROR] DOKPLOY_WEBHOOK_URL environment variable is not set.")
        print("Please configure DOKPLOY_WEBHOOK_URL in your GitHub repository secrets.")
        sys.exit(1)

    print("Triggering Dokploy deployment webhook...")
    
    max_retries = 3
    retry_delay = 5

    req = urllib.request.Request(
        webhook_url,
        data=b"",
        headers={
            "User-Agent": "GitHub-Actions-Deploy/1.0",
            "Content-Type": "application/json"
        },
        method="POST"
    )

    for attempt in range(1, max_retries + 1):
        try:
            with urllib.request.urlopen(req, timeout=30) as response:
                status_code = response.getcode()
                body = response.read().decode("utf-8", errors="replace")
                print(f"[SUCCESS] Dokploy webhook responded with status {status_code}: {body}")
                print("Deployment successfully dispatched to Dokploy.")
                return
        except urllib.error.HTTPError as e:
            error_body = e.read().decode("utf-8", errors="replace")
            print(f"[WARNING] Attempt {attempt}/{max_retries}: HTTP Error {e.code}: {error_body}")
        except Exception as e:
            print(f"[WARNING] Attempt {attempt}/{max_retries}: Error: {e}")

        if attempt < max_retries:
            print(f"Retrying in {retry_delay} seconds...")
            time.sleep(retry_delay)

    print("[ERROR] Failed to trigger Dokploy deployment after all attempts.")
    sys.exit(1)

if __name__ == "__main__":
    trigger_deploy()
