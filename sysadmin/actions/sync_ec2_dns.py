import logging
from aws.ec2 import get_ec2_public_ip
from dns.hostinger import get_current_a_record, update_a_record
import config

logger = logging.getLogger(__name__)


def sync_ec2_dns(dry_run: bool = False) -> dict:
    """
    Check the EC2 instance's current public IP and update the Hostinger
    DNS A record if it has changed.

    Returns a dict with keys:
        status:   "ok" | "updated" | "dry_run"
        ip:       current IP (when status == "ok")
        old_ip:   previous IP (when status == "updated" or "dry_run")
        new_ip:   new IP     (when status == "updated" or "dry_run")
        changed:  bool
    """
    if not config.HOSTINGER_API_TOKEN:
        raise ValueError("HOSTINGER_API_TOKEN is not set in .env")
    if not config.DNS_DOMAIN:
        raise ValueError("DNS_DOMAIN is not set in .env")

    logger.info("Fetching EC2 public IP...")
    ec2_ip = get_ec2_public_ip()
    logger.info("EC2 public IP: %s", ec2_ip)

    record_label = f"{config.DNS_RECORD_NAME}.{config.DNS_DOMAIN}"
    logger.info("Fetching current DNS A record for %s...", record_label)
    current_ip = get_current_a_record(config.DNS_DOMAIN, config.DNS_RECORD_NAME)
    logger.info("Current DNS IP: %s", current_ip or "(not set)")

    if current_ip == ec2_ip:
        logger.info("DNS is already up to date. No changes needed.")
        return {"status": "ok", "ip": ec2_ip, "changed": False}

    logger.info("IP mismatch — DNS: %s  EC2: %s", current_ip, ec2_ip)

    if dry_run:
        logger.info("[DRY RUN] Would update %s: %s -> %s", record_label, current_ip, ec2_ip)
        return {"status": "dry_run", "old_ip": current_ip, "new_ip": ec2_ip, "changed": True}

    logger.info("Updating DNS A record %s -> %s ...", record_label, ec2_ip)
    result = update_a_record(
        config.DNS_DOMAIN,
        config.DNS_RECORD_NAME,
        ec2_ip,
        config.DNS_RECORD_TTL,
    )
    logger.info("Hostinger response: %s", result)

    return {"status": "updated", "old_ip": current_ip, "new_ip": ec2_ip, "changed": True}
