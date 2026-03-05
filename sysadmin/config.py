import os
from dotenv import load_dotenv

load_dotenv()

AWS_REGION = os.getenv("AWS_REGION", "us-east-1")
AWS_ACCESS_KEY_ID = os.getenv("AWS_ACCESS_KEY_ID") or None
AWS_SECRET_ACCESS_KEY = os.getenv("AWS_SECRET_ACCESS_KEY") or None

EC2_INSTANCE_ID = os.getenv("EC2_INSTANCE_ID") or None
EC2_NAME_TAG = os.getenv("EC2_NAME_TAG") or None

HOSTINGER_API_TOKEN = os.getenv("HOSTINGER_API_TOKEN")
DNS_DOMAIN = os.getenv("DNS_DOMAIN")
DNS_RECORD_NAME = os.getenv("DNS_RECORD_NAME", "@")
DNS_RECORD_TTL = int(os.getenv("DNS_RECORD_TTL", "300"))

SYNC_INTERVAL_MINUTES = int(os.getenv("SYNC_INTERVAL_MINUTES", "15"))
