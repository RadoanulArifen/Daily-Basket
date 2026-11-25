<?php

// Analytics → Traffic.
return [
    'title' => 'Traffic',
    'button' => 'Traffic',
    'subtitle' => 'Who visits your shop, from where, and who is there right now.',
    'periods' => ['today' => 'Today', '7d' => '7 days', '30d' => '30 days', '90d' => '90 days', '12m' => '12 months'],
    'vs_previous' => ':change vs previous period',
    'no_previous' => 'No earlier data to compare',

    'kpi' => [
        'live' => 'On the shop now',
        'live_help' => 'Active in the last 5 minutes',
        'visitors' => 'Visitors',
        'visits' => 'Visits',
        'new_share' => ':share% from new visitors',
        'pages' => 'Pages viewed',
        'per_visit' => ':n pages per visit',
        'countries' => 'Countries',
        'top_country' => ':country :share%',
        'bots' => 'Bot hits left out',
        'bots_split' => 'Search :search · AI :ai · fake :fake · other :other',
    ],

    'map_title' => 'Where visitors come from',
    'legend_fewer' => 'Fewer',
    'legend_more' => 'More visitors',
    'legend_live' => 'On the shop now',
    'tooltip' => ['visitors' => 'Visitors', 'visits' => 'Visits', 'pages' => 'Pages', 'none' => 'No visitors in this period'],

    'live_title' => 'Live now',
    'live_on' => 'Live',
    'live_off' => 'Paused',
    'live_help' => 'Updates every 10 seconds. A visitor counts as here for 5 minutes after their last page.',
    'live_empty' => 'Nobody is on the shop right now.',
    'just_now' => 'just now',
    'minutes_ago' => '{1} 1 min ago|[2,*] :count min ago',
    'unknown_place' => 'Unknown place',

    'countries_title' => 'Top countries',
    'col_country' => 'Country',
    'col_visitors' => 'Visitors',
    'col_visits' => 'Visits',
    'col_pages' => 'Pages',
    'col_share' => 'Share of visitors',
    'no_visits' => 'No visits recorded in this period yet.',

    'geo_missing_title' => 'Countries and cities are not shown yet',
    'geo_missing' => 'Visits are still counted. To see where visitors are, download the free IP location file once on the server:',
    'privacy' => 'No IP addresses are stored: each visitor is a one-way code. Bots and admins are not counted.',
    'credit' => 'IP data by DB-IP',
];
