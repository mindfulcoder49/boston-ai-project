#!/usr/bin/env python3
"""
PublicDataWatch sysadmin CLI.

Usage:
    python main.py sync-ec2-dns                  # Run once and repair if needed
    python main.py sync-ec2-dns --dry-run        # Preview without updating
    python main.py check-ec2-dns --json          # Inspect state without updating
    python main.py schedule                      # Run on a repeating schedule
    python main.py schedule --interval 5         # Every 5 minutes
"""
import json
import logging
import sys

import click
from dotenv import load_dotenv

load_dotenv()

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
    handlers=[logging.StreamHandler(sys.stdout)],
)


@click.group()
@click.option("--debug", is_flag=True, help="Enable verbose debug logging.")
def cli(debug):
    """PublicDataWatch remote sysadmin tools."""
    if debug:
        logging.getLogger().setLevel(logging.DEBUG)


@cli.command("sync-ec2-dns")
@click.option("--dry-run", is_flag=True, help="Show what would change without touching DNS.")
@click.option("--json", "json_output", is_flag=True, help="Emit the result payload as JSON.")
@click.option("--no-publish-status", is_flag=True, help="Skip publishing the DNS status artifact.")
def sync_ec2_dns_cmd(dry_run, json_output, no_publish_status):
    """Check EC2 public IP and update the Hostinger DNS A record if it changed."""
    from actions.sync_ec2_dns import sync_ec2_dns

    try:
        result = sync_ec2_dns(dry_run=dry_run, publish_status=not no_publish_status)
    except Exception as exc:
        click.echo(f"Error: {exc}", err=True)
        sys.exit(1)

    if json_output:
        click.echo(json.dumps(result, indent=2, sort_keys=True))
        return

    if not result["changed"]:
        click.echo(f"DNS already correct: {result['ip']}")
    elif result["status"] == "dry_run":
        click.echo(f"[DRY RUN] Would update: {result['old_ip']} -> {result['new_ip']}")
    else:
        click.echo(f"DNS updated: {result['old_ip']} -> {result['new_ip']}")

    publish = result.get("status_publish")
    if publish and publish.get("published"):
        click.echo(f"Published DNS status: s3://{publish['bucket']}/{publish['key']}")
    elif publish:
        click.echo(
            f"DNS status was not published ({publish.get('reason', 'unknown_reason')}).",
            err=True,
        )


@cli.command("check-ec2-dns")
@click.option("--json", "json_output", is_flag=True, help="Emit the inspection payload as JSON.")
@click.option("--publish-status", is_flag=True, help="Publish the inspection payload to S3.")
def check_ec2_dns_cmd(json_output, publish_status):
    """Inspect the current EC2 and DNS state without updating the DNS record."""
    from actions.sync_ec2_dns import inspect_ec2_dns, publish_dns_status

    try:
        result = inspect_ec2_dns()
        if publish_status:
            result["status_publish"] = publish_dns_status(result)
    except Exception as exc:
        click.echo(f"Error: {exc}", err=True)
        sys.exit(1)

    if json_output:
        click.echo(json.dumps(result, indent=2, sort_keys=True))
        return

    if result["changed"]:
        click.echo(f"DNS mismatch: {result['old_ip']} -> {result['new_ip']}")
    else:
        click.echo(f"DNS already correct: {result['ip']}")

    publish = result.get("status_publish")
    if publish and publish.get("published"):
        click.echo(f"Published DNS status: s3://{publish['bucket']}/{publish['key']}")
    elif publish:
        click.echo(
            f"DNS status was not published ({publish.get('reason', 'unknown_reason')}).",
            err=True,
        )


@cli.command("schedule")
@click.option(
    "--interval",
    default=None,
    type=int,
    help="Interval in minutes (default: SYNC_INTERVAL_MINUTES from .env, fallback 15).",
)
def schedule_cmd(interval):
    """Run the EC2 DNS sync repeatedly on a schedule."""
    from apscheduler.schedulers.blocking import BlockingScheduler
    from actions.sync_ec2_dns import sync_ec2_dns
    import config

    minutes = interval or config.SYNC_INTERVAL_MINUTES

    def job():
        try:
            result = sync_ec2_dns()
            if result["changed"]:
                click.echo(f"DNS updated: {result['old_ip']} -> {result['new_ip']}")
            else:
                click.echo(f"DNS OK: {result['ip']}")

            publish = result.get("status_publish")
            if publish and publish.get("published"):
                click.echo(f"Published DNS status: s3://{publish['bucket']}/{publish['key']}")
            elif publish:
                click.echo(
                    f"DNS status was not published ({publish.get('reason', 'unknown_reason')}).",
                    err=True,
                )
        except Exception as exc:
            click.echo(f"Error during sync: {exc}", err=True)

    scheduler = BlockingScheduler()
    scheduler.add_job(job, "interval", minutes=minutes, id="sync_ec2_dns")

    click.echo(f"Scheduler started, syncing every {minutes} minute(s). Press Ctrl+C to stop.")
    job()
    try:
        scheduler.start()
    except (KeyboardInterrupt, SystemExit):
        click.echo("Scheduler stopped.")


if __name__ == "__main__":
    cli()
