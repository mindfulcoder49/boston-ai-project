import logging
import boto3
import config

logger = logging.getLogger(__name__)


def get_ec2_public_ip() -> str:
    """Return the current public IP of the configured EC2 instance."""
    if not config.EC2_INSTANCE_ID and not config.EC2_NAME_TAG:
        raise ValueError("Set EC2_INSTANCE_ID or EC2_NAME_TAG in .env")

    kwargs = {}
    if config.AWS_ACCESS_KEY_ID and config.AWS_SECRET_ACCESS_KEY:
        kwargs = {
            "aws_access_key_id": config.AWS_ACCESS_KEY_ID,
            "aws_secret_access_key": config.AWS_SECRET_ACCESS_KEY,
        }

    client = boto3.client("ec2", region_name=config.AWS_REGION, **kwargs)

    if config.EC2_INSTANCE_ID:
        logger.debug("Looking up EC2 instance by ID: %s", config.EC2_INSTANCE_ID)
        response = client.describe_instances(InstanceIds=[config.EC2_INSTANCE_ID])
    else:
        logger.debug("Looking up EC2 instance by Name tag: %s", config.EC2_NAME_TAG)
        response = client.describe_instances(
            Filters=[
                {"Name": "tag:Name", "Values": [config.EC2_NAME_TAG]},
                {"Name": "instance-state-name", "Values": ["running"]},
            ]
        )

    reservations = response.get("Reservations", [])
    if not reservations:
        raise RuntimeError("No EC2 instance found matching the configured ID or name tag")

    instance = reservations[0]["Instances"][0]
    public_ip = instance.get("PublicIpAddress")

    if not public_ip:
        raise RuntimeError(
            f"Instance {instance.get('InstanceId')} is running but has no public IP assigned"
        )

    logger.debug("Found public IP: %s for instance %s", public_ip, instance.get("InstanceId"))
    return public_ip
