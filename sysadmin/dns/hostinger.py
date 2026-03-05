import logging
import requests
import config

logger = logging.getLogger(__name__)

BASE_URL = "https://developers.hostinger.com"


def _headers() -> dict:
    return {
        "Authorization": f"Bearer {config.HOSTINGER_API_TOKEN}",
        "Content-Type": "application/json",
    }


def get_dns_records(domain: str) -> list:
    """Return all DNS records for a domain."""
    url = f"{BASE_URL}/api/dns/v1/zones/{domain}"
    resp = requests.get(url, headers=_headers(), timeout=15)
    resp.raise_for_status()
    return resp.json()


def get_current_a_record(domain: str, name: str) -> str | None:
    """Return the current IP for an A record, or None if not found."""
    records = get_dns_records(domain)
    for record in records:
        if record.get("type") == "A" and record.get("name") == name:
            contents = record.get("records", [])
            if contents:
                return contents[0].get("content")
    return None


def update_a_record(domain: str, name: str, ip: str, ttl: int = 300) -> dict:
    """Set the A record for name.domain to ip, replacing any existing value."""
    url = f"{BASE_URL}/api/dns/v1/zones/{domain}"
    payload = {
        "overwrite": True,
        "zone": [
            {
                "name": name,
                "type": "A",
                "ttl": ttl,
                "records": [{"content": ip}],
            }
        ],
    }
    logger.debug("PUT %s  payload=%s", url, payload)
    resp = requests.put(url, json=payload, headers=_headers(), timeout=15)
    resp.raise_for_status()
    return resp.json()
