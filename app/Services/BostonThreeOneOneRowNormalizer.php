<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BostonThreeOneOneRowNormalizer
{
    public function isNewSystemRow(array $row): bool
    {
        return array_key_exists('case_id', $row) || array_key_exists('open_date', $row);
    }

    public function normalize(array $row): array
    {
        return $this->isNewSystemRow($row)
            ? $this->normalizeNewSystemRow($row)
            : $this->normalizeLegacyRow($row);
    }

    public function normalizeLegacyRow(array $row): array
    {
        $caseEnquiryId = $this->validateInteger($row['case_enquiry_id'] ?? null, 'case_enquiry_id');
        $serviceRequestId = trim((string) ($row['service_request_id'] ?? $caseEnquiryId ?? ''));

        if ($serviceRequestId === '') {
            throw new \InvalidArgumentException('Missing legacy 311 service request identifier.');
        }

        return [
            'service_request_id' => $serviceRequestId,
            'case_enquiry_id' => $caseEnquiryId,
            'open_dt' => $this->validateDateTime($row['open_dt'] ?? null),
            'sla_target_dt' => $this->normalizeText($row['sla_target_dt'] ?? null),
            'closed_dt' => $this->validateDateTime($row['closed_dt'] ?? null),
            'on_time' => $this->normalizeText($row['on_time'] ?? null),
            'case_status' => $this->normalizeText($row['case_status'] ?? null),
            'closure_reason' => $this->normalizeText($row['closure_reason'] ?? null),
            'closure_comments' => $this->normalizeText($row['closure_comments'] ?? null),
            'case_title' => $this->normalizeText($row['case_title'] ?? null),
            'subject' => $this->normalizeText($row['subject'] ?? null),
            'reason' => $this->normalizeText($row['reason'] ?? null),
            'type' => $this->normalizeText($row['type'] ?? null),
            'service_name' => $this->normalizeText($row['service_name'] ?? ($row['type'] ?? null)),
            'queue' => $this->normalizeText($row['queue'] ?? null),
            'department' => $this->normalizeText($row['department'] ?? null),
            'submitted_photo' => $this->normalizeText($row['submitted_photo'] ?? null),
            'closed_photo' => $this->normalizeText($row['closed_photo'] ?? null),
            'location' => $this->normalizeText($row['location'] ?? null),
            'fire_district' => $this->normalizeText($row['fire_district'] ?? null),
            'pwd_district' => $this->normalizeText($row['pwd_district'] ?? null),
            'city_council_district' => $this->normalizeText($row['city_council_district'] ?? null),
            'police_district' => $this->normalizeText($row['police_district'] ?? null),
            'neighborhood' => $this->normalizeText($row['neighborhood'] ?? null),
            'neighborhood_services_district' => $this->normalizeText($row['neighborhood_services_district'] ?? null),
            'ward' => $this->normalizeText($row['ward'] ?? null),
            'precinct' => $this->normalizeText($row['precinct'] ?? null),
            'location_street_name' => $this->normalizeText($row['location_street_name'] ?? null),
            'location_zipcode' => $this->validateDouble($row['location_zipcode'] ?? null),
            'latitude' => $this->validateDouble($row['latitude'] ?? null),
            'longitude' => $this->validateDouble($row['longitude'] ?? null),
            'source' => $this->normalizeText($row['source'] ?? null),
            'source_system' => 'legacy_open311',
            'ward_number' => $this->normalizeText($row['ward_number'] ?? null),
            'language_code' => $this->normalizeText($row['language_code'] ?? 'en-US'),
            'threeoneonedescription' => $this->normalizeText(
                $row['threeoneonedescription']
                    ?? $row['description']
                    ?? $row['closure_comments']
                    ?? null
            ),
            'source_city' => $this->normalizeText($row['source_city'] ?? 'Boston'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function normalizeNewSystemRow(array $row): array
    {
        $serviceRequestId = trim((string) ($row['case_id'] ?? ''));

        if ($serviceRequestId === '') {
            throw new \InvalidArgumentException('Missing new-system 311 case_id.');
        }

        $serviceName = $this->normalizeText($row['service_name'] ?? null);
        $assignedDepartment = $this->normalizeText($row['assigned_department'] ?? null);
        $assignedTeam = $this->normalizeText($row['assigned_team'] ?? null);
        $normalizedTaxonomy = $this->normalizeNewSystemTaxonomy($serviceName, $assignedDepartment, $assignedTeam);

        return [
            'service_request_id' => $serviceRequestId,
            'case_enquiry_id' => null,
            'open_dt' => $this->validateDateTime($row['open_date'] ?? null),
            'sla_target_dt' => $this->validateDateTime($row['target_close_date'] ?? null),
            'closed_dt' => $this->validateDateTime($row['close_date'] ?? null),
            'on_time' => $this->normalizeText($row['on_time'] ?? null),
            'case_status' => $this->normalizeText($row['case_status'] ?? null),
            'closure_reason' => $this->normalizeText($row['closure_reason'] ?? null),
            'closure_comments' => $this->normalizeText($row['closure_comments'] ?? null),
            'case_title' => $this->normalizeText($row['case_topic'] ?? $serviceName),
            'subject' => $assignedDepartment,
            'reason' => $normalizedTaxonomy['reason'],
            'type' => $normalizedTaxonomy['type'],
            'service_name' => $serviceName,
            'queue' => $assignedTeam,
            'department' => $assignedDepartment,
            'submitted_photo' => $this->normalizeText($row['submitted_photo'] ?? null),
            'closed_photo' => $this->normalizeText($row['closed_photo'] ?? null),
            'location' => $this->normalizeText($row['full_address'] ?? null),
            'fire_district' => $this->normalizeText($row['fire_district'] ?? null),
            'pwd_district' => $this->normalizeText($row['public_works_district'] ?? null),
            'city_council_district' => $this->normalizeText($row['city_council_district'] ?? null),
            'police_district' => $this->normalizeText($row['police_district'] ?? null),
            'neighborhood' => $this->normalizeText($row['neighborhood'] ?? null),
            'neighborhood_services_district' => null,
            'ward' => $this->normalizeText($row['ward'] ?? null),
            'precinct' => $this->normalizeText($row['precinct'] ?? null),
            'location_street_name' => $this->normalizeText($row['street_name'] ?? null),
            'location_zipcode' => $this->validateDouble($row['zip_code'] ?? null),
            'latitude' => $this->validateDouble($row['latitude'] ?? null),
            'longitude' => $this->validateDouble($row['longitude'] ?? null),
            'source' => $this->normalizeText($row['report_source'] ?? null),
            'source_system' => 'modernized_311',
            'ward_number' => null,
            'language_code' => 'en-US',
            'threeoneonedescription' => $this->normalizeText($row['closure_comments'] ?? null),
            'source_city' => 'Boston',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function normalizeNewSystemTaxonomy(?string $serviceName, ?string $assignedDepartment, ?string $assignedTeam): array
    {
        $exactMappings = config('boston_311_transition.service_name_mappings', []);
        $exact = $serviceName ? Arr::get($exactMappings, $serviceName) : null;

        if (is_array($exact)) {
            return $exact;
        }

        $haystack = Str::lower(trim(implode(' ', array_filter([$serviceName, $assignedDepartment, $assignedTeam]))));

        if (Str::contains($haystack, ['animal'])) {
            return ['reason' => 'Animal Issues', 'type' => 'Animal Generic Request'];
        }

        if (Str::contains($haystack, ['street light', 'lighting'])) {
            return ['reason' => 'Street Lights', 'type' => 'General Lighting Request'];
        }

        if (Str::contains($haystack, ['tree', 'forestry', 'branch', 'stump', 'planting'])) {
            return ['reason' => 'Trees', 'type' => 'Tree Maintenance Requests'];
        }

        if (Str::contains($haystack, ['park', 'ballfield'])) {
            return ['reason' => 'Park Maintenance & Safety', 'type' => 'Ground Maintenance'];
        }

        if (Str::contains($haystack, ['cemeter'])) {
            return ['reason' => 'Cemetery', 'type' => 'Cemetery Maintenance Request'];
        }

        if (Str::contains($haystack, ['sign', 'signal', 'lane divider', 'pavement'])) {
            return ['reason' => 'Signs & Signals', 'type' => 'New Sign  Crosswalk or Pavement Marking'];
        }

        return [
            'reason' => $assignedDepartment ?: $serviceName ?: 'Administrative & General Requests',
            'type' => $serviceName ?: $assignedDepartment ?: 'General Request',
        ];
    }

    private function normalizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function validateInteger(mixed $value, string $field): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value) || intval($value) != $value) {
            throw new \InvalidArgumentException("Invalid integer for {$field}: {$value}");
        }

        return intval($value);
    }

    private function validateDouble(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("Invalid numeric value: {$value}");
        }

        return floatval($value);
    }

    private function validateDateTime(mixed $value): ?string
    {
        $text = $this->normalizeText($value);

        if ($text === null) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($text)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException("Invalid datetime value: {$text}");
        }
    }
}
