<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

use Tygh\Registry;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;

$customers_items = [
    'vendor_administrators' => [
        'href' => 'profiles.manage?user_type=V',
        'alt' => 'profiles.update?user_type=V',
        'position' => 250,
    ]
];

/**
 * @var array<string, array<string, array>> $schema
 */
$schema['central']['customers']['items'] = $customers_items + $schema['central']['customers']['items'];

$schema['central']['vendors'] = [
    'title' => __('vendors_menu_title'),
    'items' => [
        'vendors' => [
            'href' => 'companies.manage',
            'alt' => 'companies.add,companies.update,companies.invitations',
            'position' => 100,
        ],
        'vendor_accounting' => [
            'href' => 'companies.balance',
            'position' => 200,
        ],
    ],
    'position' => 600,
];

$schema['top']['administration']['items']['notifications']['subitems']['vendor_notifications'] = [
    'href' => 'notification_settings.manage?receiver_type=' . UserTypes::VENDOR,
    'position' => 300,
];

$schema['top']['administration']['items']['import_data']['subitems']['vendors'] = [
    'href' => 'exim.import?section=vendors',
    'position' => 600,
];

$schema['top']['administration']['items']['export_data']['subitems']['vendors'] = [
    'href' => 'exim.export?section=vendors',
    'position' => 600,
];

$schema['top']['settings']['items']['Vendors'] = [
    'href' => 'settings.manage?section_id=Vendors',
    'position' => 950,
    'type' => 'setting',
];

$schema['top']['administration']['items']['Storefronts'] = [
    'href'     => 'storefronts.manage',
    'position' => 90,
];

if (Registry::get('runtime.company_id')) {
    $schema['top']['administration']['items']['import_data'] = [
        'href' => 'exim.import',
        'position' => 1300,
        'subitems' => [
            'products_deprecated' => [
                'href' => 'exim.import?section=products',
                'position' => 700,
            ],
        ],
    ];

    $schema['top']['administration']['items']['export_data'] = [
        'href' => 'exim.export',
        'position' => 1400,
        'subitems' => [
            'orders' => [
                'href' => 'exim.export?section=orders',
                'position' => 100,
            ],
            'products' => [
                'href' => 'exim.export?section=products',
                'position' => 200,
            ],
        ],
    ];

    $schema['top']['administration']['items']['sync_data'] = [
        'href'              => 'sync_data.manage',
        'position'          => 1500,
        'depends_on_scheme' => true
    ];
} else {
    unset($schema['top']['administration']['items']['sync_data']);
}

if (Registry::get('settings.Vendors.allow_vendor_manage_features') == YesNo::YES) {
    $schema['top']['administration']['items']['export_data']['subitems']['features'] = [
        'href' => 'exim.export?section=features',
        'position' => 300,
    ];
    $schema['top']['administration']['items']['import_data']['subitems']['features'] = [
        'href' => 'exim.import?section=features',
        'position' => 800,
    ];
}

return $schema;
