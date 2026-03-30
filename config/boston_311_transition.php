<?php

return [
    /*
    |--------------------------------------------------------------------------
    | New-System Service Crosswalk
    |--------------------------------------------------------------------------
    |
    | The modernized Boston 311 dataset uses a different taxonomy from the
    | legacy Open311 export. To keep historical analysis, news generation,
    | and longitudinal comparisons meaningful, transitioned service names are
    | mapped back into the legacy "reason" and "type" buckets here while the
    | raw new-system service name is preserved separately.
    |
    */
    'service_name_mappings' => [
        'Street Light Outage' => [
            'reason' => 'Street Lights',
            'type' => 'Street Light Outages',
        ],
        'Street Light Other' => [
            'reason' => 'Street Lights',
            'type' => 'General Lighting Request',
        ],
        'Street Light Knockdown' => [
            'reason' => 'Street Lights',
            'type' => 'Street Light Knock Downs',
        ],
        'Domestic Animal Issue' => [
            'reason' => 'Animal Issues',
            'type' => 'Animal Generic Request',
        ],
        'Wild Animal Issue' => [
            'reason' => 'Animal Issues',
            'type' => 'Animal Generic Request',
        ],
        'Lost Pet' => [
            'reason' => 'Animal Issues',
            'type' => 'Animal Generic Request',
        ],
        'Fallen Tree or Branches' => [
            'reason' => 'Trees',
            'type' => 'Tree Emergencies',
        ],
        'Pruning Request' => [
            'reason' => 'Trees',
            'type' => 'Tree Maintenance Requests',
        ],
        'Tree or Stump Removal' => [
            'reason' => 'Trees',
            'type' => 'Tree Maintenance Requests',
        ],
        'Planting Request' => [
            'reason' => 'Trees',
            'type' => 'New Tree Requests',
        ],
        'Park Groundskeeping' => [
            'reason' => 'Park Maintenance & Safety',
            'type' => 'Ground Maintenance',
        ],
        'Broken Park Equipment' => [
            'reason' => 'Park Maintenance & Safety',
            'type' => 'Equipment Repair',
        ],
        'Park Litter & Debris' => [
            'reason' => 'Park Maintenance & Safety',
            'type' => 'Ground Maintenance',
        ],
        'Park Overflowing Trash Can' => [
            'reason' => 'Park Maintenance & Safety',
            'type' => 'Ground Maintenance',
        ],
        'Park Light Outage' => [
            'reason' => 'Park Maintenance & Safety',
            'type' => 'Parks Lighting/Electrical Issues',
        ],
        'Parks General Request' => [
            'reason' => 'Park Maintenance & Safety',
            'type' => 'Park Improvement Requests',
        ],
        'Park Graffiti' => [
            'reason' => 'Park Maintenance & Safety',
            'type' => 'Ground Maintenance',
        ],
        'Park Dead Animal' => [
            'reason' => 'Park Maintenance & Safety',
            'type' => 'Ground Maintenance',
        ],
        'Ballfield Issue' => [
            'reason' => 'Park Maintenance & Safety',
            'type' => 'Park Improvement Requests',
        ],
        'Locked Gates' => [
            'reason' => 'Park Maintenance & Safety',
            'type' => 'Ground Maintenance',
        ],
        'Park Suggestions' => [
            'reason' => 'Park Maintenance & Safety',
            'type' => 'Park Improvement Requests',
        ],
        'Lane Divider' => [
            'reason' => 'Signs & Signals',
            'type' => 'New Sign  Crosswalk or Pavement Marking',
        ],
        'Cemetery Maintenance' => [
            'reason' => 'Cemetery',
            'type' => 'Cemetery Maintenance Request',
        ],
    ],
];
