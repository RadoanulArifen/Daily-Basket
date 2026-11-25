<?php

namespace App\Support;

/**
 * Countries for addresses (checkout, account address book, draft orders) and the
 * "Countries we deliver to" setting (Settings → Shipping). Names in English and Arabic, from the
 * Unicode CLDR list, so no PHP extension is needed at runtime.
 *
 * Addresses store the ISO 3166-1 alpha-2 code (`country`) plus a free-text `state` (state,
 * province, region or emirate). For the United Arab Emirates the state is also matched to one of
 * the seven emirates, which Aramex rates and labels use.
 */
final class Countries
{
    /** @var array<string, array{0: string, 1: string}> code => [English, Arabic] */
    public const NAMES = [
        'AF' => ['Afghanistan', 'أفغانستان'],
        'AL' => ['Albania', 'ألبانيا'],
        'DZ' => ['Algeria', 'الجزائر'],
        'AS' => ['American Samoa', 'ساموا الأمريكية'],
        'AD' => ['Andorra', 'أندورا'],
        'AO' => ['Angola', 'أنغولا'],
        'AI' => ['Anguilla', 'أنغويلا'],
        'AQ' => ['Antarctica', 'أنتاركتيكا'],
        'AG' => ['Antigua & Barbuda', 'أنتيغوا وبربودا'],
        'AR' => ['Argentina', 'الأرجنتين'],
        'AM' => ['Armenia', 'أرمينيا'],
        'AW' => ['Aruba', 'أروبا'],
        'AU' => ['Australia', 'أستراليا'],
        'AT' => ['Austria', 'النمسا'],
        'AZ' => ['Azerbaijan', 'أذربيجان'],
        'BS' => ['Bahamas', 'جزر البهاما'],
        'BH' => ['Bahrain', 'البحرين'],
        'BD' => ['Bangladesh', 'بنغلاديش'],
        'BB' => ['Barbados', 'بربادوس'],
        'BY' => ['Belarus', 'بيلاروس'],
        'BE' => ['Belgium', 'بلجيكا'],
        'BZ' => ['Belize', 'بليز'],
        'BJ' => ['Benin', 'بنين'],
        'BM' => ['Bermuda', 'برمودا'],
        'BT' => ['Bhutan', 'بوتان'],
        'BO' => ['Bolivia', 'بوليفيا'],
        'BA' => ['Bosnia & Herzegovina', 'البوسنة والهرسك'],
        'BW' => ['Botswana', 'بوتسوانا'],
        'BV' => ['Bouvet Island', 'جزيرة بوفيه'],
        'BR' => ['Brazil', 'البرازيل'],
        'IO' => ['British Indian Ocean Territory', 'الإقليم البريطاني في المحيط الهندي'],
        'VG' => ['British Virgin Islands', 'جزر فيرجن البريطانية'],
        'BN' => ['Brunei', 'بروناي'],
        'BG' => ['Bulgaria', 'بلغاريا'],
        'BF' => ['Burkina Faso', 'بوركينا فاسو'],
        'BI' => ['Burundi', 'بوروندي'],
        'KH' => ['Cambodia', 'كمبوديا'],
        'CM' => ['Cameroon', 'الكاميرون'],
        'CA' => ['Canada', 'كندا'],
        'CV' => ['Cape Verde', 'الرأس الأخضر'],
        'BQ' => ['Caribbean Netherlands', 'هولندا الكاريبية'],
        'KY' => ['Cayman Islands', 'جزر كايمان'],
        'CF' => ['Central African Republic', 'جمهورية أفريقيا الوسطى'],
        'TD' => ['Chad', 'تشاد'],
        'CL' => ['Chile', 'تشيلي'],
        'CN' => ['China', 'الصين'],
        'CX' => ['Christmas Island', 'جزيرة كريسماس'],
        'CC' => ['Cocos (Keeling) Islands', 'جزر كوكوس (كيلينغ)'],
        'CO' => ['Colombia', 'كولومبيا'],
        'KM' => ['Comoros', 'جزر القمر'],
        'CG' => ['Congo - Brazzaville', 'الكونغو - برازافيل'],
        'CD' => ['Congo - Kinshasa', 'الكونغو - كينشاسا'],
        'CK' => ['Cook Islands', 'جزر كوك'],
        'CR' => ['Costa Rica', 'كوستاريكا'],
        'HR' => ['Croatia', 'كرواتيا'],
        'CU' => ['Cuba', 'كوبا'],
        'CW' => ['Curaçao', 'كوراساو'],
        'CY' => ['Cyprus', 'قبرص'],
        'CZ' => ['Czechia', 'التشيك'],
        'CI' => ['Côte d’Ivoire', 'ساحل العاج'],
        'DK' => ['Denmark', 'الدانمرك'],
        'DJ' => ['Djibouti', 'جيبوتي'],
        'DM' => ['Dominica', 'دومينيكا'],
        'DO' => ['Dominican Republic', 'جمهورية الدومينيكان'],
        'EC' => ['Ecuador', 'الإكوادور'],
        'EG' => ['Egypt', 'مصر'],
        'SV' => ['El Salvador', 'السلفادور'],
        'GQ' => ['Equatorial Guinea', 'غينيا الاستوائية'],
        'ER' => ['Eritrea', 'إريتريا'],
        'EE' => ['Estonia', 'إستونيا'],
        'SZ' => ['Eswatini', 'إسواتيني'],
        'ET' => ['Ethiopia', 'إثيوبيا'],
        'FK' => ['Falkland Islands', 'جزر فوكلاند'],
        'FO' => ['Faroe Islands', 'جزر فارو'],
        'FJ' => ['Fiji', 'فيجي'],
        'FI' => ['Finland', 'فنلندا'],
        'FR' => ['France', 'فرنسا'],
        'GF' => ['French Guiana', 'غويانا الفرنسية'],
        'PF' => ['French Polynesia', 'بولينيزيا الفرنسية'],
        'TF' => ['French Southern Territories', 'الأقاليم الجنوبية الفرنسية'],
        'GA' => ['Gabon', 'الغابون'],
        'GM' => ['Gambia', 'غامبيا'],
        'GE' => ['Georgia', 'جورجيا'],
        'DE' => ['Germany', 'ألمانيا'],
        'GH' => ['Ghana', 'غانا'],
        'GI' => ['Gibraltar', 'جبل طارق'],
        'GR' => ['Greece', 'اليونان'],
        'GL' => ['Greenland', 'غرينلاند'],
        'GD' => ['Grenada', 'غرينادا'],
        'GP' => ['Guadeloupe', 'غوادلوب'],
        'GU' => ['Guam', 'غوام'],
        'GT' => ['Guatemala', 'غواتيمالا'],
        'GG' => ['Guernsey', 'غيرنزي'],
        'GN' => ['Guinea', 'غينيا'],
        'GW' => ['Guinea-Bissau', 'غينيا بيساو'],
        'GY' => ['Guyana', 'غيانا'],
        'HT' => ['Haiti', 'هايتي'],
        'HM' => ['Heard & McDonald Islands', 'جزيرة هيرد وجزر ماكدونالد'],
        'HN' => ['Honduras', 'هندوراس'],
        'HK' => ['Hong Kong SAR China', 'هونغ كونغ الصينية (منطقة إدارية خاصة)'],
        'HU' => ['Hungary', 'هنغاريا'],
        'IS' => ['Iceland', 'آيسلندا'],
        'IN' => ['India', 'الهند'],
        'ID' => ['Indonesia', 'إندونيسيا'],
        'IR' => ['Iran', 'إيران'],
        'IQ' => ['Iraq', 'العراق'],
        'IE' => ['Ireland', 'أيرلندا'],
        'IM' => ['Isle of Man', 'جزيرة مان'],
        'IL' => ['Israel', 'إسرائيل'],
        'IT' => ['Italy', 'إيطاليا'],
        'JM' => ['Jamaica', 'جامايكا'],
        'JP' => ['Japan', 'اليابان'],
        'JE' => ['Jersey', 'جيرسي'],
        'JO' => ['Jordan', 'الأردن'],
        'KZ' => ['Kazakhstan', 'كازاخستان'],
        'KE' => ['Kenya', 'كينيا'],
        'KI' => ['Kiribati', 'كيريباتي'],
        'XK' => ['Kosovo', 'كوسوفو'],
        'KW' => ['Kuwait', 'الكويت'],
        'KG' => ['Kyrgyzstan', 'قيرغيزستان'],
        'LA' => ['Laos', 'لاوس'],
        'LV' => ['Latvia', 'لاتفيا'],
        'LB' => ['Lebanon', 'لبنان'],
        'LS' => ['Lesotho', 'ليسوتو'],
        'LR' => ['Liberia', 'ليبيريا'],
        'LY' => ['Libya', 'ليبيا'],
        'LI' => ['Liechtenstein', 'ليختنشتاين'],
        'LT' => ['Lithuania', 'ليتوانيا'],
        'LU' => ['Luxembourg', 'لوكسمبورغ'],
        'MO' => ['Macao SAR China', 'منطقة ماكاو الإدارية الخاصة'],
        'MG' => ['Madagascar', 'مدغشقر'],
        'MW' => ['Malawi', 'ملاوي'],
        'MY' => ['Malaysia', 'ماليزيا'],
        'MV' => ['Maldives', 'جزر المالديف'],
        'ML' => ['Mali', 'مالي'],
        'MT' => ['Malta', 'مالطا'],
        'MH' => ['Marshall Islands', 'جزر مارشال'],
        'MQ' => ['Martinique', 'جزر المارتينيك'],
        'MR' => ['Mauritania', 'موريتانيا'],
        'MU' => ['Mauritius', 'موريشيوس'],
        'YT' => ['Mayotte', 'مايوت'],
        'MX' => ['Mexico', 'المكسيك'],
        'FM' => ['Micronesia', 'ميكرونيزيا'],
        'MD' => ['Moldova', 'مولدوفا'],
        'MC' => ['Monaco', 'موناكو'],
        'MN' => ['Mongolia', 'منغوليا'],
        'ME' => ['Montenegro', 'الجبل الأسود'],
        'MS' => ['Montserrat', 'مونتسرات'],
        'MA' => ['Morocco', 'المغرب'],
        'MZ' => ['Mozambique', 'موزمبيق'],
        'MM' => ['Myanmar (Burma)', 'ميانمار (بورما)'],
        'NA' => ['Namibia', 'ناميبيا'],
        'NR' => ['Nauru', 'ناورو'],
        'NP' => ['Nepal', 'نيبال'],
        'NL' => ['Netherlands', 'هولندا'],
        'NC' => ['New Caledonia', 'كاليدونيا الجديدة'],
        'NZ' => ['New Zealand', 'نيوزيلندا'],
        'NI' => ['Nicaragua', 'نيكاراغوا'],
        'NE' => ['Niger', 'النيجر'],
        'NG' => ['Nigeria', 'نيجيريا'],
        'NU' => ['Niue', 'نيوي'],
        'NF' => ['Norfolk Island', 'جزيرة نورفولك'],
        'KP' => ['North Korea', 'كوريا الشمالية'],
        'MK' => ['North Macedonia', 'مقدونيا الشمالية'],
        'MP' => ['Northern Mariana Islands', 'جزر ماريانا الشمالية'],
        'NO' => ['Norway', 'النرويج'],
        'OM' => ['Oman', 'عُمان'],
        'PK' => ['Pakistan', 'باكستان'],
        'PW' => ['Palau', 'بالاو'],
        'PS' => ['Palestinian Territories', 'الأراضي الفلسطينية'],
        'PA' => ['Panama', 'بنما'],
        'PG' => ['Papua New Guinea', 'بابوا غينيا الجديدة'],
        'PY' => ['Paraguay', 'باراغواي'],
        'PE' => ['Peru', 'بيرو'],
        'PH' => ['Philippines', 'الفلبين'],
        'PN' => ['Pitcairn Islands', 'جزر بيتكيرن'],
        'PL' => ['Poland', 'بولندا'],
        'PT' => ['Portugal', 'البرتغال'],
        'PR' => ['Puerto Rico', 'بورتوريكو'],
        'QA' => ['Qatar', 'قطر'],
        'RO' => ['Romania', 'رومانيا'],
        'RU' => ['Russia', 'روسيا'],
        'RW' => ['Rwanda', 'رواندا'],
        'RE' => ['Réunion', 'روينيون'],
        'WS' => ['Samoa', 'ساموا'],
        'SM' => ['San Marino', 'سان مارينو'],
        'CQ' => ['Sark', 'Sark'],
        'SA' => ['Saudi Arabia', 'المملكة العربية السعودية'],
        'SN' => ['Senegal', 'السنغال'],
        'RS' => ['Serbia', 'صربيا'],
        'SC' => ['Seychelles', 'سيشل'],
        'SL' => ['Sierra Leone', 'سيراليون'],
        'SG' => ['Singapore', 'سنغافورة'],
        'SX' => ['Sint Maarten', 'سانت مارتن'],
        'SK' => ['Slovakia', 'سلوفاكيا'],
        'SI' => ['Slovenia', 'سلوفينيا'],
        'SB' => ['Solomon Islands', 'جزر سليمان'],
        'SO' => ['Somalia', 'الصومال'],
        'ZA' => ['South Africa', 'جنوب أفريقيا'],
        'GS' => ['South Georgia & South Sandwich Islands', 'جورجيا الجنوبية وجزر ساندويتش الجنوبية'],
        'KR' => ['South Korea', 'كوريا الجنوبية'],
        'SS' => ['South Sudan', 'جنوب السودان'],
        'ES' => ['Spain', 'إسبانيا'],
        'LK' => ['Sri Lanka', 'سريلانكا'],
        'BL' => ['St. Barthélemy', 'سان بارتليمي'],
        'SH' => ['St. Helena', 'سانت هيلينا'],
        'KN' => ['St. Kitts & Nevis', 'سانت كيتس ونيفيس'],
        'LC' => ['St. Lucia', 'سانت لوسيا'],
        'MF' => ['St. Martin', 'سان مارتن'],
        'PM' => ['St. Pierre & Miquelon', 'سان بيير ومكويلون'],
        'VC' => ['St. Vincent & Grenadines', 'سانت فنسنت وجزر غرينادين'],
        'SD' => ['Sudan', 'السودان'],
        'SR' => ['Suriname', 'سورينام'],
        'SJ' => ['Svalbard & Jan Mayen', 'سفالبارد وجان ماين'],
        'SE' => ['Sweden', 'السويد'],
        'CH' => ['Switzerland', 'سويسرا'],
        'SY' => ['Syria', 'سوريا'],
        'ST' => ['São Tomé & Príncipe', 'ساو تومي وبرينسيبي'],
        'TW' => ['Taiwan', 'تايوان'],
        'TJ' => ['Tajikistan', 'طاجيكستان'],
        'TZ' => ['Tanzania', 'تنزانيا'],
        'TH' => ['Thailand', 'تايلاند'],
        'TL' => ['Timor-Leste', 'تيمور - ليشتي'],
        'TG' => ['Togo', 'توغو'],
        'TK' => ['Tokelau', 'توكيلاو'],
        'TO' => ['Tonga', 'تونغا'],
        'TT' => ['Trinidad & Tobago', 'ترينيداد وتوباغو'],
        'TN' => ['Tunisia', 'تونس'],
        'TM' => ['Turkmenistan', 'تركمانستان'],
        'TC' => ['Turks & Caicos Islands', 'جزر توركس وكايكوس'],
        'TV' => ['Tuvalu', 'توفالو'],
        'TR' => ['Türkiye', 'تركيا'],
        'UM' => ['U.S. Outlying Islands', 'جزر الولايات المتحدة النائية'],
        'VI' => ['U.S. Virgin Islands', 'جزر فيرجن الأمريكية'],
        'UG' => ['Uganda', 'أوغندا'],
        'UA' => ['Ukraine', 'أوكرانيا'],
        'AE' => ['United Arab Emirates', 'الإمارات العربية المتحدة'],
        'GB' => ['United Kingdom', 'المملكة المتحدة'],
        'US' => ['United States', 'الولايات المتحدة'],
        'UY' => ['Uruguay', 'أورغواي'],
        'UZ' => ['Uzbekistan', 'أوزبكستان'],
        'VU' => ['Vanuatu', 'فانواتو'],
        'VA' => ['Vatican City', 'الفاتيكان'],
        'VE' => ['Venezuela', 'فنزويلا'],
        'VN' => ['Vietnam', 'فيتنام'],
        'WF' => ['Wallis & Futuna', 'جزر والس وفوتونا'],
        'EH' => ['Western Sahara', 'الصحراء الغربية'],
        'YE' => ['Yemen', 'اليمن'],
        'ZM' => ['Zambia', 'زامبيا'],
        'ZW' => ['Zimbabwe', 'زيمبابوي'],
        'AX' => ['Åland Islands', 'جزر آلاند'],
    ];

    /** code => name in the current language, sorted by name. */
    public static function all(?string $locale = null): array
    {
        $i = ($locale ?? app()->getLocale()) === 'ar' ? 1 : 0;
        $names = array_map(fn ($pair) => $pair[$i], self::NAMES);
        asort($names, SORT_LOCALE_STRING);

        return $names;
    }

    public static function name(?string $code, ?string $locale = null): string
    {
        $code = strtoupper((string) $code);

        return self::all($locale)[$code] ?? $code;
    }

    /** Codes customers may choose (Settings → Shipping → Countries); none ticked = every country. */
    public static function allowedCodes(): array
    {
        $chosen = array_values(array_intersect(
            array_map('strtoupper', (array) settings()->get('shipping.countries', [])),
            array_keys(self::NAMES),
        ));

        return $chosen ?: array_keys(self::NAMES);
    }

    /** code => name, only the countries customers may choose. */
    public static function allowed(?string $locale = null): array
    {
        return array_intersect_key(self::all($locale), array_flip(self::allowedCodes()));
    }

    /** Pre-selected on new address forms: Settings → Shipping, else STORE_COUNTRY, if allowed. */
    public static function default(): string
    {
        $code = strtoupper((string) (settings()->get('shipping.default_country') ?: config('store.default_country', 'AE')));
        $allowed = self::allowedCodes();

        return in_array($code, $allowed, true) ? $code : $allowed[0];
    }

    /** For the UAE: the emirate code matching a state name or code ('Dubai', 'dubai', 'دبي'); else null. */
    public static function emirate(?string $country, ?string $state): ?string
    {
        if (strtoupper((string) $country) !== 'AE' || blank($state)) {
            return null;
        }
        $wanted = mb_strtolower(trim((string) $state));
        foreach ((array) config('store.emirates') as $code) {
            $names = [$code, str_replace('_', ' ', $code), __("store.emirates.{$code}", [], 'en'), __("store.emirates.{$code}", [], 'ar')];
            if (in_array($wanted, array_map('mb_strtolower', $names), true)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * The destination key used for delivery rates: the emirate code inside the UAE (so Aramex can
     * price it), otherwise the country code.
     */
    public static function shippingRegion(?string $country, ?string $state): string
    {
        return self::emirate($country, $state) ?? strtoupper((string) $country);
    }

    /** "City, State, Country" for addresses shown to people. */
    public static function line(?string $city, ?string $state, ?string $country, ?string $emirate = null): string
    {
        $state = filled($state) ? $state : (filled($emirate) ? __("store.emirates.{$emirate}") : null);
        $country = filled($country) ? $country : (filled($emirate) ? 'AE' : null);

        return collect([$city, $state, $country ? self::name($country) : null])->filter()->implode(', ');
    }
}
