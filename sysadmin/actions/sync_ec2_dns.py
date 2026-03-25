import logging
from datetime import datetime, timezone

import config
from aws.ec2 import get_ec2_public_ip
from aws.s3 import put_json_object
from dns.hostinger import get_current_a_record, update_a_record

logger = logging.getLogger(__name__)


def _validate_config() -> None:
    if not config.HOSTINGER_API_TOKEN:
        raise ValueError("HOSTINGER_API_TOKEN is not set in .env")
    if not config.DNS_DOMAIN:
        raise ValueError("DNS_DOMAIN is not set in .env")


def _build_status_payload(*, current_ip: str | None, ec2_ip: str, status: str, changed: bool) -> dict:
    record_label = f"{config.DNS_RECORD_NAME}.{config.DNS_DOMAIN}"
    dns_ip = ec2_ip if status == "updated" else current_ip

    return {
        "checked_at": datetime.now(timezone.utc).isoformat(),
        "status": status,
        "changed": changed,
        "record_label": record_label,
        "dns_ip": dns_ip,
        "ec2_ip": ec2_ip,
        "dns_record_name": config.DNS_RECORD_NAME,
        "dns_domain": config.DNS_DOMAIN,
        "ttl": config.DNS_RECORD_TTL,
    }


def publish_dns_status(status_payload: dict) -> dict:
    bucket = config.S3_BUCKET_NAME
    key = config.DNS_STATUS_S3_KEY

    if not bucket:
        logger.info("Skipping DNS status publish because S3_BUCKET_NAME is not configured.")
        return {
            "published": False,
            "bucket": None,
            "key": key,
            "reason": "missing_bucket",
        }

    put_json_object(bucket, key, status_payload)

    return {
        "published": True,
        "bucket": bucket,
        "key": key,
    }


def inspect_ec2_dns() -> dict:
    """Read the current EC2 and DNS state without updating the DNS record."""
    _validate_config()

    logger.info("Fetching EC2 public IP...")
    ec2_ip = get_ec2_public_ip()
    logger.info("EC2 public IP: %s", ec2_ip)

    record_label = f"{config.DNS_RECORD_NAME}.{config.DNS_DOMAIN}"
    logger.info("Fetching current DNS A record for %s...", record_label)
    current_ip = get_current_a_record(config.DNS_DOMAIN, config.DNS_RECORD_NAME)
    logger.info("Current DNS IP: %s", current_ip or "(not set)")

    changed = current_ip != ec2_ip
    status = "needs_update" if changed else "ok"
    payload = _build_status_payload(
        current_ip=current_ip,
        ec2_ip=ec2_ip,
        status=status,
        changed=changed,
    )
    payload["old_ip"] = current_ip
    payload["new_ip"] = ec2_ip
    payload["ip"] = ec2_ip

    return payload


def sync_ec2_dns(dry_run: bool = False, publish_status: bool = True) -> dict:
    """
    Check the EC2 instance's current public IP and update the Hostinger
    DNS A record if it has changed.
    """
    inspection = inspect_ec2_dns()
    current_ip = inspection.get("old_ip")
    ec2_ip = inspection["ec2_ip"]
    record_label = inspection["record_label"]

    if not inspection["changed"]:
        logger.info("DNS is already up to date. No changes needed.")
        result = {
            **inspection,
            "status": "ok",
            "changed": False,
            "ip": ec2_ip,
        }
    elif dry_run:
        logger.info("[DRY RUN] Would update %s: %s -> %s", record_label, current_ip, ec2_ip)
        result = {
            **inspection,
            "status": "dry_run",
            "changed": True,
        }
    else:
        logger.info("Updating DNS A record %s -> %s ...", record_label, ec2_ip)
        hostinger_result = update_a_record(
            config.DNS_DOMAIN,
            config.DNS_RECORD_NAME,
            ec2_ip,
            config.DNS_RECORD_TTL,
        )
        logger.info("Hostinger response: %s", hostinger_result)
        result = {
            **inspection,
            "status": "updated",
            "changed": True,
            "dns_ip": ec2_ip,
            "hostinger_response": hostinger_result,
        }

    if publish_status:
        result["status_publish"] = publish_dns_status(
            {
                key: value
                for key, value in result.items()
                if key != "status_publish"
            }
        )

    return result
