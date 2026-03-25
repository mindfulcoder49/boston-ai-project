import unittest
from unittest.mock import patch

from actions.sync_ec2_dns import inspect_ec2_dns, publish_dns_status, sync_ec2_dns


class SyncEc2DnsTests(unittest.TestCase):
    @patch("actions.sync_ec2_dns.config.DNS_RECORD_NAME", "@")
    @patch("actions.sync_ec2_dns.config.DNS_DOMAIN", "publicdatawatch.com")
    @patch("actions.sync_ec2_dns.config.HOSTINGER_API_TOKEN", "token")
    @patch("actions.sync_ec2_dns.get_current_a_record", return_value="1.1.1.1")
    @patch("actions.sync_ec2_dns.get_ec2_public_ip", return_value="2.2.2.2")
    def test_inspect_reports_mismatch(self, *_args):
        result = inspect_ec2_dns()

        self.assertTrue(result["changed"])
        self.assertEqual("needs_update", result["status"])
        self.assertEqual("1.1.1.1", result["old_ip"])
        self.assertEqual("2.2.2.2", result["new_ip"])
        self.assertEqual("1.1.1.1", result["dns_ip"])
        self.assertEqual("2.2.2.2", result["ec2_ip"])

    @patch("actions.sync_ec2_dns.publish_dns_status")
    @patch("actions.sync_ec2_dns.config.DNS_RECORD_NAME", "@")
    @patch("actions.sync_ec2_dns.config.DNS_DOMAIN", "publicdatawatch.com")
    @patch("actions.sync_ec2_dns.config.HOSTINGER_API_TOKEN", "token")
    @patch("actions.sync_ec2_dns.get_current_a_record", return_value="2.2.2.2")
    @patch("actions.sync_ec2_dns.get_ec2_public_ip", return_value="2.2.2.2")
    def test_sync_no_change_keeps_dns_and_publishes_status(self, _ec2, _dns, publish_status_mock):
        publish_status_mock.return_value = {"published": True, "bucket": "bucket", "key": "ops/health/ec2_dns_status.json"}

        result = sync_ec2_dns()

        self.assertEqual("ok", result["status"])
        self.assertFalse(result["changed"])
        self.assertEqual("2.2.2.2", result["ip"])
        publish_status_mock.assert_called_once()

    @patch("actions.sync_ec2_dns.publish_dns_status")
    @patch("actions.sync_ec2_dns.update_a_record", return_value={"ok": True})
    @patch("actions.sync_ec2_dns.config.DNS_RECORD_TTL", 300)
    @patch("actions.sync_ec2_dns.config.DNS_RECORD_NAME", "@")
    @patch("actions.sync_ec2_dns.config.DNS_DOMAIN", "publicdatawatch.com")
    @patch("actions.sync_ec2_dns.config.HOSTINGER_API_TOKEN", "token")
    @patch("actions.sync_ec2_dns.get_current_a_record", return_value="1.1.1.1")
    @patch("actions.sync_ec2_dns.get_ec2_public_ip", return_value="2.2.2.2")
    def test_sync_updates_dns_when_needed(self, _ec2, _dns, update_mock, publish_status_mock):
        publish_status_mock.return_value = {"published": False, "reason": "missing_bucket", "bucket": None, "key": "ops/health/ec2_dns_status.json"}

        result = sync_ec2_dns()

        self.assertEqual("updated", result["status"])
        self.assertTrue(result["changed"])
        self.assertEqual("2.2.2.2", result["dns_ip"])
        update_mock.assert_called_once()

    @patch("actions.sync_ec2_dns.put_json_object")
    @patch("actions.sync_ec2_dns.config.DNS_STATUS_S3_KEY", "ops/health/ec2_dns_status.json")
    @patch("actions.sync_ec2_dns.config.S3_BUCKET_NAME", "pdw-bucket")
    def test_publish_dns_status_uses_configured_bucket(self, put_json_object_mock):
        payload = {"status": "ok"}

        result = publish_dns_status(payload)

        self.assertEqual(
            {"published": True, "bucket": "pdw-bucket", "key": "ops/health/ec2_dns_status.json"},
            result,
        )
        put_json_object_mock.assert_called_once_with("pdw-bucket", "ops/health/ec2_dns_status.json", payload)


if __name__ == "__main__":
    unittest.main()
