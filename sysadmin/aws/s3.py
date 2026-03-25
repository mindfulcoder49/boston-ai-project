import json
import logging

import boto3

import config

logger = logging.getLogger(__name__)


def put_json_object(bucket: str, key: str, payload: dict) -> None:
    """Upload a JSON payload to S3."""
    kwargs = {}
    if config.AWS_ACCESS_KEY_ID and config.AWS_SECRET_ACCESS_KEY:
        kwargs = {
            "aws_access_key_id": config.AWS_ACCESS_KEY_ID,
            "aws_secret_access_key": config.AWS_SECRET_ACCESS_KEY,
        }

    client = boto3.client("s3", region_name=config.AWS_REGION, **kwargs)
    body = json.dumps(payload, indent=2, sort_keys=True).encode("utf-8")
    logger.debug("Uploading JSON status artifact to s3://%s/%s", bucket, key)
    client.put_object(
        Bucket=bucket,
        Key=key,
        Body=body,
        ContentType="application/json",
    )
