<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;
use Carbon\Carbon;

class CambridgeThreeOneOneCase extends Model
{
    use HasFactory, Mappable;

    protected $table = 'cambridge_311_service_requests';

    protected $fillable = [
        'ticket_id_external',
        'city',
        'issue_type',
        'issue_category',
        'ticket_status',
        'issue_description',
        'ticket_closed_date_time',
        'ticket_created_date_time',
        'ticket_last_updated_date_time',
        'address',
        'latitude',
        'longitude',
        'location_text',
        'image_url',
        'acknowledged_at',
        'html_url',
    ];

    protected $casts = [
        'ticket_closed_date_time' => 'datetime',
        'ticket_created_date_time' => 'datetime',
        'ticket_last_updated_date_time' => 'datetime',
        'acknowledged_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public static function getHumanName(): string
    {
        return 'Cambridge 311 Service Request';
    }

    public static function getIconClass(): string
    {
        return 'case-div-icon'; // Example icon
    }

    public static function getAlcivartechTypeForStyling(): string
    {
        return '311 Case'; // Consistent with other 311 types if any
    }

    public static function getLatitudeField(): string
    {
        return 'latitude';
    }

    public static function getLongitudeField(): string
    {
        return 'longitude';
    }

    public static function getDateField(): string
    {
        return 'ticket_created_date_time';
    }

    public function getDate(): ?string
    {
        return $this->ticket_created_date_time ? Carbon::parse($this->ticket_created_date_time)->toDateString() : null;
    }

    public static function getExternalIdName(): string
    {
        return 'ticket_id_external';
    }

    public function getExternalId(): string
    {
        return $this->ticket_id_external;
    }

    public static $searchable_columns_config = [
        'issue_type', 'issue_category', 'ticket_status', 'issue_description', 'address'
    ];

    public static function getPopupConfig(): array
    {
        return [
            'mainIdentifierLabel' => 'Ticket ID',
            'mainIdentifierField' => 'ticket_id_external',
            'descriptionLabel' => 'Issue Type',
            'descriptionField' => 'issue_type',
            'additionalFields' => [
                ['label' => 'Status', 'key' => 'ticket_status'],
                ['label' => 'Address', 'key' => 'address'],
            ],
        ];
    }
}
